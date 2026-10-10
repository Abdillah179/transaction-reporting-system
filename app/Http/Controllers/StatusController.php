<?php

namespace App\Http\Controllers;

use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StatusController extends Controller
{
    public function StatusIndex(Request $request)
    {

        $validation = $request->validate([
            'cari' => 'nullable|string',
        ]);

        $getDataStatus = DB::table('statuses');

        if ($request->filled('cari')) {

            $search = $validation['cari'];

            $getDataStatus->where(function ($sts) use ($search) {

                $sts->where('name', 'like', "%{$search}%")
                    ->orwhere('code', 'like', "%{$search}%");
            });
        }

        $getStatusData = $getDataStatus->orderBy('name')->paginate(10)->withQueryString();

        return view('Status.StatusIndex', [
            'judul' => 'Data Status',
            'dataStatus' => $getStatusData,
        ]);
    }

    public function StatusCreate()
    {

        return view('Status.StatusCreate', [
            'judul' => 'Tambah Status',
        ]);
    }

    public function PostCreateStatus(Request $request)
    {

        $validation = $request->validate([
            'code' => 'required|string|max:20|alpha_dash|lowercase|unique:statuses,code',
            'name' => 'required|string|max:30',
        ], [
            'code.unique' => 'Maaf, code yang Anda masukkan sudah ada, silakan gunakan code lain.',
            'code.alpha_dash' => 'Code hanya boleh berisi huruf, angka, tanda - dan _ tanpa spasi.',
            'code.lowercase' => 'Code harus menggunakan huruf kecil.',
        ]);

        DB::beginTransaction();
        try {

            Status::create([
                'code' => $validation['code'],
                'name' => $validation['name'],
            ]);

            DB::commit();

            return back()->with('success', 'Tambah Data Berhasil');
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Gagal menambah status', ['error' => $th->getMessage()]);

            return back()->withInput()->with('error', 'Terjadi kesalahan saat menyimpan data, silakan coba lagi');
        }
    }

    public function StatusEdit(Status $data)
    {

        return view('Status.StatusEdit', [
            'judul' => 'Edit Status',
            'detailData' => $data,
        ]);
    }

    public function PostUpdateStatus(Request $request, Status $data)
    {

        $validation = $request->validate([
            'name' => 'required|string|max:30',
        ]);

        DB::beginTransaction();
        try {

            $data->update([
                'name' => $validation['name'],
            ]);

            DB::commit();

            return redirect('/Status')->with('success', 'Edit Data Berhasil');
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Gagal mengubah status', ['error' => $th->getMessage()]);

            return back()->withInput()->with('error', 'Terjadi kesalahan saat menyimpan data, silakan coba lagi');
        }
    }

    public function StatusDelete(Status $data)
    {

        if (in_array($data->code, Status::SYSTEM_CODES)) {

            return back()->with('error', 'Maaf Data Status Ini Tidak Dapat Di Hapus Karena Bawaan Default.');
        }

        if ($data->transactions()->exists()) {

            return back()->with('error', 'Maaf Data Status Ini Tidak Dapat Di Hapus Karena sudah ada data transaksi terhubung.');
        }

        DB::beginTransaction();
        try {

            $data->delete();

            DB::commit();

            return back()->with('success', 'Hapus Data Berhasil');
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Gagal menghapus status', ['error' => $th->getMessage()]);

            return back()->with('error', 'Terjadi kesalahan saat menghapus data, silakan coba lagi');
        }
    }
}
