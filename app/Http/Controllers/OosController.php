<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Oos;
use App\Models\OosReason;
use App\Models\OosSolution;
use App\Models\Platform;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OosController extends Controller
{
    private const DEFAULT_REASONS  = ['Wrong Price', 'Stock Damage', 'OOS No Bugs', 'Bounce Back', 'Delay Inbound'];
    private const DEFAULT_SOLUTIONS = ['Cancel', 'Perpanjang Masa Garansi', 'Tawarkan Varian Lain'];
    private const UPDATE_CS_OPTIONS = ['Done Blast', 'Cancel'];

    public function index(Request $request): Response
    {
        $supportsAgentAssignment = $this->oosSupportsAgentAssignment();
        $brandOptions    = $this->brandOptions();
        $platformOptions = $this->platformOptions();
        $reasonOptions   = $this->reasonOptions();
        $solutionOptions = $this->solutionOptions();

        $query = Oos::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim((string) $request->input('search'));
                $q->where(function ($inner) use ($search) {
                    $inner->where('order_id', 'like', "%{$search}%")
                        ->orWhere('product_name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('platform', 'like', "%{$search}%")
                        ->orWhere('reason', 'like', "%{$search}%");
                });
            })
            ->when(
                $request->filled('brand') && $request->input('brand') !== 'All',
                fn($q) => $q->where('brand', $request->input('brand'))
            )
            ->when(
                $request->filled('platform') && $request->input('platform') !== 'All',
                fn($q) => $q->where('platform', $request->input('platform'))
            )
            ->when(
                $request->filled('reason') && $request->input('reason') !== 'All',
                fn($q) => $q->where('reason', $request->input('reason'))
            )
            ->when(
                $request->filled('update_cs') && $request->input('update_cs') !== 'All',
                fn($q) => $q->where('update_cs', $request->input('update_cs'))
            )
            ->latest('tanggal_input')
            ->latest('id');

        $all = (clone $query)->get();

        $oosData = (clone $query)
            ->paginate(10)
            ->withQueryString()
            ->through(fn(Oos $oos) => $this->transform($oos));

        return Inertia::render('Oos/Index', [
            'oosData'         => $oosData,
            'filters'         => [
                'search'    => $request->input('search'),
                'brand'     => $request->input('brand'),
                'platform'  => $request->input('platform'),
                'reason'    => $request->input('reason'),
                'update_cs' => $request->input('update_cs'),
            ],
            'brandOptions'    => $brandOptions,
            'platformOptions' => $platformOptions,
            'csNameOptions'   => $supportsAgentAssignment ? $this->csNameOptions() : [],
            'supportsAgentAssignment' => $supportsAgentAssignment,
            'reasonOptions'   => $reasonOptions,
            'solutionOptions' => $solutionOptions,
            'updateCsOptions' => self::UPDATE_CS_OPTIONS,
            'overview'        => [
                'total'      => $all->count(),
                'done_blast' => $all->where('update_cs', 'Done Blast')->count(),
                'cancel'     => $all->where('update_cs', 'Cancel')->count(),
                'pending'    => $all->whereNull('update_cs')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge($this->coerceNullable($request->all()));
        $validated = $request->validate($this->rules());
        if (!$this->oosSupportsAgentAssignment()) {
            unset($validated['cs_name']);
        }
        Oos::create($validated);

        return redirect()->back()->with('success', 'Data OOS berhasil disimpan.');
    }

    public function update(Request $request, Oos $oos): RedirectResponse
    {
        $request->merge($this->coerceNullable($request->all()));
        $validated = $request->validate($this->rules());
        if (!$this->oosSupportsAgentAssignment()) {
            unset($validated['cs_name']);
        }
        $oos->update($validated);

        return redirect()->back()->with('success', 'Data OOS berhasil diperbarui.');
    }

    public function destroy(Oos $oos): RedirectResponse
    {
        $oos->delete();

        return redirect()->back()->with('success', 'Data OOS terhapus.');
    }

    private function transform(Oos $oos): array
    {
        return [
            'id'                 => $oos->id,
            'tanggal_input'      => $oos->tanggal_input,
            'brand'              => $oos->brand,
            'platform'           => $oos->platform,
            'cs_name'            => $this->oosSupportsAgentAssignment() ? $oos->cs_name : null,
            'order_id'           => $oos->order_id,
            'product_name'       => $oos->product_name,
            'sku'                => $oos->sku,
            'reason'             => $oos->reason,
            'solusi'             => $oos->solusi,
            'note_detail_varian' => $oos->note_detail_varian,
            'update_cs'          => $oos->update_cs,
            'tanggal_blast'      => $oos->tanggal_blast,
            'feedback_customers' => $oos->feedback_customers,
            'month'              => $oos->month,
            'created_at'         => optional($oos->created_at)?->toDateTimeString(),
            'updated_at'         => optional($oos->updated_at)?->toDateTimeString(),
        ];
    }

    private function rules(): array
    {
        $brandOptions    = $this->brandOptions();
        $platformOptions = $this->platformOptions();
        $reasonOptions   = $this->reasonOptions();
        $solutionOptions = $this->solutionOptions();

        $rules = [
            'tanggal_input'      => ['required', 'date'],
            'brand'              => empty($brandOptions)
                ? ['required', 'string', 'max:255']
                : ['required', 'string', Rule::in($brandOptions)],
            'platform'           => empty($platformOptions)
                ? ['required', 'string', 'max:255']
                : ['required', 'string', Rule::in($platformOptions)],
            'order_id'           => ['required', 'string', 'max:255'],
            'product_name'       => ['nullable', 'string', 'max:255'],
            'sku'                => ['nullable', 'string', 'max:255'],
            'reason'             => empty($reasonOptions)
                ? ['nullable', 'string', 'max:255']
                : ['nullable', 'string', Rule::in($reasonOptions)],
            'solusi'             => empty($solutionOptions)
                ? ['nullable', 'string', 'max:255']
                : ['nullable', 'string', Rule::in($solutionOptions)],
            'note_detail_varian' => ['nullable', 'string'],
            'update_cs'          => ['nullable', 'string', Rule::in(self::UPDATE_CS_OPTIONS)],
            'tanggal_blast'      => ['nullable', 'date'],
            'feedback_customers' => ['nullable', 'string'],
        ];

        if ($this->oosSupportsAgentAssignment()) {
            $csNameOptions = $this->csNameOptions();
            $rules['cs_name'] = empty($csNameOptions)
                ? ['nullable', 'string', 'max:255']
                : ['nullable', 'string', Rule::in($csNameOptions)];
        }

        return $rules;
    }

    private function coerceNullable(array $data): array
    {
        $fields = [
            'reason', 'solusi', 'update_cs', 'product_name',
            'sku', 'note_detail_varian', 'feedback_customers', 'tanggal_blast',
        ];

        if ($this->oosSupportsAgentAssignment()) {
            $fields[] = 'cs_name';
        }

        foreach ($fields as $field) {
            if (array_key_exists($field, $data) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }

    private function brandOptions(): array
    {
        return Brand::query()->where('is_active', true)->orderBy('name')->pluck('name')->all();
    }

    private function platformOptions(): array
    {
        return Platform::query()->where('is_active', true)->orderBy('name')->pluck('name')->all();
    }

    private function csNameOptions(): array
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'CS'))
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    private function reasonOptions(): array
    {
        $master = OosReason::query()->where('is_active', true)->orderBy('name')->pluck('name')->all();

        return !empty($master) ? $master : self::DEFAULT_REASONS;
    }

    private function solutionOptions(): array
    {
        $master = OosSolution::query()->where('is_active', true)->orderBy('name')->pluck('name')->all();

        return !empty($master) ? $master : self::DEFAULT_SOLUTIONS;
    }

    private function oosSupportsAgentAssignment(): bool
    {
        return Schema::hasColumn('oos', 'cs_name');
    }
}
