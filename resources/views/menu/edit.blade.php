<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Menu - Sistem Menu Kantin</title>
</head>
<body>
    <h1>Edit Menu</h1>
    <p><a href="{{ route('menu.index') }}">&laquo; Kembali ke daftar menu</a></p>
    <hr>

    @if (session('gagal'))
        <p><strong>Gagal:</strong> {{ session('gagal') }}</p>
    @endif

    @if ($errors->any())
        <p><strong>Periksa kembali input berikut:</strong></p>
        <ul>
            @foreach ($errors->all() as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('menu.update', $menu['id']) }}" method="POST">
        @csrf
        @method('PUT')
        <table>
            <tr>
                <td><label for="kode_menu">Kode Menu</label></td>
                <td>
                    <input type="text" id="kode_menu" name="kode_menu" value="{{ old('kode_menu', $menu['kode_menu']) }}">
                    @error('kode_menu') <br><small>{{ $message }}</small> @enderror
                </td>
            </tr>
            <tr>
                <td><label for="nama_menu">Nama Menu</label></td>
                <td>
                    <input type="text" id="nama_menu" name="nama_menu" value="{{ old('nama_menu', $menu['nama_menu']) }}">
                    @error('nama_menu') <br><small>{{ $message }}</small> @enderror
                </td>
            </tr>
            <tr>
                <td><label for="barcode">Barcode</label></td>
                <td>
                    <input type="text" id="barcode" name="barcode" value="{{ old('barcode', $menu['barcode']) }}">
                    @error('barcode') <br><small>{{ $message }}</small> @enderror
                </td>
            </tr>
            <tr>
                <td><label for="harga">Harga</label></td>
                <td>
                    <input type="number" id="harga" name="harga" value="{{ old('harga', $menu['harga']) }}">
                    @error('harga') <br><small>{{ $message }}</small> @enderror
                </td>
            </tr>
            <tr>
                <td><label for="kategori_menu_id">Kategori</label></td>
                <td>
                    <select id="kategori_menu_id" name="kategori_menu_id">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach ($daftarKategori as $kategori)
                            <option value="{{ $kategori['id'] }}" @selected((string) old('kategori_menu_id', $menu['kategori_menu_id']) === (string) $kategori['id'])>
                                {{ $kategori['nama_kategori'] }}
                            </option>
                        @endforeach
                    </select>
                    @error('kategori_menu_id') <br><small>{{ $message }}</small> @enderror
                </td>
            </tr>
            <tr>
                <td></td>
                <td><button type="submit">Simpan Perubahan</button></td>
            </tr>
        </table>
    </form>
</body>
</html>
