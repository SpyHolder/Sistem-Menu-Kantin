-- =====================================================================
-- Studi Kasus Praktikum Pemrograman Web - Paket 2 Tipe E
-- Sistem Menu Kantin
-- Nama : Mohd Ikhsan Sadillah
-- NIM  : 2401020133
-- =====================================================================

-- ---------------------------------------------------------------------
-- DDL: Basis data
-- ---------------------------------------------------------------------
DROP DATABASE IF EXISTS menu_kantin_2401020133;
CREATE DATABASE menu_kantin_2401020133
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE menu_kantin_2401020133;

-- ---------------------------------------------------------------------
-- DDL: Tabel master kategori_menu
-- ---------------------------------------------------------------------
CREATE TABLE kategori_menu (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    CONSTRAINT uq_kategori_menu_nama UNIQUE (nama_kategori)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- DDL: Tabel utama menu (relasi banyak ke satu kategori_menu)
-- ---------------------------------------------------------------------
CREATE TABLE menu (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_menu CHAR(10) NOT NULL,
    nama_menu VARCHAR(100) NOT NULL,
    barcode VARCHAR(30) NOT NULL,
    harga INT UNSIGNED NOT NULL,
    kategori_menu_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_menu_kode_menu UNIQUE (kode_menu),
    CONSTRAINT uq_menu_barcode UNIQUE (barcode),
    CONSTRAINT ck_menu_harga CHECK (harga BETWEEN 1000 AND 100000),
    CONSTRAINT fk_menu_kategori_menu
        FOREIGN KEY (kategori_menu_id) REFERENCES kategori_menu (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- DML: Data awal
-- ---------------------------------------------------------------------
INSERT INTO kategori_menu (nama_kategori) VALUES
    ('Makanan'),
    ('Minuman');

INSERT INTO menu (kode_menu, nama_menu, barcode, harga, kategori_menu_id) VALUES
    ('MN00000001', 'Nasi Goreng', 'BR-MKN-0001', 18000,
        (SELECT id FROM kategori_menu WHERE nama_kategori = 'Makanan')),
    ('MN00000002', 'Es Teh', 'BR-MNM-0001', 5000,
        (SELECT id FROM kategori_menu WHERE nama_kategori = 'Minuman')),
    ('MN00000003', 'Mie Rebus', 'BR-MKN-0002', 15000,
        (SELECT id FROM kategori_menu WHERE nama_kategori = 'Makanan'));

-- ---------------------------------------------------------------------
-- Pemeriksaan
-- ---------------------------------------------------------------------
SELECT m.kode_menu, m.nama_menu, m.barcode, m.harga, k.nama_kategori
FROM menu AS m
JOIN kategori_menu AS k ON k.id = m.kategori_menu_id
ORDER BY m.kode_menu;
