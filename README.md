# Transaction Reporting System

Aplikasi laporan transaksi keuangan yang dibuat untuk mengolah data dalam jumlah besar. Saya menguji aplikasi ini dengan **5 juta transaksi**: data di-import dari file CSV, diringkas untuk dashboard, bisa difilter, lalu di-export kembali ke CSV atau Excel.

Fokus utama project ini bukan banyaknya fitur, tapi bagaimana setiap fitur tetap cepat dan hemat memori ketika datanya sudah jutaan baris. Semua angka performa di bawah adalah hasil pengukuran langsung di laptop saya, bukan perkiraan.

## Fitur

- Login dan logout
- Kelola data master **Category** dan **Status**
- **Import CSV** berukuran besar (sampai 1 GB) dengan upload bertahap, diproses di background menggunakan queue
- Halaman **progress import** yang ter-update otomatis (persentase, kecepatan, estimasi selesai) beserta daftar baris yang gagal dan alasannya
- **Dashboard**: total transaksi, total amount, rata-rata, success rate, grafik per hari dan per bulan, ringkasan per kategori dan status, dengan filter rentang tanggal
- **Daftar transaksi** dengan filter tanggal, kategori, status, rentang amount, dan nomor referensi
- **Export** hasil filter ke **CSV** dan **Excel**
- Command untuk membuat data uji sebanyak apa pun

## Tech Stack

| Bagian | Yang dipakai |
|---|---|
| Backend | Laravel 13 (PHP 8.3) |
| Database | MySQL 8 |
| Queue | Database driver |
| Tampilan | Blade, Bootstrap 5, Chart.js |
| Export Excel | OpenSpout |

Queue sengaja memakai driver database supaya aplikasi bisa dijalankan tanpa Redis atau service tambahan.

## Instalasi

### Kebutuhan

- PHP 8.3 dengan ekstensi `pdo_mysql`, `zip`, dan `xmlwriter`
- Composer
- MySQL 8

### Langkah

```bash
git clone https://github.com/Abdillah179/transaction-reporting-system.git
cd transaction-reporting-system

composer install
cp .env.example .env
php artisan key:generate
```

Buat database kosong bernama `db_reportingsystem` (atau sesuaikan `DB_DATABASE` di `.env`), lalu:

```bash
php artisan migrate --seed
```

### Menjalankan aplikasi

Aplikasi butuh web server dan queue worker yang berjalan bersamaan. Buka beberapa terminal:

```bash
php artisan serve
```

```bash
php artisan queue:work
```

Import diproses oleh queue worker. Tanpa worker, import akan tertahan di status *pending*. Untuk data jutaan baris, jalankan 3–4 worker di terminal terpisah supaya chunk diproses paralel.

### Akun demo

| Email | Password |
|---|---|
| demo1@example.com | password |

### Membuat data uji

```bash
php artisan transactions:generate 5000000 --invalid=100
```

File akan dibuat di `storage/app/samples/transactions_5000000.csv`. Opsi `--invalid` menyisipkan baris rusak secara merata (amount bukan angka, tanggal tidak valid, kategori tidak terdaftar, status kosong) untuk menguji validasi import.

File tersebut lalu di-upload lewat menu **Import → Upload CSV**.

### Pengaturan MySQL (disarankan untuk data jutaan baris)

