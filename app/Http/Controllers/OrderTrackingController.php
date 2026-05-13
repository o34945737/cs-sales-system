<?php

namespace App\Http\Controllers;

use App\Exports\OrderTrackingExport;
use App\Exports\OrderTrackingErpTemplateExport;
use App\Exports\OrderTrackingRgoTemplateExport;
use App\Exports\OrderTrackingTemplateExport;
use App\Imports\OrderTrackingErpImport;
use App\Imports\OrderTrackingImport;
use App\Imports\OrderTrackingRgoImport;
use App\Models\Brand;
use App\Models\Complaint;
use App\Models\JetTrackEntry;
use App\Models\LastStep;
use App\Models\OrderTracking;
use App\Models\OrderTrackingDataSource;
use App\Models\OrderTrackingErpStatus;
use App\Models\Platform;
use App\Models\ReasonLateResponse;
use App\Models\ReasonWhitelist;
use App\Models\SubCase;
use App\Services\GoogleSheetsRgoService;
use App\Services\OrderTrackingAutomationService;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class OrderTrackingController extends Controller
{
    public function index(Request $request): Response
    {
        $rgoService = app(GoogleSheetsRgoService::class);
        if ($rgoService->isConfigured() && !Cache::has('rgo_last_sync')) {
            try {
                $rgoService->sync();
            } catch (\Throwable) {
                // don't break page load on sync failure
            }
        }

        $sourceOptions = $this->sourceOptions();
        $brandOptions = $this->brandOptions();
        $platformOptions = $this->platformOptions();
        $erpStatusOptions = $this->erpStatusOptions();
        $csNameOptions = $this->csNameOptions();
        $categoryOptions = $this->categoryOptions();
        $causeByOptions = $this->causeByOptions();
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
                        ->orWhere('cause_by', 'like', "%{$search}%")
                        ->orWhere('awb', 'like', "%{$search}%")
                        ->orWhere('erp_status', 'like', "%{$search}%")
                        ->when(
                            $matches = $this->erpStatusEquivalentValues($search, true),
                            fn($query) => $query->orWhereIn('erp_status', $matches)
                        )
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
            'platformTtsDaysMap' => Platform::query()
                ->where('is_active', true)
                ->whereNotNull('tts_days')
                ->pluck('tts_days', 'name')
                ->all(),
            'sourceOptions' => $sourceOptions,
            'categoryOptions' => $categoryOptions,
            'causeByOptions' => $causeByOptions,
            'lastStepOptions' => $lastStepOptions,
            'reasonWhitelistOptions' => $reasonWhitelistOptions,
            'erpStatusOptions' => $erpStatusOptions,
            'reasonLateResponseOptions' => $reasonLateResponseOptions,
            'autoCauseByMap' => SubCase::query()
                ->where('is_active', true)
                ->whereNotNull('default_cause_by')
                ->orderBy('name')
                ->get(['name', 'default_cause_by'])
                ->mapWithKeys(fn(SubCase $subCase) => [$subCase->name => $subCase->default_cause_by])
                ->all(),
            'complaintSyncMap' => Complaint::query()
                ->whereNotNull('order_id')
                ->get(['order_id', 'sub_case', 'last_step', 'status', 'reason_whitelist', 'reason_late_respons'])
                ->filter(fn($c) => filled($c->order_id))
                ->mapWithKeys(fn($c) => [
                    Str::lower(trim((string) $c->order_id)) => [
                        'category'           => $c->sub_case,
                        'last_step'          => $c->last_step,
                        'status'             => $c->status,
                        'reason_whitelist'   => $c->reason_whitelist,
                        'reason_late_respons' => $c->reason_late_respons,
                    ],
                ])
                ->all(),
            'rgoOrderIds' => OrderTracking::query()
                ->whereNotNull('rgo_status')
                ->whereNotNull('order_id')
                ->pluck('order_id')
                ->unique()
                ->values()
                ->all(),
            'rgoLastSynced' => $rgoService->lastSyncedAt(),
            'jetTrackMap' => JetTrackEntry::query()
                ->where('is_active', true)
                ->get(['awb', 'kondisi_barang', 'video_url', 'warehouse_doc_link'])
                ->mapWithKeys(fn(JetTrackEntry $entry) => [
                    $entry->awb => [
                        'kondisi_barang' => $entry->kondisi_barang,
                        'video_url' => $entry->video_url,
                        'warehouse_doc_link' => $entry->warehouse_doc_link,
                    ],
                ])
                ->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge($this->coerceNullableFields($request->all()));
        $request->merge($this->complaintSyncFields($request->input('order_id')));
        $validated = $request->validate($this->rules(), $this->messages());
        $payload = $this->preparePayload($request, $validated);

        OrderTracking::create($payload);

        return redirect()->back()->with('success', 'Order Tracking berhasil disimpan.');
    }

    public function update(Request $request, OrderTracking $orderTracking): RedirectResponse
    {
        $request->merge($this->coerceNullableFields($request->all()));
        $request->merge($this->complaintSyncFields($request->input('order_id')));
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
            'cause_by' => $orderTracking->cause_by,
            'awb' => $orderTracking->awb,
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
            'rgo_status' => $orderTracking->rgo_status,
            'rgo_synced_at' => optional($orderTracking->rgo_synced_at)?->toDateTimeString(),
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
        $csNameOptions = $this->csNameOptions();
        $categoryOptions = $this->categoryOptions();
        $causeByOptions = $this->causeByOptions();
        $lastStepNames = collect($this->lastStepOptions())
            ->pluck('value')
            ->filter()
            ->values()
            ->all();
        $erpStatusValues = $this->erpStatusValidationValues();
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
            'cause_by' => empty($causeByOptions)
                ? [$required, 'string', 'max:255']
                : [$required, 'string', Rule::in($causeByOptions)],
            'awb' => ['nullable', 'string', 'max:255'],
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
            $payload['tanggal_tts'],
            $payload['erp_status']  // erp_status will default to 'Done' via automation
        );

        return $payload;
    }

    private function complaintSyncFields(?string $orderId): array
    {
        if (!filled($orderId)) {
            return [];
        }

        $complaint = Complaint::query()
            ->where('order_id', trim($orderId))
            ->first(['sub_case', 'last_step', 'reason_whitelist', 'reason_late_respons']);

        if (!$complaint) {
            return [];
        }

        return array_filter([
            'category' => $complaint->sub_case,
            'last_step' => $complaint->last_step,
            'reason_whitelist' => $complaint->reason_whitelist,
            'reason_late_respons' => $complaint->reason_late_respons,
        ], fn($value) => filled($value));
    }

    private function coerceNullableFields(array $data): array
    {
        $nullable = [
            'payment_method',
            'insurance_info',
            'reason_whitelist',
            'reason_late_respons',
            'awb',
            'wh_note',
            'update',
            'update_wh',
            'update_finance',
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
        $master = Cache::remember(
            'options.ot_data_sources',
            300,
            fn() =>
            OrderTrackingDataSource::query()->where('is_active', true)->orderBy('name')->pluck('name')->all()
        );

        return !empty($master) ? $master : ['WH', 'Finance', 'Reject Return'];
    }

    private function brandOptions(): array
    {
        return Cache::remember(
            'options.brands',
            300,
            fn() =>
            Brand::query()->where('is_active', true)->orderBy('name')->pluck('name')->all()
        );
    }

    private function platformOptions(): array
    {
        return Cache::remember(
            'options.platforms',
            300,
            fn() =>
            Platform::query()->where('is_active', true)->orderBy('name')->pluck('name')->all()
        );
    }

    private function csNameOptions(): array
    {
        return Cache::remember(
            'options.cs_names',
            300,
            fn() =>
            User::query()->whereHas('roles', fn($q) => $q->where('name', 'CS'))
                ->where('is_active', true)->orderBy('name')->pluck('name')->all()
        );
    }

    private function categoryOptions(): array
    {
        return Cache::remember(
            'options.sub_cases',
            300,
            fn() =>
            SubCase::query()->where('is_active', true)->orderBy('name')->pluck('name')->all()
        );
    }

    private function causeByOptions(): array
    {
        return Cache::remember(
            'options.cause_bys',
            300,
            fn() =>
            \App\Models\CauseBy::query()->where('is_active', true)->orderBy('name')->pluck('name')->all()
        );
    }

    private function lastStepOptions(): array
    {
        return Cache::remember(
            'options.last_steps_ot',
            300,
            fn() =>
            LastStep::query()->where('is_active', true)->orderBy('name')
                ->get(['name', 'status_result'])
                ->map(fn(LastStep $ls) => [
                    'label' => $ls->name,
                    'value' => $ls->name,
                    'status_result' => $ls->status_result,
                ])->all()
        );
    }

    private function erpStatusOptions(): array
    {
        return OrderTrackingErpStatus::query()->where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn(OrderTrackingErpStatus $status) => [
                'id' => (string) $status->id,
                'name' => $status->name,
            ])
            ->all();
    }

    private function erpStatusValidationValues(): array
    {
        return collect($this->erpStatusOptions())
            ->flatMap(fn(array $status) => [$status['id'], $status['name']])
            ->filter(fn($value) => filled($value))
            ->unique()
            ->values()
            ->all();
    }

    private function resolveErpStatus(?string $value): array
    {
        if (!filled($value)) {
            return ['id' => null, 'name' => null];
        }

        $value = trim((string) $value);

        foreach ($this->erpStatusOptions() as $status) {
            if ($status['id'] === $value || strcasecmp($status['name'], $value) === 0) {
                return $status;
            }
        }

        return ['id' => null, 'name' => null];
    }

    private function normalizeErpStatusValue(string $value): string
    {
        $status = $this->resolveErpStatus($value);

        return $status['name'] ?? $value;
    }

    private function erpStatusDisplayLabel(?string $value): ?string
    {
        if (!filled($value)) {
            return null;
        }

        $value = trim((string) $value);

        if (!is_numeric($value)) {
            foreach ($this->erpStatusOptions() as $status) {
                if (strcasecmp($status['name'], $value) === 0) {
                    return $status['name'];
                }
            }

            return $value;
        }

        return $this->resolveErpStatus($value)['name'] ?? $value;
    }

    private function erpStatusEquivalentValues(string $value, bool $search = false): array
    {
        if (!filled($value)) {
            return [];
        }

        $value = trim($value);

        return collect($this->erpStatusOptions())
            ->filter(function (array $status) use ($value, $search) {
                if ($search) {
                    return str_contains(strtolower($status['name']), strtolower($value))
                        || str_contains($status['id'], $value);
                }

                return $status['id'] === $value || strcasecmp($status['name'], $value) === 0;
            })
            ->flatMap(fn(array $status) => [$status['id'], $status['name']])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function reasonWhitelistOptions(): array
    {
        return Cache::remember(
            'options.reason_whitelists',
            300,
            fn() =>
            ReasonWhitelist::query()->where('is_active', true)->orderBy('name')->pluck('name')->all()
        );
    }

    public function downloadTemplate()
    {
        return Excel::download(new OrderTrackingTemplateExport(), 'order-tracking-import-template.xlsx');
    }

    public function downloadErpStatusTemplate(Request $request)
    {
        return Excel::download(
            new OrderTrackingErpTemplateExport(),
            'erp-status-import-template.xlsx'
        );
    }

    public function downloadRgoTemplate()
    {
        return Excel::download(new OrderTrackingRgoTemplateExport(), 'order-tracking-rgo-template.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ]);

        $batchId  = 'OT-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
        $importer = new OrderTrackingImport($batchId, auth()->id());

        try {
            Excel::import($importer, $request->file('file'));
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'Import gagal diproses. Pastikan file sesuai template dan coba lagi.');
        }

        return back()->with('import_result', $importer->results);
    }

    public function importErpStatus(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ]);

        $batchId = 'OT-ERP-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
        $importer = new OrderTrackingErpImport($batchId, auth()->id());

        try {
            Excel::import($importer, $request->file('file'));
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'Import ERP Status gagal diproses. Pastikan file berisi kolom order_id.');
        }

        // Recompute status, automation_track, dll. untuk semua order_id yang ter-update
        if (!empty($importer->importedOrderIds)) {
            app(\App\Services\OrderTrackingAutomationService::class)
                ->recomputeByOrderIds(array_unique($importer->importedOrderIds));
        }

        return back()
            ->with('success', 'Import ERP Status selesai diproses.')
            ->with('erp_import_result', $importer->results);
    }

    public function importRgo(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ]);

        $batchId = 'OT-RGO-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
        $importer = new OrderTrackingRgoImport($batchId, auth()->id());

        try {
            Excel::import($importer, $request->file('file'));

            if (!empty($importer->importedOrderIds)) {
                app(OrderTrackingAutomationService::class)->recomputeByOrderIds($importer->importedOrderIds);
            }
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'Import RGO gagal diproses. Pastikan file berisi order_id, notes, dan is_active.');
        }

        return back()
            ->with('success', 'Import RGO selesai diproses.')
            ->with('rgo_import_result', $importer->results);
    }

    public function syncRgo(): RedirectResponse
    {
        $service = app(GoogleSheetsRgoService::class);

        if (!$service->isConfigured()) {
            return back()->with('error', 'Sync RGO belum dikonfigurasi. Pastikan RGO_SHEET_ID dan GOOGLE_SERVICE_ENABLED sudah diset.');
        }

        try {
            $results = $service->sync();
        } catch (\Throwable $exception) {
            report($exception);
            return back()->with('error', 'Sync RGO gagal: ' . $exception->getMessage());
        }

        return back()
            ->with('success', "Sync RGO selesai. {$results['updated']} data diperbarui, {$results['skipped']} dilewati.")
            ->with('rgo_sync_result', $results);
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:order_trackings,id'],
        ]);

        try {
            OrderTracking::whereIn('id', $request->input('ids', []))->delete();
        } catch (\Throwable $exception) {
            report($exception);
            return redirect()->back()->with('error', 'Gagal menghapus beberapa data. Silakan coba lagi.');
        }

        return redirect()->back()->with('success', 'Semua data yang dipilih berhasil dihapus.');
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $query = (clone OrderTracking::query())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($trackingQuery) use ($search) {
                    $trackingQuery
                        ->where('data_source', 'like', "%{$search}%")
                        ->orWhere('order_id', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('platform', 'like', "%{$search}%")
                        ->orWhere('cause_by', 'like', "%{$search}%")
                        ->orWhere('awb', 'like', "%{$search}%")
                        ->orWhere('erp_status', 'like', "%{$search}%")
                        ->when(
                            $matches = $this->erpStatusEquivalentValues($search, true),
                            fn($query) => $query->orWhereIn('erp_status', $matches)
                        )
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

        return Excel::download(new OrderTrackingExport($query), 'order-tracking-export.xlsx');
    }

    private function reasonLateResponseOptions(): array
    {
        return Cache::remember(
            'options.reason_late_responses',
            300,
            fn() =>
            ReasonLateResponse::query()->where('is_active', true)->orderBy('name')->pluck('name')->all()
        );
    }
}
