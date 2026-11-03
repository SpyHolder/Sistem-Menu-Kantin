# Sistem Menu Kantin – Studi Kasus Paket 2 Tipe E

Aplikasi CRUD menu kantin berbasis **Laravel 12**, **MySQL**, **PDO prepared statement**, dan **Blade** (HTML sederhana tanpa CSS/JavaScript).

| | |
|---|---|
| Nama | Mohd Ikhsan Sadillah |
| NIM | 2401020133 |
| Mata kuliah | Praktikum Pemrograman Web (INF11197) |
| Tipe soal | Paket 2 – Tipe E (Sistem Menu Kantin) |

## Kebutuhan

- PHP 8.2+, Composer
- MySQL 8 / MariaDB (contoh: Laragon)
- Git

## Instalasi

```bash
git clone <url-repository> menu_kantin_2401020133
cd menu_kantin_2401020133
composer install
cp .env.example .env        # Windows: copy .env.example .env
php artisan key:generate
```

Buat basis data dan data awal dengan menjalankan berkas SQL:

```bash
mysql -u root < database/sql/menu_kantin_2401020133.sql
```

Sesuaikan `DB_USERNAME` / `DB_PASSWORD` pada `.env` bila perlu, lalu jalankan:

```bash
php artisan serve
```

Buka http://127.0.0.1:8000/menu

> Sesi dan cache memakai driver `file`, sehingga basis data hanya berisi dua tabel sesuai soal.

## Rancangan Basis Data

- `kategori_menu` (master): `id` PK, `nama_kategori` VARCHAR(100) NOT NULL UNIQUE
- `menu` (utama): `id` PK, `kode_menu` CHAR(10) NOT NULL UNIQUE, `nama_menu` VARCHAR(100) NOT NULL,
  `barcode` VARCHAR(30) NOT NULL UNIQUE, `harga` INT UNSIGNED NOT NULL CHECK (1000–100000),
  `kategori_menu_id` FK → `kategori_menu(id)` ON UPDATE CASCADE ON DELETE RESTRICT, `created_at`, `updated_at`

Relasi: satu `kategori_menu` memiliki banyak `menu`.

## Daftar Route

| Method | URL | Nama | Fungsi |
|---|---|---|---|
| GET | `/menu` | `menu.index` | Daftar menu, form tambah, pencarian, filter, feedback |
| POST | `/menu` | `menu.store` | Menyimpan menu baru |
| GET | `/menu/{id}/edit` | `menu.edit` | Form edit dengan data lama |
| PUT | `/menu/{id}` | `menu.update` | Memperbarui menu (method spoofing) |
| DELETE | `/menu/{id}` | `menu.destroy` | Menghapus menu (method spoofing + CSRF) |

## Struktur Berkas Utama

```
app/Http/Controllers/MenuController.php   # CRUD, PDO prepared statement, try-catch + report()
app/Helpers/menu_helper.php               # statusHarga() dan formatRupiah()
resources/views/menu/index.blade.php      # Indeks: form tambah, cari, filter, tabel
resources/views/menu/edit.blade.php       # Form edit
routes/web.php                            # Lima route CRUD
database/sql/menu_kantin_2401020133.sql   # DDL + DML
```

## Keamanan

- Input teks disanitasi dengan `trim()` dan `strip_tags()`, lalu divalidasi di server.
- Semua query memakai `$pdo->prepare()` + `execute()` dengan parameter terikat.
- Kode menu dan barcode diperiksa duplikatnya sebelum disimpan atau diperbarui.
- Kesalahan basis data ditangkap `try-catch`, dicatat dengan `report()` ke `storage/logs/laravel.log`,
  dan pengguna hanya menerima pesan singkat yang aman.
- Form memakai `@csrf`; PUT dan DELETE memakai `@method`.
- File `.env` tidak masuk repository (lihat `.gitignore`).

## Skenario Pengujian

| No | Uji | Langkah | Hasil yang diharapkan |
|---|---|---|---|
| 1 | Read | Buka `/menu` | 3 data awal dan nama kategori tampil |
| 2 | Create valid | `MN00000004`, Ayam Bakar, `BR-MKN-0003`, 25000, Makanan | Data tersimpan, pesan berhasil, status Premium |
| 3 | Create tidak valid | `AB12`, `Es`, `B1`, 500, kategori kosong | Pesan kesalahan tampil, input lama dipertahankan |
| 4 | Duplikat | Kode `MN00000001` / barcode `BR-MKN-0001` | Ditolak, data tidak berubah |
| 5 | Update | Edit menu, ubah nama/harga | Data lama tampil di form, perubahan tersimpan |
| 6 | Konflik update | Ubah kode menjadi `MN00000002` milik menu lain | Ditolak |
| 7 | Delete | Klik Hapus | Data terhapus melalui form DELETE dengan CSRF |
| 8 | Pencarian | `q=teh`, `q=MN00000003`, `q=BR-MKN` | Mencari di kode, nama, barcode |
| 9 | Filter | Pilih kategori Minuman | Hanya menu Minuman tampil |
| 10 | Error handling | Matikan MySQL / ubah nama tabel sementara, buka `/menu` | Pesan aman tampil, detail tercatat di log |
