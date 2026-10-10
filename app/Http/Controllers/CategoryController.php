<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    public function Category(Request $request)
    {
        $validation = $request->validate([
            'cari' => 'nullable|string',
        ]);

        $dataCategory = DB::table('categories');

        if ($request->filled('cari')) {

            $search = $validation['cari'];

            $dataCategory->where(function ($qry) use ($search) {

                $qry->where('name', 'like', "%{$search}%")
                    ->orwhere('code', 'like', "%{$search}%");
            });
        }

        $getDataCategory = $dataCategory->orderBy('name')->paginate(10)->withQueryString();

        return view('Category.CategoryIndex', [
            'judul' => 'Category',
            'dataCategory' => $getDataCategory,
        ]);
    }

    public function CategoryCreate()
    {

        return view('Category.CategoryCreate', [
            'judul' => 'Tambah Data',
        ]);
    }

    public function PostCategoryCreate(Request $request)
    {
        $validation = $request->validate([
            'code' => 'required|string|max:30|alpha_dash|lowercase|unique:categories,code',
            'name' => 'required|string|max:50',
        ], [
            'code.unique' => 'Maaf, code yang Anda masukkan sudah ada, silakan gunakan code lain.',
            'code.alpha_dash' => 'Code hanya boleh berisi huruf, angka, tanda - dan _ tanpa spasi.',
            'code.lowercase' => 'Code harus menggunakan huruf kecil.',
        ]);

        DB::beginTransaction();
        try {

            Category::create([
                'code' => $validation['code'],
                'name' => $validation['name'],
            ]);

            DB::commit();

            return back()->with('success', 'Tambah Data Berhasil');
        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error('Gagal menambah category', ['error' => $th->getMessage()]);

            return back()->withInput()->with('error', 'Terjadi kesalahan saat menyimpan data, silakan coba lagi.');
        }
    }

    public function CategoryEdit(Category $data)
    {
        return view('Category.CategoryEdit', [
            'judul' => 'Edit Category',
            'detailData' => $data,
        ]);
    }

    public function PostUpdateCategory(Request $request, Category $data)
    {

        $validation = $request->validate([
            'code' => 'required|string|max:30|alpha_dash|lowercase|unique:categories,code,'.$data->id,
            'name' => 'required|string|max:50',
        ], [
            'code.unique' => 'Maaf, code yang Anda masukkan sudah ada, silakan gunakan code lain.',
            'code.alpha_dash' => 'Code hanya boleh berisi huruf, angka, tanda - dan _ tanpa spasi.',
            'code.lowercase' => 'Code harus menggunakan huruf kecil.',
        ]);

        DB::beginTransaction();
        try {

            $data->update([
                'code' => $validation['code'],
                'name' => $validation['name'],
            ]);

            DB::commit();

            return redirect('/Category')->with('success', 'Edit Data Berhasil');
        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error('Gagal mengubah category', ['error' => $th->getMessage()]);

            return back()->withInput()->with('error', 'Terjadi kesalahan saat menyimpan data, silakan coba lagi.');
        }
    }

    public function CategoryDelete(Category $data)
    {

        if ($data->transactions()->exists()) {

            return back()->with('error', 'Maaf category tidak bisa dihapus,karena sudah ada data transaksi.');
        }

        DB::beginTransaction();
        try {

            $data->delete();

            DB::commit();

            return back()->with('success', 'Hapus Data Berhasil');
        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error('Gagal menghapus category', ['error' => $th->getMessage()]);

            return back()->with('error', 'Terjadi kesalahan saat menghapus data, silakan coba lagi.');
        }
    }
}
