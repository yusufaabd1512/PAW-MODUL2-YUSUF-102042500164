<?php
session_start();
require 'koneksi.php';

$stmt1 = $conn->prepare("SELECT COUNT(*) AS total FROM mata_kuliah");
$stmt1->execute();
$row1 = $stmt1->get_result()->fetch_assoc();
$total_mk = $row1['total'];

$stmt2 = $conn->prepare("SELECT COUNT(*) AS total FROM materi");
$stmt2->execute();
$row2 = $stmt2->get_result()->fetch_assoc();
$total_materi = $row2['total'];

$stmt3 = $conn->prepare("SELECT COUNT(*) AS total FROM tugas");
$stmt3->execute();
$row3 = $stmt3->get_result()->fetch_assoc();
$total_tugas = $row3['total'];

$stmt_mk = $conn->prepare("SELECT id, kode, nama FROM mata_kuliah ORDER BY kode ASC");
$stmt_mk->execute();
$daftar_mk = $stmt_mk->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Learning Management System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body><main>
    <div class="menu">
        <a href="index.php">Beranda</a> |
        <a href="mata_kuliah.php">Mata Kuliah</a> |
        <a href="materi.php">Materi</a> |
        <a href="tugas.php">Tugas</a>
    </div>

    <p class="label">PRAKTIKUM PAW / JURNAL 2</p>
    <h1>Learning Management System</h1>
    <p>Pengelolaan mata kuliah, materi pembelajaran, dan tugas.</p>

    <?php if (isset($_SESSION['pesan'])): ?>
        <p class="pesan"><?= htmlspecialchars($_SESSION['pesan'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php unset($_SESSION['pesan']); ?>
    <?php endif; ?>

    <div class="ringkasan-box">
        <div class="ringkasan-item">
            <div class="teks">Mata kuliah</div>
            <div class="angka"><?= $total_mk ?></div>
        </div>
        <div class="ringkasan-item">
            <div class="teks">Materi</div>
            <div class="angka"><?= $total_materi ?></div>
        </div>
        <div class="ringkasan-item">
            <div class="teks">Tugas</div>
            <div class="angka"><?= $total_tugas ?></div>
        </div>
    </div>

    <h2>Mata kuliah tersedia</h2>
    <ul>
        <?php while ($mk = $daftar_mk->fetch_assoc()): ?>
            <li>
                <?= htmlspecialchars($mk['kode'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($mk['nama'], ENT_QUOTES, 'UTF-8') ?>
            </li>
        <?php endwhile; ?>
    </ul>
</main></body>
</html>
