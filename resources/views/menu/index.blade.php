<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Menu Kantin</title>
</head>
<body>
    <h1>Sistem Menu Kantin</h1>
    <p>Mohd Ikhsan Sadillah - 2401020133 | Paket 2 Tipe E</p>
    <hr>

    {{-- Feedback operasi --}}
    @if (session('berhasil'))
        <p><strong>Berhasil:</strong> {{ session('berhasil') }}</p>
    @endif

    @if (session('gagal'))
        <p><strong>Gagal:</strong> {{ session('gagal') }}</p>
    @endif

    @isset($pesanError)
        <p><strong>Gagal:</strong> {{ $pesanError }}</p>
    @endisset

    @if ($errors->any())
        <p><strong>Periksa kembali input berikut:</strong></p>
        <ul>
            @foreach ($errors->all() as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
    @endif

    {{-- Form tambah --}}
    <h2>Tambah Menu</h2>
    <form action="{{ route('menu.store') }}" method="POST">
        @csrf
        <table>
            <tr>
                <td><label for="kode_menu">Kode Menu</label></td>
                <td>
                    <input type="text" id="kode_menu" name="kode_menu" value="{{ old('kode_menu') }}" placeholder="MN00000004">
                    @error('kode_menu') <br><small>{{ $message }}</small> @enderror
                </td>
            </tr>
            <tr>
                <td><label for="nama_menu">Nama Menu</label></td>
                <td>
                    <input type="text" id="nama_menu" name="nama_menu" value="{{ old('nama_menu') }}">
                    @error('nama_menu') <br><small>{{ $message }}</small> @enderror
                </td>
            </tr>
            <tr>
                <td><label for="barcode">Barcode</label></td>
                <td>
                    <input type="text" id="barcode" name="barcode" value="{{ old('barcode') }}" placeholder="BR-MKN-0003">
                    @error('barcode') <br><small>{{ $message }}</small> @enderror
                </td>
            </tr>
            <tr>
                <td><label for="harga">Harga</label></td>
                <td>
                    <input type="number" id="harga" name="harga" value="{{ old('harga') }}">
                    @error('harga') <br><small>{{ $message }}</small> @enderror
                </td>
            </tr>
            <tr>
                <td><label for="kategori_menu_id">Kategori</label></td>
                <td>
                    <select id="kategori_menu_id" name="kategori_menu_id">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach ($daftarKategori as $kategori)
                            <option value="{{ $kategori['id'] }}" @selected((string) old('kategori_menu_id') === (string) $kategori['id'])>
                                {{ $kategori['nama_kategori'] }}
                            </option>
                        @endforeach
                    </select>
                    @error('kategori_menu_id') <br><small>{{ $message }}</small> @enderror
                </td>
            </tr>
            <tr>
                <td></td>
                <td><button type="submit">Simpan</button></td>
            </tr>
        </table>
    </form>
    <hr>

    {{-- Form pencarian dan filter --}}
    <h2>Pencarian dan Filter</h2>
    <form action="{{ route('menu.index') }}" method="GET">
        <label for="q">Kata kunci</label>
        <input type="text" id="q" name="q" value="{{ $kataKunci }}" placeholder="Kode, nama, atau barcode">

        <label for="filter_kategori">Kategori</label>
        <select id="filter_kategori" name="kategori_menu_id">
            <option value="">Semua Kategori</option>
            @foreach ($daftarKategori as $kategori)
                <option value="{{ $kategori['id'] }}" @selected($kategoriFilter === (string) $kategori['id'])>
                    {{ $kategori['nama_kategori'] }}
                </option>
            @endforeach
        </select>

        <button type="submit">Cari</button>
        <a href="{{ route('menu.index') }}">Reset</a>
    </form>
    <hr>

    {{-- Tabel data --}}
    <h2>Daftar Menu</h2>
    <p>Jumlah data: {{ count($daftarMenu) }}</p>
    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Menu</th>
                <th>Nama Menu</th>
                <th>Barcode</th>
                <th>Harga</th>
                <th>Status Harga</th>
                <th>Kategori</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($daftarMenu as $menu)
                @php $status = statusHarga((int) $menu['harga']); @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $menu['kode_menu'] }}</td>
                    <td>{{ $menu['nama_menu'] }}</td>
                    <td>{{ $menu['barcode'] }}</td>
                    <td>{{ formatRupiah((int) $menu['harga']) }}</td>
                    <td>
                        @if ($status === 'Premium')
                            <strong>{{ $status }}</strong>
                        @else
                            {{ $status }}
                        @endif
                    </td>
                    <td>{{ $menu['nama_kategori'] }}</td>
                    <td>
                        <a href="{{ route('menu.edit', $menu['id']) }}">Edit</a>
                        <form action="{{ route('menu.destroy', $menu['id']) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Data menu tidak ditemukan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Ringkasan status harga menggunakan perulangan dan percabangan --}}
    @php
        $jumlahPremium = 0;
        $jumlahReguler = 0;
    @endphp
    @foreach ($daftarMenu as $menu)
        @if (statusHarga((int) $menu['harga']) === 'Premium')
            @php $jumlahPremium++; @endphp
        @else
            @php $jumlahReguler++; @endphp
        @endif
    @endforeach
    <p>Ringkasan status harga: Premium = {{ $jumlahPremium }}, Reguler = {{ $jumlahReguler }}</p>

    <h3>Tabel Master: kategori_menu</h3>
    <ul>
        @foreach ($daftarKategori as $kategori)
            <li>{{ $kategori['id'] }} - {{ $kategori['nama_kategori'] }}</li>
        @endforeach
    </ul>
</body>
</html>
