<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $query = Complaint::query();

        // 1. Search Logic
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhere('resi', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        // 2. Filter by Status
        if ($request->has('status') && $request->status !== 'All') {
            $query->where('status', $request->status);
        }

        // 3. Sorting (Default: Latest)
        $sortField = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        return Inertia::render('Complaints/Index', [
            'complaints' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['search', 'status', 'sort', 'order']),
            // Tambahan data summary untuk sidebar kiri (Grouping by CS)
            'cs_summary' => Complaint::select('cs_name', \DB::raw('count(*) as total'))
                                ->groupBy('cs_name')
                                ->get()
        ]);
    }

    public function store(Request $request)
    {
        // Validation with Conditional Logic
        $request->validate([
            'step_cs_selesai' => 'nullable|in:YES,NO',
            'last_step' => 'nullable|string',

            // Conditional validation rules:
            'tanggal_step_cs_selesai' => 'required_if:step_cs_selesai,YES',
            'reason_whitelist' => 'required_if:last_step,Claim Reject',
            'reason_late_respons' => 'required_if:reason_whitelist,Late Respons',
            
            // File validation handling
            'video_unboxing' => 'nullable|file|mimes:mp4,mov,ogg,qt|max:20000', // max 20MB as sample
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5000',
        ], [
            // Custom Messages
            'tanggal_step_cs_selesai.required_if' => 'Tanggal harus diisi jika Step CS Selesai = YES.',
            'reason_whitelist.required_if' => 'Reason Whitelist wajib diisi jika Last Step = Claim Reject.',
            'reason_late_respons.required_if' => 'Reason Late Respons wajib diisi jika Whitelist = Late Respons.'
        ]);

        $data = collect($request->all())->except(['_token', 'video_unboxing', 'proof'])->toArray();

        // Handle File Uploads ke folder storage/app/public/
        if ($request->hasFile('video_unboxing')) {
            $data['video_unboxing'] = $request->file('video_unboxing')->store('complaints/videos', 'public');
        }
        if ($request->hasFile('proof')) {
            $data['proof'] = $request->file('proof')->store('complaints/proofs', 'public');
        }

        Complaint::create($data);

        return redirect()->back()->with('success', 'Data Complaint berhasil disimpan.');
    }

    public function update(Request $request, Complaint $complaint)
    {
        // Validasi conditional serupa untuk versi Update
        $request->validate([
            'step_cs_selesai' => 'nullable|in:YES,NO',
            'last_step' => 'nullable|string',
            'tanggal_step_cs_selesai' => 'required_if:step_cs_selesai,YES',
            'reason_whitelist' => 'required_if:last_step,Claim Reject',
            'reason_late_respons' => 'required_if:reason_whitelist,Late Respons',
        ]);
        
        // Handling update data bisa diletakkan di sini
        $data = collect($request->all())->except(['_token', '_method'])->toArray();
        $complaint->update($data);

        return redirect()->back()->with('success', 'Data Complaint berhasil diupdate.');
    }

    public function destroy(Complaint $complaint)
    {
        $complaint->delete();
        return redirect()->back()->with('success', 'Data Complaint dihapus.');
    }
}
