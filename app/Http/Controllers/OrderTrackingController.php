<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Complaint;
use App\Models\JetTrackEntry;
use App\Models\LastStep;
use App\Models\Logistic;
use App\Models\OrderTracking;
use App\Models\OrderTrackingDataSource;
use App\Models\OrderTrackingErpStatus;
use App\Models\OrderTrackingRgoEntry;
use App\Models\Platform;
use App\Models\ReasonLateResponse;
use App\Models\ReasonWhitelist;
use App\Models\SubCase;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderTrackingController extends Controller
{
    public function index(Request $request): Response
    {
        $sourceOptions = $this->sourceOptions();
        $brandOptions = $this->brandOptions();
        $platformOptions = $this->platformOptions();
        $logisticsOptions = $this->logisticsOptions();
        $erpStatusOptions = $this->erpStatusOptions();
        $csNameOptions = $this->csNameOptions();
        $categoryOptions = $this->categoryOptions();
        $lastStepOptions = $this->lastStepOptions();
        $reasonWhitelistOptions = $this->reasonWhitelistOptions();
        $reasonLateResponseOptions = $this->reasonLateResponseOptions();

        $baseQuery = OrderTracking::query();

        $filteredQuery = (clone $baseQuery)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($trackingQuery) use ($search) {
                    $trackingQuery
                        ->where('data_source', 'like', "%{$search}%")
                        ->orWhere('order_id', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('platform', 'like', "%{$search}%")
                        ->orWhere('logistics', 'like', "%{$search}%")
                        ->orWhere('awb', 'like', "%{$search}%")
                        ->orWhere('erp_status', 'like', "%{$search}%")
                        ->orWhere('cs_name', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('last_step', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('automation_track', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status') && $request->input('status') !== 'All', function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->when($request->filled('cs_name'), function ($query) use ($request) {
                $query->where('cs_name', $request->input('cs_name'));
            })
            ->when($request->filled('brand') && $request->input('brand') !== 'All', function ($query) use ($request) {
                $query->where('brand', $request->input('brand'));
            })
            ->when($request->filled('source') && $request->input('source') !== 'All', function ($query) use ($request) {
                $query->where('data_source', $request->input('source'));
            })
            ->when($request->filled('platform') && $request->input('platform') !== 'All', function ($query) use ($request) {
                $query->where('platform', $request->input('platform'));
            })
            ->when($request->filled('category') && $request->input('category') !== 'All', function ($query) use ($request) {
                $query->where('category', $request->input('category'));
            })
            ->latest('tanggal_input')
            ->latest('id');

        $allOrderTrackings = (clone $filteredQuery)->get();

        $orderTrackings = (clone $filteredQuery)
            ->paginate(10)
            ->withQueryString()
            ->through(fn(OrderTracking $orderTracking) => $this->transformOrderTracking($orderTracking));

        return Inertia::render('OrderTrackings/Index', [
            'orderTrackings' => $orderTrackings,
            'filters' => [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'cs_name' => $request->input('cs_name'),
                'brand' => $request->input('brand'),
                'source' => $request->input('source'),
                'platform' => $request->input('platform'),
                'category' => $request->input('category'),
            ],
            'csSummary' => $allOrderTrackings
                ->groupBy('cs_name')
                ->map(fn($group, $name) => [
                    'cs_name' => $name ?: 'UNASSIGNED',
                    'total' => $group->count(),
                ])
                ->values()
                ->all(),
            'statusSummary' => [
                'all' => $allOrderTrackings->count(),
                'pending' => $allOrderTrackings->where('status', 'Pending')->count(),
                'solved' => $allOrderTrackings->where('status', 'Solved')->count(),
                'whitelist' => $allOrderTrackings->where('status', 'Whitelist')->count(),
            ],
            'overview' => [
                'total' => $allOrderTrackings->count(),
                'pending' => $allOrderTrackings->where('status', 'Pending')->count(),
                'solved' => $allOrderTrackings->where('status', 'Solved')->count(),
                'whitelist' => $allOrderTrackings->where('status', 'Whitelist')->count(),
            ],
            'brandOptions' => $brandOptions,
            'csNameOptions' => $csNameOptions,
            'platformOptions' => $platformOptions,
            'sourceOptions' => $sourceOptions,
            'logisticsOptions' => $logisticsOptions,
            'categoryOptions' => $categoryOptions,
            'lastStepOptions' => $lastStepOptions,
            'reasonWhitelistOptions' => $reasonWhitelistOptions,
            'erpStatusOptions' => $erpStatusOptions,
            'reasonLateResponseOptions' => $reasonLateResponseOptions,
            'complaintSyncMap' => Complaint::query()
                ->whereNotNull('order_id')
                ->get(['order_id', 'sub_case', 'last_step', 'status', 'reason_whitelist', 'reason_late_respons'])
                ->filter(fn($c) => filled($c->order_id))
                ->keyBy('order_id')
                ->map(fn($c) => [
                    'category'           => $c->sub_case,
                    'last_step'          => $c->last_step,
                    'status'             => $c->status,
                    'reason_whitelist'   => $c->reason_whitelist,
                    'reason_late_respons' => $c->reason_late_respons,
                ])
                ->all(),
            'rgoOrderIds' => OrderTrackingRgoEntry::query()
                ->where('is_active', true)
                ->pluck('order_id')
                ->filter(fn($orderId) => filled($orderId))
                ->unique()
                ->values()
                ->all(),
            'jetTrackMap' => JetTrackEntry::query()
                ->where('is_active', true)
                ->get(['awb', 'kondisi_barang'])
                ->mapWithKeys(fn(JetTrackEntry $entry) => [
                    $entry->awb => [
                        'kondisi_barang' => $entry->kondisi_barang,
                    ],
                ])
                ->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge($this->coerceNullableFields($request->all()));
        $validated = $request->validate($this->rules(), $this->messages());
        $payload = $this->preparePayload($request, $validated);

        OrderTracking::create($payload);

        return redirect()->back()->with('success', 'Order Tracking berhasil disimpan.');
    }

    public function update(Request $request, OrderTracking $orderTracking): RedirectResponse
    {
        $request->merge($this->coerceNullableFields($request->all()));
        $validated = $request->validate($this->rules(), $this->messages());
        $payload = $this->preparePayload($request, $validated, $orderTracking);

        $orderTracking->update($payload);

        return redirect()->back()->with('success', 'Order Tracking berhasil diperbarui.');
    }

    public function destroy(OrderTracking $orderTracking): RedirectResponse
    {
        $orderTracking->delete();

        return redirect()->back()->with('success', 'Data tracking terhapus.');
    }

    private function transformOrderTracking(OrderTracking $orderTracking): array
    {
        return [
            'id' => $orderTracking->id,
            'source' => $orderTracking->data_source,
            'data_source' => $orderTracking->data_source,
            'tanggal_input' => $orderTracking->tanggal_input,
            'tanggal_order' => $orderTracking->tanggal_order,
            'brand' => $orderTracking->brand,
            'platform' => $orderTracking->platform,
            'order_id' => $orderTracking->order_id,
            'value' => $orderTracking->value !== null ? (float) $orderTracking->value : null,
            'logistics' => $orderTracking->logistics,
            'awb' => $orderTracking->awb,
            'erp_status' => $orderTracking->erp_status,
            'payment_method' => $orderTracking->payment_method,
            'wh_note' => $orderTracking->wh_note,
            'cs_name' => $orderTracking->cs_name,
            'category' => $orderTracking->category,
            'last_step' => $orderTracking->last_step,
            'update' => $orderTracking->update,
            'tanggal_update' => $orderTracking->tanggal_update,
            'value_receive' => $orderTracking->value_receive !== null ? (float) $orderTracking->value_receive : null,
            'insurance_info' => $orderTracking->insurance_info,
            'video_unboxing_wh' => $orderTracking->video_unboxing_wh,
            'video_unboxing_url' => $orderTracking->video_unboxing_wh
                ? Storage::url($orderTracking->video_unboxing_wh)
                : null,
            'bap_wh' => $orderTracking->bap_wh,
            'bap_url' => $orderTracking->bap_wh
                ? Storage::url($orderTracking->bap_wh)
                : null,
            'update_wh' => $orderTracking->update_wh,
            'update_finance' => $orderTracking->update_finance,
            'status' => $orderTracking->status,
            'month' => $orderTracking->month,
            'automation_track' => $orderTracking->automation_track,
            'kondisi_barang' => $orderTracking->kondisi_barang,
            'tanggal_tts' => $orderTracking->tanggal_tts,
            'reason_whitelist' => $orderTracking->reason_whitelist,
            'reason_late_respons' => $orderTracking->reason_late_respons,
            'created_at' => optional($orderTracking->created_at)?->toDateTimeString(),
            'updated_at' => optional($orderTracking->updated_at)?->toDateTimeString(),
        ];
    }

    private function rules(): array
    {
        $required = 'required';
        $sourceOptions = $this->sourceOptions();
        $brandOptions = $this->brandOptions();
        $platformOptions = $this->platformOptions();
        $logisticsOptions = $this->logisticsOptions();
        $csNameOptions = $this->csNameOptions();
        $categoryOptions = $this->categoryOptions();
        $lastStepNames = collect($this->lastStepOptions())
            ->pluck('value')
            ->filter()
            ->values()
            ->all();
        $erpStatusOptions = $this->erpStatusOptions();
        $reasonWhitelistOptions = $this->reasonWhitelistOptions();
        $reasonLateResponseOptions = $this->reasonLateResponseOptions();

        return [
            'data_source' => empty($sourceOptions)
                ? [$required, 'string', 'max:255']
                : [$required, 'string', Rule::in($sourceOptions)],
            'tanggal_input' => [$required, 'date'],
            'tanggal_order' => [$required, 'date'],
            'brand' => empty($brandOptions)
                ? [$required, 'string', 'max:255']
                : [$required, 'string', Rule::in($brandOptions)],
            'platform' => empty($platformOptions)
                ? [$required, 'string', 'max:255']
                : [$required, 'string', Rule::in($platformOptions)],
            'order_id' => [$required, 'string', 'max:255'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'logistics' => empty($logisticsOptions)
                ? [$required, 'string', 'max:255']
                : [$required, 'string', Rule::in($logisticsOptions)],
            'awb' => ['nullable', 'string', 'max:255'],
            'erp_status' => empty($erpStatusOptions)
                ? ['nullable', 'string', 'max:255']
                : ['nullable', 'string', Rule::in($erpStatusOptions)],
            'payment_method' => ['nullable', 'string', Rule::in(['COD', 'NON COD'])],
            'wh_note' => ['nullable', 'string'],
            'cs_name' => empty($csNameOptions)
                ? [$required, 'string', 'max:255']
                : [$required, 'string', Rule::in($csNameOptions)],
            'category' => empty($categoryOptions)
                ? [$required, 'string', 'max:255']
                : [$required, 'string', Rule::in($categoryOptions)],
            'last_step' => empty($lastStepNames)
                ? [$required, 'string', 'max:255']
                : [$required, 'string', Rule::in($lastStepNames)],
            'update' => ['nullable', 'string'],
            'tanggal_update' => [$required, 'date'],
            'value_receive' => ['nullable', 'numeric', 'min:0'],
            'insurance_info' => ['nullable', 'string', Rule::in(['Y', 'N'])],
            'update_wh' => ['nullable', 'string'],
            'update_finance' => ['nullable', 'string'],
            'reason_whitelist' => empty($reasonWhitelistOptions)
                ? ['required_if:last_step,Claim Reject', 'nullable', 'string', 'max:255']
                : ['required_if:last_step,Claim Reject', 'nullable', 'string', Rule::in($reasonWhitelistOptions)],
            'reason_late_respons' => empty($reasonLateResponseOptions)
                ? ['required_if:reason_whitelist,Late Respons', 'nullable', 'string', 'max:255']
                : ['required_if:reason_whitelist,Late Respons', 'nullable', 'string', Rule::in($reasonLateResponseOptions)],
            'video_unboxing' => ['nullable', 'file', 'mimes:mp4,mov,ogg,qt', 'max:20000'],
            'video_unboxing_wh' => ['nullable', 'file', 'mimes:mp4,mov,ogg,qt', 'max:20000'],
            'bap' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5000'],
            'bap_wh' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5000'],
        ];
    }

    private function messages(): array
    {
        return [
            'reason_whitelist.required_if' => 'Reason Whitelist wajib diisi jika Last Step = Claim Reject.',
            'reason_late_respons.required_if' => 'Reason Late Respons wajib diisi jika Whitelist = Late Respons.',
        ];
    }

    private function preparePayload(Request $request, array $validated, ?OrderTracking $orderTracking = null): array
    {
        $payload = collect($validated)
            ->except(['video_unboxing', 'video_unboxing_wh', 'bap', 'bap_wh'])
            ->toArray();

        if ($request->hasFile('video_unboxing')) {
            $payload['video_unboxing_wh'] = $request->file('video_unboxing')->store('trackings/videos', 'public');
        } elseif ($request->hasFile('video_unboxing_wh')) {
            $payload['video_unboxing_wh'] = $request->file('video_unboxing_wh')->store('trackings/videos', 'public');
        } elseif ($orderTracking) {
            $payload['video_unboxing_wh'] = $orderTracking->video_unboxing_wh;
        }

        if ($request->hasFile('bap')) {
            $payload['bap_wh'] = $request->file('bap')->store('trackings/bap', 'public');
        } elseif ($request->hasFile('bap_wh')) {
            $payload['bap_wh'] = $request->file('bap_wh')->store('trackings/bap', 'public');
        } elseif ($orderTracking) {
            $payload['bap_wh'] = $orderTracking->bap_wh;
        }

        // Derived fields are always controlled by model automation.
        unset(
            $payload['status'],
            $payload['month'],
            $payload['automation_track'],
            $payload['tanggal_tts']
        );

        return $payload;
    }

    private function coerceNullableFields(array $data): array
    {
        $nullable = [
            'erp_status', 'payment_method', 'insurance_info',
            'reason_whitelist', 'reason_late_respons',
            'awb', 'wh_note', 'update', 'update_wh', 'update_finance',
        ];

        foreach ($nullable as $field) {
            if (array_key_exists($field, $data) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }

    private function sourceOptions(): array
    {
        $masterOptions = OrderTrackingDataSource::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();

        if (!empty($masterOptions)) {
            return $masterOptions;
        }

        return ['WH', 'Finance', 'Reject Return'];
    }

    private function brandOptions(): array
    {
        return Brand::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    private function platformOptions(): array
    {
        return Platform::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    private function logisticsOptions(): array
    {
        return Logistic::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    private function csNameOptions(): array
    {
        return User::query()
            ->whereHas('roles', fn($query) => $query->where('name', 'CS'))
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    private function categoryOptions(): array
    {
        return SubCase::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    private function lastStepOptions(): array
    {
        return LastStep::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['name', 'status_result'])
            ->map(fn(LastStep $lastStep) => [
                'label' => $lastStep->name,
                'value' => $lastStep->name,
                'status_result' => $lastStep->status_result,
            ])
            ->all();
    }

    private function erpStatusOptions(): array
    {
        return OrderTrackingErpStatus::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    private function reasonWhitelistOptions(): array
    {
        return ReasonWhitelist::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    private function reasonLateResponseOptions(): array
    {
        return ReasonLateResponse::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }
}
