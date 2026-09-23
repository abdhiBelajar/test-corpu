<?php

namespace App\Http\Controllers\Api\AdminBkpsdm;

use App\Http\Controllers\Controller;
use App\Models\KategoriKursus;
use App\Models\Pembelajaran;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KategoriKursusController extends Controller
{
    /**
     * Display a listing of categories with course counts.
     */
    public function index(Request $request)
    {
        $query = KategoriKursus::withCount(['pembelajaran' => function ($q) {
            $q->whereNull('deleted_at');
        }]);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_kategori', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        $kategori = $query->orderBy('nama_kategori', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar Kategori Kursus berhasil diambil',
            'data' => $kategori
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:255|unique:kategori_kursus,nama_kategori',
            'deskripsi' => 'nullable|string|max:1000',
        ], [
            'nama_kategori.required' => 'Nama kategori wajib diisi.',
            'nama_kategori.unique' => 'Nama kategori sudah terdaftar.',
            'nama_kategori.max' => 'Nama kategori maksimal 255 karakter.',
        ]);

        $kategori = KategoriKursus::create([
            'nama_kategori' => trim($validated['nama_kategori']),
            'deskripsi' => $validated['deskripsi'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori kursus berhasil ditambahkan.',
            'data' => $kategori
        ], 201);
    }

    /**
     * Display the specified category.
     */
    public function show($id)
    {
        $kategori = KategoriKursus::withCount(['pembelajaran' => function ($q) {
            $q->whereNull('deleted_at');
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $kategori
        ]);
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, $id)
    {
        $kategori = KategoriKursus::findOrFail($id);

        $validated = $request->validate([
            'nama_kategori' => [
                'required',
                'string',
                'max:255',
                Rule::unique('kategori_kursus', 'nama_kategori')->ignore($kategori->kategori_id, 'kategori_id')
            ],
            'deskripsi' => 'nullable|string|max:1000',
        ], [
            'nama_kategori.required' => 'Nama kategori wajib diisi.',
            'nama_kategori.unique' => 'Nama kategori sudah terdaftar.',
        ]);

        $kategori->update([
            'nama_kategori' => trim($validated['nama_kategori']),
            'deskripsi' => $validated['deskripsi'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori kursus berhasil diperbarui.',
            'data' => $kategori
        ]);
    }

    /**
     * Remove the specified category.
     */
    public function destroy($id)
    {
        $kategori = KategoriKursus::findOrFail($id);

        // Check if there are courses still using this category
        $courseCount = Pembelajaran::where('kategori_id', $kategori->kategori_id)->count();

        if ($courseCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Kategori \"{$kategori->nama_kategori}\" tidak dapat dihapus karena masih digunakan oleh {$courseCount} kursus. Silakan ubah kategori kursus terkait terlebih dahulu."
            ], 422);
        }

        $kategori->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori kursus berhasil dihapus.'
        ]);
    }
}
