<?php

if (! function_exists('statusHarga')) {
    /**
     * Menentukan status harga menu kantin.
     * Harga >= 20000 berstatus Premium, di bawahnya berstatus Reguler.
     */
    function statusHarga(int $harga): string
    {
        if ($harga >= 20000) {
            return 'Premium';
        }

        return 'Reguler';
    }
}

if (! function_exists('formatRupiah')) {
    /**
     * Menampilkan angka harga dalam format Rupiah sederhana.
     */
    function formatRupiah(int $angka): string
    {
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }
}
