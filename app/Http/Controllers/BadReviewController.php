<?php

namespace App\Http\Controllers;

use App\Models\BadReview;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BadReviewController extends Controller
{
    public function index()
    {
        return Inertia::render('BadReviews/Index', [
            'badReviews' => BadReview::latest()->paginate(10)
        ]);
    }

    public function store(Request $request)
    {
        // Simple validasi dasar
        $request->validate([
            'star' => 'nullable|integer|in:1,2,3',
            'progress' => 'nullable|string|in:Follow Up Customer,Auto Reply',
        ]);

        $data = collect($request->all())->except(['_token'])->toArray();
        BadReview::create($data);

        return redirect()->back()->with('success', 'Bad Review berhasil disimpan.');
    }

    public function update(Request $request, BadReview $badReview)
    {
        $data = collect($request->all())->except(['_token', '_method'])->toArray();
        $badReview->update($data);

        return redirect()->back()->with('success', 'Bad Review diupdate.');
    }

    public function destroy(BadReview $badReview)
    {
        $badReview->delete();
        return redirect()->back()->with('success', 'Data dihapus.');
    }
}