Dengan konfigurasi bawaan MySQL, import 5 juta baris di laptop saya melambat drastis seiring tabel membesar (detailnya di bagian [Masalah yang Ditemui](#masalah-yang-ditemui-dan-solusinya)). Untuk pengujian, saya menambahkan ini di `my.ini` / `my.cnf` bagian `[mysqld]`:

```ini
innodb_buffer_pool_size = 2G
innodb_redo_log_capacity = 1G
innodb_flush_log_at_trx_commit = 2
disable_log_bin
```

`flush_log_at_trx_commit = 2` dan `disable_log_bin` hanya cocok untuk lingkungan lokal. Di production, binary log tetap dibutuhkan untuk replikasi dan backup.

### Command lain

```bash
php artisan summaries:rebuild
```

Menghitung ulang tabel ringkasan dashboard dari seluruh data transaksi. Biasanya tidak perlu dijalankan manual karena sudah otomatis berjalan setiap kali import selesai.

## Alur Import

```
Browser
  │  file dipotong per ±10 MB (file.slice), dikirim satu per satu
  ▼
POST /postimportchunk  (berulang)   → potongan disambung ke storage/app/private/imports/tmp/{id}.part
POST /postimportcomplete            → cek header CSV, simpan record imports, kirim PrepareImportJob
  ▼
PrepareImportJob
  │  membaca file sekali dari awal sampai akhir tanpa memproses isinya,
  │  mencatat posisi byte setiap 5.000 baris
  ▼
Bus::batch( ±1.000 × ImportChunkJob )
  │  setiap job: fseek ke posisi byte → baca 5.000 baris → validasi →
  │  dalam 1 transaksi database:
  │     - bulk insert baris valid ke transactions
  │     - bulk insert baris gagal ke import_errors
  │     - update progress (processed_rows, failed_rows)
  ▼
Batch selesai → status import completed/failed → RebuildDailySummaryJob
  ▼
Dashboard membaca daily_summaries
```

## Keputusan Teknis

### Import

**Upload bertahap (chunked upload).** Awalnya file di-upload dalam satu request. Untuk file 485 MB, prosesnya lambat, server development tidak bisa melayani request lain selama upload, dan upload gagal kalau batas `upload_max_filesize` di PHP kecil. Sekarang file dipotong di browser dan dikirim per potongan. Ukuran potongan menyesuaikan batas upload server (maksimal 10 MB). Browser mengirim posisi byte (`offset`) setiap potongan, dan server hanya menerima potongan yang posisinya sama dengan ukuran file sementara saat ini. Dengan begitu potongan yang di-retry tidak tersambung dua kali, dan upload yang terputus bisa dilanjutkan.

**Job hanya membawa posisi byte, bukan isi data.** Payload job disimpan di tabel `jobs`. Kalau setiap job membawa 5.000 baris data, tabel `jobs` akan membengkak ratusan MB. Job cukup membawa `offset` dan jumlah baris, lalu membaca langsung dari file dengan `fseek`.

**Ukuran chunk 5.000 baris.** Satu query insert 5.000 baris × 9 kolom = 45.000 placeholder, masih di bawah batas MySQL (65.535). Jumlah query insert untuk 5 juta baris menjadi ±1.000, bukan 5 juta.

**Validasi manual, bukan Laravel Validator.** Validator Laravel terlalu berat kalau dipanggil 5 juta kali. Validasi memakai `isset`, `is_numeric`, dan `DateTime::createFromFormat`. Data master (kategori dan status) diambil sekali per job dengan `pluck('id', 'code')`, jadi pengecekan per baris cukup lookup array tanpa query.

**Baris yang rusak tidak menghentikan import.** Baris gagal dicatat ke `import_errors` beserta nomor baris dan alasannya, lalu bisa dilihat di halaman detail import.

**Idempotent.** `reference_no` bersifat unik dan insert memakai `insertOrIgnore`, sehingga file yang sama di-import dua kali tidak membuat data dobel. Panjang setiap kolom tetap divalidasi lebih dulu, karena `insertOrIgnore` juga mengabaikan error lain seperti teks yang terlalu panjang.

**Aman di-retry.** Insert transaksi, insert error, dan update progress dijalankan dalam satu transaksi database. Kalau job gagal di tengah jalan lalu di-retry, tidak ada data atau progress yang tercatat dua kali. Progress di-update dengan `incrementEach` (`processed_rows = processed_rows + n`) karena beberapa worker bisa meng-update baris yang sama secara bersamaan.

**`allowFailures()` pada batch.** Satu chunk yang gagal tidak membatalkan chunk lain. Di akhir, import ditandai *failed* beserta jumlah chunk yang gagal, dan bisa diproses ulang dengan `php artisan queue:retry-batch {batch_id}`.

**`DB_QUEUE_RETRY_AFTER=900`.** Secara default queue database menganggap job yang berjalan lebih dari 90 detik sebagai macet dan menjalankannya lagi di worker lain. `PrepareImportJob` membaca file ratusan MB dan bisa lebih lama dari itu.

### Struktur database

- `amount` memakai `decimal(15,2)`, bukan float, supaya tidak ada pembulatan pada nilai uang.
- `category_id` dan `status_id` memakai `smallint` dan `tinyint`. Pada 5 juta baris, ukurannya jauh lebih kecil dibanding `bigint` atau string, dan index ikut mengecil.
- Index dibuat mengikuti pola filter: `(transaction_date)`, `(status_id, transaction_date)`, `(category_id, transaction_date)`, dan unique `reference_no`. Kolom yang difilter dengan `=` diletakkan di depan, kolom range dan urutan di belakang.
- `amount` sengaja tidak diberi index. Setiap index tambahan memperlambat import, dan filter amount tetap cepat karena MySQL membaca index tanggal secara berurutan lalu berhenti setelah mendapat 25 baris.
- `transactions.import_id` tidak diberi foreign key. Selama import berjalan, baris di tabel `imports` terus di-update untuk progress. Foreign key akan membuat setiap insert mengambil shared lock ke baris tersebut dan berisiko lock contention dengan banyak worker. Foreign key tetap dipasang ke `categories` dan `statuses` karena tabelnya kecil dan tidak pernah di-update saat import.
- Tabel `transactions` hanya punya `created_at`. Data transaksi tidak pernah diubah setelah masuk.

### Dashboard

Dashboard tidak membaca tabel `transactions` secara langsung. Setelah import selesai, `RebuildDailySummaryJob` menghitung ringkasan per **tanggal × kategori × status** ke tabel `daily_summaries` dengan satu query `INSERT ... SELECT ... GROUP BY`. Untuk 5 juta transaksi hasilnya hanya ±17.500 baris, sehingga semua card, grafik, dan tabel di dashboard cukup membaca tabel kecil ini.

Proses hitung ulang dijalankan dalam satu transaksi database, jadi selama proses berlangsung dashboard tetap menampilkan data ringkasan sebelumnya, bukan tabel kosong.

Nilai amount di dashboard hanya menghitung transaksi berstatus berhasil, karena transaksi *pending* dan *failed* bukan uang yang benar-benar masuk.

### Daftar transaksi

**Cursor pagination, bukan `paginate()`.** `paginate()` menjalankan `COUNT(*)` di setiap halaman dan memakai `OFFSET`. Di halaman yang jauh, MySQL harus membaca lalu membuang jutaan baris lebih dulu. Cursor pagination memakai posisi baris terakhir (`WHERE (transaction_date, id) < (...)`), sehingga index bisa langsung melompat ke posisi tersebut. Urutan memakai `transaction_date` lalu `id` supaya selalu unik. Kekurangannya, tidak ada nomor halaman, hanya tombol sebelumnya dan berikutnya.

**Jumlah total diambil dari `daily_summaries`.** Untuk filter tanggal, kategori, dan status, jumlah total dihitung dari tabel ringkasan. Untuk filter amount dan nomor referensi, jumlah total tidak ditampilkan karena tidak tersedia di ringkasan dan menghitungnya langsung akan memperlambat halaman.

**Pencarian nomor referensi berdasarkan awalan** (`LIKE 'TRX2610%'`) agar tetap bisa memakai index. Pencarian di tengah teks (`%...%`) akan membaca seluruh tabel.

### Export

- **CSV** dikirim dengan `response()->streamDownload()`. Data diambil per 5.000 baris dengan `lazyById()` dan langsung ditulis ke output, jadi memori tidak bertambah berapa pun jumlah barisnya. Kolom CSV sama dengan format import, sehingga file hasil export bisa di-import ulang.
- **Excel** dibuat dengan OpenSpout, yang menulis baris ke file sementara di disk, bukan ke memori. Library seperti PhpSpreadsheet membangun seluruh spreadsheet di memori dan tidak cocok untuk jutaan baris. Excel punya batas 1.048.576 baris per sheet, jadi data otomatis dipecah per 1.000.000 baris ke sheet berikutnya.
- File sementara OpenSpout diarahkan ke `storage/app/private/exports/tmp`. Awalnya memakai folder temp sistem, dan export Excel besar gagal karena drive C: penuh.
- `toBase()` dipakai agar setiap baris tidak diubah menjadi objek Eloquent, dan nama kategori/status diambil sekali dengan `pluck` di awal.
- Export dibatasi 5 kali per menit per user.

### Hak akses

- Riwayat dan detail import hanya bisa dibuka oleh user yang meng-upload. Akses ke ID milik user lain mengembalikan 403.
- Data transaksi bersifat bersama, sebagai laporan transaksi perusahaan, dan bisa dilihat semua user di dashboard, daftar transaksi, dan export.
- Data transaksi bersifat read-only. Transaksi hanya masuk lewat import dan tidak bisa diedit atau dihapus dari aplikasi, supaya laporan tetap konsisten dengan tabel ringkasan dan jejak datanya terjaga.
- Status bawaan (`success`, `pending`, `failed`) tidak bisa dihapus dan kodenya tidak bisa diubah, karena dipakai sebagai acuan perhitungan dashboard.

## Hasil Pengujian

Lingkungan pengujian: laptop Intel Core i5-5200U (2 core), RAM 12 GB, MySQL 8.0.30 dengan data di HDD, dijalankan dengan `php artisan serve`.

| Proses | Hasil |
|---|---|
| Generate CSV 5 juta baris | 41 detik, memori puncak 26 MB (sama dengan saat membuat 1.000 baris) |
| Upload file 485 MB | ±2 menit |
| Import 5 juta baris, MySQL konfigurasi bawaan | dimulai ±2.000 baris/detik, turun ke ±333 baris/detik di sekitar 2 juta baris |
| Import 5 juta baris, MySQL setelah tuning | **23 menit 21 detik**, rata-rata 3.570 baris/detik, 100 baris gagal sesuai data uji |
| Hitung ringkasan dashboard | ±17.500 baris ringkasan dari 5 juta transaksi, otomatis setelah import |
| Buka dashboard | 230–370 ms |
| Daftar transaksi dengan berbagai filter | 9–330 ms |
| Halaman di posisi ke-4.000.000, `OFFSET` | 15.416 ms |
| Halaman di posisi ke-4.000.000, cursor | **3 ms** |
| `COUNT(*)` seluruh tabel (yang dihindari) | 519 ms per request |
| Export CSV 5 juta baris | 33 detik, memori puncak 52 MB, file 499 MB |
| Export CSV/Excel 1 bulan (±212 ribu baris) | 31–35 detik saat cache MySQL masih kosong, memori 52–72 MB |
| Export Excel 5 juta baris | ±10 menit, file 201 MB, 6 sheet |

Hasil `EXPLAIN` untuk query daftar transaksi menunjukkan semua filter memakai index yang sesuai dan tidak ada yang memakai `Using filesort`. MySQL membaca index sesuai urutan (*backward index scan*) lalu berhenti setelah mendapat 26 baris.

## Masalah yang Ditemui dan Solusinya

**Upload file besar macet.** Upload 485 MB dalam satu request membuat seluruh aplikasi tidak bisa diakses selama proses berlangsung dan akhirnya gagal. Solusinya adalah upload bertahap seperti dijelaskan di atas.

**Error 429 di akhir upload.** Upload bertahap mengirim puluhan request ke `/postimportchunk`, lalu request terakhir ke `/postimportcomplete` ditolak dengan *Too Many Attempts*. Setelah saya telusuri ke `ThrottleRequests`, ternyata untuk user yang login, middleware `throttle:x,y` memakai ID user sebagai kunci tanpa membedakan route. Request chunk ikut menghabiskan kuota route complete. Solusinya memakai named rate limiter (`RateLimiter::for(...)`) sehingga setiap endpoint punya kuota sendiri.

**Import makin lama makin lambat.** Kecepatan import turun dari ±2.000 ke ±333 baris/detik. Menambah worker dari 1 menjadi 3 hampir tidak berpengaruh, yang berarti bottleneck-nya bukan di aplikasi. Penyebabnya, `innodb_buffer_pool_size` bawaan hanya 128 MB, sehingga index tabel `transactions` tidak muat di memori dan setiap insert harus membaca serta menulis halaman index secara acak ke HDD. Setelah buffer pool dinaikkan dan pengaturan lain di bagian instalasi diterapkan, kecepatan di awal import naik ±3 kali lipat (±6.500 baris/detik) dan tidak lagi anjlok seperti sebelumnya. Seluruh 5 juta baris selesai dalam 23 menit.

**Export Excel gagal karena disk penuh.** OpenSpout menulis file sementara ke folder temp sistem di drive C: yang sisa ruangnya kecil. Folder sementara dipindah ke `storage`.

**Export Excel memakan memori besar.** Export CSV 5 juta baris hanya memakai 52 MB, tapi export Excel dengan jumlah data yang sama mencapai 456 MB, mendekati batas `memory_limit` 512 MB. Saya cek terpisah, OpenSpout sendiri tetap di ±40 MB saat menulis ratusan ribu baris. Ternyata memori melonjak di langkah terakhir, ketika file .xlsx yang sudah jadi (201 MB) disalin ke `php://output`: file tersebut masuk ke output buffer dalam satu potongan besar. Dengan file 201 MB yang sama, cara ini memakai 412 MB, sedangkan `response()->download()` hanya 10 MB karena file dikirim per potongan kecil. Sekarang Excel ditulis ke file sementara di `storage`, dikirim dengan `response()->download()`, lalu dihapus otomatis setelah terkirim (`deleteFileAfterSend()`).

## Trade-off dan Batasan

- Daftar transaksi tidak punya nomor halaman karena memakai cursor pagination.
- Ringkasan dashboard dihitung ulang penuh setiap import selesai. Cara ini sederhana dan hasilnya selalu sama dengan isi tabel transaksi, tapi akan terlalu lama untuk ratusan juta baris.
- Export berjalan langsung di request web. Untuk 5 juta baris, satu request akan sibuk selama export berlangsung, dan dengan `php artisan serve` halaman lain harus menunggu.
- Urutan baris hasil export mengikuti `id` (urutan data masuk), bukan tanggal transaksi, karena `lazyById` membutuhkan urutan `id`.
- File CSV yang sudah di-upload tetap disimpan di `storage/app/private/imports`, dan file sementara dari upload yang tidak selesai belum dibersihkan otomatis.

## Pengembangan Selanjutnya

- Ringkasan inkremental: setiap chunk langsung menambahkan hasilnya ke `daily_summaries` dengan `INSERT ... ON DUPLICATE KEY UPDATE`, sehingga tidak perlu hitung ulang penuh.
- Export di background melalui queue, lalu user mendapat link download setelah file selesai dibuat.
- Partitioning tabel `transactions` per bulan atau tahun.
- Redis untuk queue dan cache, serta Laravel Horizon untuk mengatur worker.
- Scheduled command untuk menghapus file upload sementara yang tidak selesai dan file import lama.
- Role dan hak akses (misalnya admin melihat semua data, staf hanya data import miliknya).
- Pencatatan transaksi koreksi (reversal) sebagai pengganti edit dan hapus transaksi.
