<?php

$conn = mysqli_connect('127.0.0.1', 'root', '', 'lms_paw');

if (!$conn) {
    die('Koneksi ke database gagal: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
