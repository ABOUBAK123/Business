<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Film;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FilmController extends Controller
{
    public function index(Request $request)
    {
        $films = Film::query()
            ->when($request->search, fn($q, $s) => $q->where('title', 'like', "%{$s}%")
                ->orWhere('genre', 'like', "%{$s}%"))
            ->when($request->status !== null && $request->status !== '', fn($q) =>
                $q->where('is_active', $request->status === '1'))
            ->latest()
            ->paginate(15);

        return view('admin.films.index', compact('films'));
    }

    public function create()
    {
        return view('admin.films.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'            => 'required|string|max:255',
            'original_title'   => 'nullable|string|max:255',
            'description'      => 'nullable|string',
            'poster'           => 'nullable|image|max:4096',
            'trailer_url'      => 'nullable|url|max:500',
            'hls_manifest_url' => 'nullable|string|max:1000',
            'genre'            => 'nullable|string|max:100',
            'duration'         => 'nullable|integer|min:1|max:999',
            'release_year'     => 'nullable|integer|min:1900|max:2100',
            'price'            => 'required|numeric|min:0',
            'currency'         => 'required|string|max:10',
            'drm_key'          => 'nullable|string|max:500',
            'is_active'        => 'boolean',
        ]);

        if ($request->hasFile('poster')) {
            $data['poster_path'] = $request->file('poster')->store('films/posters', 'public');
        }
        unset($data['poster']);
        $data['is_active'] = $request->boolean('is_active', true);

        Film::create($data);

        return redirect()->route('admin.films.index')
            ->with('success', 'Film ajouté avec succès.');
    }

    public function edit(Film $film)
    {
        return view('admin.films.edit', compact('film'));
    }

    public function update(Request $request, Film $film)
    {
        $data = $request->validate([
            'title'            => 'required|string|max:255',
            'original_title'   => 'nullable|string|max:255',
            'description'      => 'nullable|string',
            'poster'           => 'nullable|image|max:4096',
            'trailer_url'      => 'nullable|url|max:500',
            'hls_manifest_url' => 'nullable|string|max:1000',
            'genre'            => 'nullable|string|max:100',
            'duration'         => 'nullable|integer|min:1|max:999',
            'release_year'     => 'nullable|integer|min:1900|max:2100',
            'price'            => 'required|numeric|min:0',
            'currency'         => 'required|string|max:10',
            'drm_key'          => 'nullable|string|max:500',
            'is_active'        => 'boolean',
        ]);

        if ($request->hasFile('poster')) {
            if ($film->poster_path) {
                Storage::disk('public')->delete($film->poster_path);
            }
            $data['poster_path'] = $request->file('poster')->store('films/posters', 'public');
        }
        unset($data['poster']);
        $data['is_active'] = $request->boolean('is_active');

        $film->update($data);

        return redirect()->route('admin.films.index')
            ->with('success', 'Film mis à jour avec succès.');
    }

    public function destroy(Film $film)
    {
        if ($film->poster_path) {
            Storage::disk('public')->delete($film->poster_path);
        }
        $film->delete();

        return redirect()->route('admin.films.index')
            ->with('success', 'Film supprimé.');
    }

    public function toggleStatus(Film $film)
    {
        $film->update(['is_active' => !$film->is_active]);
        return back()->with('success', $film->is_active ? 'Film activé.' : 'Film désactivé.');
    }
}
