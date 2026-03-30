<?php

namespace App\Http\Controllers;

use App\Models\OrderTracking;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderTrackingController extends Controller
{
    public function index()
    {
        return Inertia::render('OrderTrackings/Index', [
            'orderTrackings' => OrderTracking::latest()->paginate(10)
        ]);
    }

    public function store(Request $request)
    {
        // Menerapkan Conditional Logic sama dengan Complaint!
        $request->validate([
            'last_step' => 'nullable|string',
            'reason_whitelist' => 'required_if:last_step,Claim Reject',
            'reason_late_respons' => 'required_if:reason_whitelist,Late Respons',
            
            // File Uploads validations
            'video_unboxing_wh' => 'nullable|file|mimes:mp4,mov,ogg,qt|max:20000',
            'bap_wh' => 'nullable|image|mimes:jpg,jpeg,png|max:5000',
        ], [
            'reason_whitelist.required_if' => 'Reason Whitelist wajib diisi jika Last Step = Claim Reject.',
            'reason_late_respons.required_if' => 'Reason Late Respons wajib diisi jika Whitelist = Late Respons.'
        ]);

        $data = collect($request->all())->except(['_token', 'video_unboxing_wh', 'bap_wh'])->toArray();

        if ($request->hasFile('video_unboxing_wh')) {
            $data['video_unboxing_wh'] = $request->file('video_unboxing_wh')->store('trackings/videos', 'public');
        }
        if ($request->hasFile('bap_wh')) {
            $data['bap_wh'] = $request->file('bap_wh')->store('trackings/bap', 'public');
        }

        OrderTracking::create($data);

        return redirect()->back()->with('success', 'Order Tracking berhasil disimpan.');
    }

    public function update(Request $request, OrderTracking $orderTracking)
    {
        // Logical Update
        $data = collect($request->all())->except(['_token', '_method', 'video_unboxing_wh', 'bap_wh'])->toArray();
        $orderTracking->update($data);

        return redirect()->back()->with('success', 'Order Tracking berhasil diperbarui.');
    }

    public function destroy(OrderTracking $orderTracking)
    {
        $orderTracking->delete();
        return redirect()->back()->with('success', 'Data tracking terhapus.');
    }
}
