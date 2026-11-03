<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use PDO;
use PDOException;
use Throwable;

class MenuController extends Controller
{
    /**
     * Pesan kesalahan validasi dalam Bahasa Indonesia.
     *
     * @var array<string, string>
     */
    private array $pesanValidasi = [
        'kode_menu.required' => 'Kode menu wajib diisi.',
        'kode_menu.size' => 'Kode menu harus tepat 10 karakter.',
        'kode_menu.alpha_num' => 'Kode menu hanya boleh berisi huruf dan angka.',
        'nama_menu.required' => 'Nama menu wajib diisi.',
        'nama_menu.min' => 'Nama menu minimal 3 karakter.',
        'nama_menu.max' => 'Nama menu maksimal 100 karakter.',
        'barcode.required' => 'Barcode wajib diisi.',
        'barcode.min' => 'Barcode minimal 5 karakter.',
        'barcode.max' => 'Barcode maksimal 30 karakter.',
        'harga.required' => 'Harga wajib diisi.',
        'harga.integer' => 'Harga harus berupa bilangan bulat.',
        'harga.min' => 'Harga minimal Rp 1.000.',
        'harga.max' => 'Harga maksimal Rp 100.000.',
        'kategori_menu_id.required' => 'Kategori menu wajib dipilih.',
        'kategori_menu_id.integer' => 'Kategori menu tidak valid.',
    ];

