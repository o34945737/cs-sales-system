<?php

namespace App\Http\Controllers;

use App\Models\Oos;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OosController extends Controller
{
    public function index()
    {
        return Inertia::render('Oos/Index', [
            'oosData' => Oos::latest()->paginate(10)
        ]);
    }

    public function store(Request $request)
    {
        // Simple Input Validation
        $request->validate([
            'tanggal_input' => 'nullable|date',
            'order_id' => 'nullable|string|max:100',
        ]);

        $data = collect($request->all())->except(['_token'])->toArray();
        Oos::create($data);

        return redirect()->back()->with('success', 'Data OOS berhasil disimpan.');
    }

    public function update(Request $request, Oos $oos)
    {
        $data = collect($request->all())->except(['_token', '_method'])->toArray();
        $oos->update($data);

        return redirect()->back()->with('success', 'OOS diperbarui.');
    }

    public function destroy(Oos $oos)
    {
        $oos->delete();
        return redirect()->back()->with('success', 'Data terhapus.');
    }
}
