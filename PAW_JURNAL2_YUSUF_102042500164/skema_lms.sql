CREATE DATABASE IF NOT EXISTS lms_paw
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE lms_paw;

DROP TABLE IF EXISTS tugas;
DROP TABLE IF EXISTS materi;
DROP TABLE IF EXISTS mata_kuliah;

CREATE TABLE mata_kuliah (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    dosen VARCHAR(100) NOT NULL,
    semester TINYINT UNSIGNED NOT NULL
) ENGINE=InnoDB;

CREATE TABLE materi (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mata_kuliah_id INT UNSIGNED NOT NULL,
    judul VARCHAR(150) NOT NULL,
    isi TEXT NOT NULL,
    tautan VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (mata_kuliah_id)
        REFERENCES mata_kuliah(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE tugas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mata_kuliah_id INT UNSIGNED NOT NULL,
    judul VARCHAR(150) NOT NULL,
    instruksi TEXT NOT NULL,
    tenggat DATE NOT NULL,
    status VARCHAR(20) NOT NULL,
    FOREIGN KEY (mata_kuliah_id)
        REFERENCES mata_kuliah(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT INTO mata_kuliah (kode, nama, dosen, semester) VALUES
('ALP-101', 'Algoritma dan Pemrograman', 'Dosen ALP', 1),
('PAW-101', 'Pengembangan Aplikasi Website', 'Dosen PAW', 3),
('DBW-DEMO', 'Database Web', 'Dosen Praktikum', 3);

INSERT INTO materi (mata_kuliah_id, judul, isi, tautan) VALUES
(2, 'Dasar PHP', 'Pengenalan sintaks dasar PHP, variabel, array, dan kontrol logika untuk aplikasi web dinamis.', 'https://www.php.net/manual/en/tutorial.php'),
(3, 'Transaksi database', 'Materi membahas pengelolaan perubahan data pada database aplikasi website.', 'https://www.php.net/manual/en/book.mysqli.php');

INSERT INTO tugas (mata_kuliah_id, judul, instruksi, tenggat, status) VALUES
(2, 'Latihan CRUD', 'Kerjakan latihan membuat operasi CRUD dengan PHP dan MySQL sesuai panduan modul praktikum.', '2026-10-30', 'Aktif'),
(1, 'Logika Algoritma', 'Buatlah flowchart dan program penyelesaian masalah struktur percabangan dan perulangan.', '2026-11-05', 'Draf');