    /**
     * GET /menu - menampilkan daftar menu, form tambah, pencarian, dan filter.
     */
    public function index(Request $request): View
    {
        $kataKunci = strip_tags(trim((string) $request->query('q', '')));
        $kategoriFilter = trim((string) $request->query('kategori_menu_id', ''));

        if (! ctype_digit($kategoriFilter)) {
            $kategoriFilter = '';
        }

        try {
            $pdo = DB::connection()->getPdo();

            $sql = 'SELECT m.id, m.kode_menu, m.nama_menu, m.barcode, m.harga,
                           m.kategori_menu_id, k.nama_kategori
                    FROM menu AS m
                    JOIN kategori_menu AS k ON k.id = m.kategori_menu_id
                    WHERE 1 = 1';
            $parameter = [];

            if ($kataKunci !== '') {
                $sql .= ' AND (m.kode_menu LIKE :cari_kode
                          OR m.nama_menu LIKE :cari_nama
                          OR m.barcode LIKE :cari_barcode)';
                $nilaiPencarian = '%' . $kataKunci . '%';
                $parameter['cari_kode'] = $nilaiPencarian;
                $parameter['cari_nama'] = $nilaiPencarian;
                $parameter['cari_barcode'] = $nilaiPencarian;
            }

            if ($kategoriFilter !== '') {
                $sql .= ' AND m.kategori_menu_id = :kategori_menu_id';
                $parameter['kategori_menu_id'] = (int) $kategoriFilter;
            }

            $sql .= ' ORDER BY m.kode_menu';

            $ambilMenu = $pdo->prepare($sql);
            $ambilMenu->execute($parameter);
            $daftarMenu = $ambilMenu->fetchAll(PDO::FETCH_ASSOC);

            $daftarKategori = $this->ambilDaftarKategori($pdo);

            return view('menu.index', compact('daftarMenu', 'daftarKategori', 'kataKunci', 'kategoriFilter'));
        } catch (Throwable $error) {
            report($error);

            return view('menu.index', [
                'daftarMenu' => [],
                'daftarKategori' => [],
                'kataKunci' => $kataKunci,
                'kategoriFilter' => $kategoriFilter,
                'pesanError' => 'Data menu tidak dapat ditampilkan. Silakan coba beberapa saat lagi.',
            ]);
        }
    }

    /**
     * POST /menu - menyimpan data menu baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->bersihkanInput($request);

        $validator = Validator::make($data, $this->aturanValidasi(), $this->pesanValidasi);

        if ($validator->fails()) {
            return redirect()->route('menu.index')->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $data['harga'] = (int) $data['harga'];
        $data['kategori_menu_id'] = (int) $data['kategori_menu_id'];

        try {
            $pdo = DB::connection()->getPdo();

            if (! $this->kategoriTersedia($pdo, $data['kategori_menu_id'])) {
                return redirect()->route('menu.index')
                    ->withErrors(['kategori_menu_id' => 'Kategori menu yang dipilih tidak tersedia.'])
                    ->withInput();
            }

            $errorDuplikat = $this->cekDuplikat($pdo, $data['kode_menu'], $data['barcode']);

            if ($errorDuplikat !== []) {
                return redirect()->route('menu.index')
                    ->withErrors($errorDuplikat)
                    ->withInput()
                    ->with('gagal', 'Data menu gagal ditambahkan karena kode atau barcode sudah digunakan.');
            }

            $simpan = $pdo->prepare(
                'INSERT INTO menu (kode_menu, nama_menu, barcode, harga, kategori_menu_id, created_at, updated_at)
                 VALUES (:kode_menu, :nama_menu, :barcode, :harga, :kategori_menu_id, NOW(), NOW())'
            );
            $simpan->execute($data);

            return redirect()->route('menu.index')
                ->with('berhasil', 'Menu ' . $data['nama_menu'] . ' berhasil ditambahkan.');
        } catch (Throwable $error) {
            report($error);

            return redirect()->route('menu.index')
                ->withInput()
                ->with('gagal', $this->pesanAman($error, 'Data menu gagal disimpan. Silakan coba lagi.'));
        }
    }

    /**
     * GET /menu/{id}/edit - menampilkan form edit dengan data lama.
     */
    public function edit(int $id): View|RedirectResponse
    {
        try {
            $pdo = DB::connection()->getPdo();

            $ambilMenu = $pdo->prepare(
                'SELECT id, kode_menu, nama_menu, barcode, harga, kategori_menu_id
                 FROM menu
                 WHERE id = :id'
            );
            $ambilMenu->execute(['id' => $id]);
            $menu = $ambilMenu->fetch(PDO::FETCH_ASSOC);

            if (! $menu) {
                return redirect()->route('menu.index')->with('gagal', 'Data menu tidak ditemukan.');
            }

            $daftarKategori = $this->ambilDaftarKategori($pdo);

            return view('menu.edit', compact('menu', 'daftarKategori'));
        } catch (Throwable $error) {
            report($error);

            return redirect()->route('menu.index')->with('gagal', 'Data menu gagal dibuka untuk diubah.');
        }
    }

    /**
     * PUT /menu/{id} - memperbarui data menu.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $this->bersihkanInput($request);

        $validator = Validator::make($data, $this->aturanValidasi(), $this->pesanValidasi);

        if ($validator->fails()) {
            return redirect()->route('menu.edit', $id)->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $data['harga'] = (int) $data['harga'];
        $data['kategori_menu_id'] = (int) $data['kategori_menu_id'];

        try {
            $pdo = DB::connection()->getPdo();

            $cekMenu = $pdo->prepare('SELECT COUNT(*) FROM menu WHERE id = :id');
            $cekMenu->execute(['id' => $id]);

            if ((int) $cekMenu->fetchColumn() === 0) {
                return redirect()->route('menu.index')->with('gagal', 'Data menu tidak ditemukan.');
            }

            if (! $this->kategoriTersedia($pdo, $data['kategori_menu_id'])) {
                return redirect()->route('menu.edit', $id)
                    ->withErrors(['kategori_menu_id' => 'Kategori menu yang dipilih tidak tersedia.'])
                    ->withInput();
            }

            $errorDuplikat = $this->cekDuplikat($pdo, $data['kode_menu'], $data['barcode'], $id);

            if ($errorDuplikat !== []) {
                return redirect()->route('menu.edit', $id)
                    ->withErrors($errorDuplikat)
                    ->withInput()
                    ->with('gagal', 'Perubahan ditolak karena kode atau barcode sudah dipakai menu lain.');
            }

            $ubah = $pdo->prepare(
                'UPDATE menu
                 SET kode_menu = :kode_menu,
                     nama_menu = :nama_menu,
                     barcode = :barcode,
                     harga = :harga,
                     kategori_menu_id = :kategori_menu_id,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $ubah->execute([
                'kode_menu' => $data['kode_menu'],
                'nama_menu' => $data['nama_menu'],
                'barcode' => $data['barcode'],
                'harga' => $data['harga'],
                'kategori_menu_id' => $data['kategori_menu_id'],
                'id' => $id,
            ]);

            return redirect()->route('menu.index')
                ->with('berhasil', 'Menu ' . $data['nama_menu'] . ' berhasil diperbarui.');
        } catch (Throwable $error) {
            report($error);

            return redirect()->route('menu.edit', $id)
                ->withInput()
                ->with('gagal', $this->pesanAman($error, 'Data menu gagal diperbarui. Silakan coba lagi.'));
        }
    }

    /**
     * DELETE /menu/{id} - menghapus data menu.
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            $pdo = DB::connection()->getPdo();

            $hapus = $pdo->prepare('DELETE FROM menu WHERE id = :id');
            $hapus->execute(['id' => $id]);

            if ($hapus->rowCount() === 0) {
                return redirect()->route('menu.index')->with('gagal', 'Data menu tidak ditemukan.');
            }

            return redirect()->route('menu.index')->with('berhasil', 'Data menu berhasil dihapus.');
        } catch (Throwable $error) {
            report($error);

            return redirect()->route('menu.index')->with('gagal', 'Data menu gagal dihapus. Silakan coba lagi.');
        }
    }

    /**
     * Sanitasi input teks dengan trim dan strip_tags.
     *
     * @return array<string, string>
     */
    private function bersihkanInput(Request $request): array
    {
        return [
            'kode_menu' => strtoupper(strip_tags(trim((string) $request->input('kode_menu')))),
            'nama_menu' => strip_tags(trim((string) $request->input('nama_menu'))),
            'barcode' => strip_tags(trim((string) $request->input('barcode'))),
            'harga' => trim((string) $request->input('harga')),
            'kategori_menu_id' => trim((string) $request->input('kategori_menu_id')),
        ];
    }

    /**
     * Aturan validasi server-side untuk Create dan Update.
     *
     * @return array<string, array<int, string>>
     */
    private function aturanValidasi(): array
    {
        return [
            'kode_menu' => ['required', 'string', 'size:10', 'alpha_num:ascii'],
            'nama_menu' => ['required', 'string', 'min:3', 'max:100'],
            'barcode' => ['required', 'string', 'min:5', 'max:30'],
            'harga' => ['required', 'integer', 'min:1000', 'max:100000'],
            'kategori_menu_id' => ['required', 'integer'],
        ];
    }

    /**
     * Mengambil seluruh data tabel master kategori_menu.
     *
     * @return array<int, array<string, mixed>>
     */
    private function ambilDaftarKategori(PDO $pdo): array
    {
        $ambilKategori = $pdo->prepare('SELECT id, nama_kategori FROM kategori_menu ORDER BY nama_kategori');
        $ambilKategori->execute();

        return $ambilKategori->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Memastikan kategori yang dipilih tersedia pada tabel master.
     */
    private function kategoriTersedia(PDO $pdo, int $kategoriId): bool
    {
        $cekKategori = $pdo->prepare('SELECT COUNT(*) FROM kategori_menu WHERE id = :id');
        $cekKategori->execute(['id' => $kategoriId]);

        return (int) $cekKategori->fetchColumn() > 0;
    }

    /**
     * Memeriksa duplikasi kode_menu dan barcode.
     * Saat update, data milik menu itu sendiri dikecualikan.
     *
     * @return array<string, string>
     */
    private function cekDuplikat(PDO $pdo, string $kodeMenu, string $barcode, ?int $kecualiId = null): array
    {
        $errors = [];

        $sqlKode = 'SELECT COUNT(*) FROM menu WHERE kode_menu = :kode_menu';
        $sqlBarcode = 'SELECT COUNT(*) FROM menu WHERE barcode = :barcode';
        $parameterKode = ['kode_menu' => $kodeMenu];
        $parameterBarcode = ['barcode' => $barcode];

        if ($kecualiId !== null) {
            $sqlKode .= ' AND id <> :id';
            $sqlBarcode .= ' AND id <> :id';
            $parameterKode['id'] = $kecualiId;
            $parameterBarcode['id'] = $kecualiId;
        }

        $cekKode = $pdo->prepare($sqlKode);
        $cekKode->execute($parameterKode);

        if ((int) $cekKode->fetchColumn() > 0) {
            $errors['kode_menu'] = 'Kode menu ' . $kodeMenu . ' sudah digunakan.';
        }

        $cekBarcode = $pdo->prepare($sqlBarcode);
        $cekBarcode->execute($parameterBarcode);

        if ((int) $cekBarcode->fetchColumn() > 0) {
            $errors['barcode'] = 'Barcode ' . $barcode . ' sudah digunakan.';
        }

        return $errors;
    }

    /**
     * Menghasilkan pesan singkat yang aman untuk pengguna.
     * Pelanggaran constraint UNIQUE (SQLSTATE 23000) diberi pesan khusus.
     */
    private function pesanAman(Throwable $error, string $pesanBawaan): string
    {
        if ($error instanceof PDOException && $error->getCode() === '23000') {
            return 'Data ditolak karena melanggar aturan basis data (data duplikat atau relasi tidak valid).';
        }

        return $pesanBawaan;
    }
}
