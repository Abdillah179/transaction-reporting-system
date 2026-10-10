<?php

namespace App\Http\Controllers;

use App\Jobs\PrepareImportJob;
use App\Models\Import;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    public function ImportIndex(Request $request)
    {

        $validation = $request->validate([
            'cari' => 'nullable|string|max:255',
            'status' => 'nullable|in:'.implode(',', [Import::STATUS_PENDING, Import::STATUS_PROCESSING, Import::STATUS_COMPLETED, Import::STATUS_FAILED]),
        ]);

        $dataImport = Import::with('user')->where('user_id', auth()->user()->id)->latest('id');

        if ($request->filled('cari')) {

            $search = $validation['cari'];

            $dataImport->where(function ($imprt) use ($search) {

                $imprt->where('file_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {

            $dataImport->where('status', $validation['status']);
        }

        $importData = $dataImport->paginate(10)->withQueryString();

        return view('Import.ImportIndex', [
            'judul' => 'Data Import',
            'dataImport' => $importData,
        ]);
    }

    public function ImportCreate()
    {
        return view('Import.ImportCreate', [
            'judul' => 'Upload CSV',
            'csvHeader' => Import::CSV_HEADER,
            'uploadId' => (string) Str::uuid7(),
            'chunkSize' => min(10 * 1024 * 1024, (int) (UploadedFile::getMaxFilesize() * 0.9)),
        ]);
    }

    public function PostImportChunk(Request $request)
    {
        $validation = $request->validate([
            'upload_id' => 'required|uuid',
            'offset' => 'required|integer|min:0',
            'chunk' => 'required|file|max:10240',
        ]);

        $partPath = Storage::disk('local')->path('imports/tmp/'.$validation['upload_id'].'.part');
        File::ensureDirectoryExists(dirname($partPath));

        $currentSize = File::exists($partPath) ? filesize($partPath) : 0;
        $chunk = $validation['chunk'];

        if ($validation['offset'] < $currentSize) {

            return response()->json(['uploaded' => $currentSize]);
        }

        if ($validation['offset'] > $currentSize) {

            return response()->json(['message' => 'Urutan potongan file tidak sesuai, silakan upload ulang.'], 409);
        }

        if ($currentSize + $chunk->getSize() > 1024 * 1024 * 1024) {

            return response()->json(['message' => 'Ukuran file maksimal 1 GB.'], 422);
        }

        $source = fopen($chunk->getRealPath(), 'rb');
        $target = fopen($partPath, 'ab');
        stream_copy_to_stream($source, $target);
        fclose($source);
        fclose($target);

        return response()->json(['uploaded' => $currentSize + $chunk->getSize()]);
    }

    public function PostImportComplete(Request $request)
    {
        $validation = $request->validate([
            'upload_id' => 'required|uuid',
            'file_name' => ['required', 'string', 'max:255', 'regex:/\.csv$/i'],
        ], [
            'file_name.regex' => 'File harus berformat CSV.',
        ]);

        $partPath = 'imports/tmp/'.$validation['upload_id'].'.part';

        if (! Storage::disk('local')->exists($partPath)) {

            return response()->json(['message' => 'File upload tidak ditemukan, silakan upload ulang.'], 404);
        }

        if (! Import::hasValidHeader(Storage::disk('local')->path($partPath))) {

            Storage::disk('local')->delete($partPath);

            return response()->json(['message' => 'Format header CSV tidak sesuai. Header yang benar: '.implode(',', Import::CSV_HEADER)], 422);
        }

        try {

            $path = 'imports/'.$validation['upload_id'].'.csv';
            Storage::disk('local')->move($partPath, $path);

            $import = Import::create([
                'user_id' => auth()->id(),
                'file_name' => $validation['file_name'],
                'file_path' => $path,
                'status' => Import::STATUS_PENDING,
            ]);

            PrepareImportJob::dispatch($import);

            session()->flash('success', 'File berhasil diupload, import sedang diproses.');

            return response()->json(['redirect' => '/Import/detail/'.$import->id]);
        } catch (\Throwable $th) {

            Log::error('Gagal menyelesaikan upload import', ['error' => $th->getMessage()]);

            return response()->json(['message' => 'Terjadi kesalahan saat menyimpan file, silakan coba lagi.'], 500);
        }
    }

    public function ImportDetail(Import $data)
    {
        if ($data->user_id !== auth()->user()->id) {

            abort(403);
        }

        $data->load('user');

        $dataError = $data->importErrors()->orderBy('row_number')->paginate(20);

        return view('Import.ImportDetail', [
            'judul' => 'Detail Import',
            'detailData' => $data,
            'progress' => $data->progressData(),
            'dataError' => $dataError,
        ]);
    }

    public function ImportProgress(Import $data)
    {
        if ($data->user_id !== auth()->user()->id) {

            abort(403);
        }

        return response()->json($data->progressData());
    }
}
