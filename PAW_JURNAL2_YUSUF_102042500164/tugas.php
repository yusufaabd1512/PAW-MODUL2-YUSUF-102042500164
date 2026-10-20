<?php
session_start();
require 'koneksi.php';

if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

$aksi = $_GET['aksi'] ?? 'daftar';
$pesan = '';

if ($aksi === 'tambah') {
    $hasil_mk = mysqli_query($conn, "SELECT id, kode, nama FROM mata_kuliah ORDER BY kode ASC");
    $mata_kuliah_id = (int) ($_GET['mata_kuliah_id'] ?? 0);
    $judul = '';
    $instruksi = '';
    $tenggat = '';
    $status = 'Draf';

    if (isset($_POST['simpan'])) {
        if (!isset($_POST['token']) || !hash_equals($_SESSION['token'], $_POST['token'])) {
            $pesan = 'ERROR J05 — Token keamanan tidak valid atau telah kedaluwarsa.';
        } else {
            $mata_kuliah_id = (int) ($_POST['mata_kuliah_id'] ?? 0);
            $judul = trim($_POST['judul'] ?? '');
            $instruksi = trim($_POST['instruksi'] ?? '');
            $tenggat = trim($_POST['tenggat'] ?? '');
            $status = trim($_POST['status'] ?? '');

            $cek_tanggal = DateTime::createFromFormat('Y-m-d', $tenggat);
            $tanggal_valid = ($cek_tanggal && $cek_tanggal->format('Y-m-d') === $tenggat);

            if ($mata_kuliah_id <= 0 || $judul === '' || $instruksi === '' || $tenggat === '' || $status === '') {
                $pesan = 'Semua kolom wajib diisi.';
            } elseif (strlen($judul) < 3 || strlen($judul) > 150) {
                $pesan = 'Judul tugas harus 3-150 karakter.';
            } elseif (strlen($instruksi) < 3 || strlen($instruksi) > 2000) {
                $pesan = 'Instruksi tugas harus 3-2000 karakter.';
            } elseif (!$tanggal_valid) {
                $pesan = 'Pilih tanggal tenggat yang valid (format: YYYY-MM-DD).';
            } elseif (!in_array($status, ['Draf', 'Aktif', 'Selesai'])) {
                $pesan = 'Status hanya boleh Draf, Aktif, atau Selesai.';
            } else {
                $stmt_cek = $conn->prepare("SELECT id FROM mata_kuliah WHERE id = ?");
                $stmt_cek->bind_param("i", $mata_kuliah_id);
                $stmt_cek->execute();
                if ($stmt_cek->get_result()->num_rows === 0) {
                    $pesan = 'Mata kuliah yang dipilih tidak ditemukan.';
                } else {
                    $stmt_ins = $conn->prepare("INSERT INTO tugas (mata_kuliah_id, judul, instruksi, tenggat, status) VALUES (?, ?, ?, ?, ?)");
                    $stmt_ins->bind_param("issss", $mata_kuliah_id, $judul, $instruksi, $tenggat, $status);
                    $stmt_ins->execute();

                    $_SESSION['pesan'] = 'Tugas "' . $judul . '" berhasil ditambahkan.';
                    header('Location: tugas.php');
                    exit;
                }
            }
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Tambah Tugas - LMS EAD</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body><main>
        <div class="menu">
            <a href="index.php">Beranda</a> |
            <a href="mata_kuliah.php">Mata Kuliah</a> |
            <a href="materi.php">Materi</a> |
            <a href="tugas.php">Tugas</a>
        </div>

        <p class="label">LMS EAD / TAMBAH TUGAS</p>
        <h1>Tambah tugas</h1>
        <p><a href="tugas.php">Kembali ke daftar</a></p>

        <?php if ($pesan != ''): ?>
            <div class="kesalahan"><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="tugas.php?aksi=tambah">
            <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['token'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="mata_kuliah_id">Mata kuliah</label>
            <select id="mata_kuliah_id" name="mata_kuliah_id" required>
                <option value="">Pilih Mata kuliah</option>
                <?php while ($mk = mysqli_fetch_assoc($hasil_mk)): ?>
                    <option value="<?= $mk['id'] ?>" <?= ($mata_kuliah_id == $mk['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($mk['kode'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($mk['nama'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="judul">Judul tugas</label>
            <input id="judul" name="judul" maxlength="150" value="<?= htmlspecialchars($judul, ENT_QUOTES, 'UTF-8') ?>" placeholder="Contoh: Latihan CRUD" required>

            <label for="instruksi">Instruksi tugas</label>
            <textarea id="instruksi" name="instruksi" rows="6" placeholder="Petunjuk pengerjaan..." required><?= htmlspecialchars($instruksi, ENT_QUOTES, 'UTF-8') ?></textarea>

            <label for="tenggat">Tenggat</label>
            <input id="tenggat" name="tenggat" type="date" value="<?= htmlspecialchars($tenggat, ENT_QUOTES, 'UTF-8') ?>" required>

            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="Draf" <?= ($status === 'Draf') ? 'selected' : '' ?>>Draf</option>
                <option value="Aktif" <?= ($status === 'Aktif') ? 'selected' : '' ?>>Aktif</option>
                <option value="Selesai" <?= ($status === 'Selesai') ? 'selected' : '' ?>>Selesai</option>
            </select>

            <button name="simpan">Simpan data</button>
            <a href="tugas.php">Batal</a>
        </form>
    </main></body>
    </html>
    <?php
    exit;
}

if ($aksi === 'edit') {
    $id = (int) ($_GET['id'] ?? ($_POST['id'] ?? 0));
    $stmt = $conn->prepare("SELECT * FROM tugas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $tugas = $stmt->get_result()->fetch_assoc();

    if (!$tugas) {
        exit('Tugas tidak ditemukan. Kembali ke daftar.');
    }

    $hasil_mk = mysqli_query($conn, "SELECT id, kode, nama FROM mata_kuliah ORDER BY kode ASC");

    $mata_kuliah_id = $tugas['mata_kuliah_id'];
    $judul = $tugas['judul'];
    $instruksi = $tugas['instruksi'];
    $tenggat = $tugas['tenggat'];
    $status = $tugas['status'];

    if (isset($_POST['simpan'])) {
        if (!isset($_POST['token']) || !hash_equals($_SESSION['token'], $_POST['token'])) {
            $pesan = 'ERROR J05 — Token keamanan tidak valid atau telah kedaluwarsa.';
        } else {
            $new_mk_id = (int) ($_POST['mata_kuliah_id'] ?? 0);
            $new_judul = trim($_POST['judul'] ?? '');
            $new_instruksi = trim($_POST['instruksi'] ?? '');
            $new_tenggat = trim($_POST['tenggat'] ?? '');
            $new_status = trim($_POST['status'] ?? '');

            if ($new_mk_id === (int)$tugas['mata_kuliah_id'] && $new_judul === $tugas['judul'] && $new_instruksi === $tugas['instruksi'] && $new_tenggat === $tugas['tenggat'] && $new_status === $tugas['status']) {
                $_SESSION['pesan'] = 'Tidak ada perubahan data.';
                header('Location: tugas.php');
                exit;
            }

            $cek_tanggal = DateTime::createFromFormat('Y-m-d', $new_tenggat);
            $tanggal_valid = ($cek_tanggal && $cek_tanggal->format('Y-m-d') === $new_tenggat);

            if ($new_mk_id <= 0 || $new_judul === '' || $new_instruksi === '' || $new_tenggat === '' || $new_status === '') {
                $pesan = 'Semua kolom wajib diisi.';
            } elseif (strlen($new_judul) < 3 || strlen($new_judul) > 150) {
                $pesan = 'Judul tugas harus 3-150 karakter.';
            } elseif (strlen($new_instruksi) < 3 || strlen($new_instruksi) > 2000) {
                $pesan = 'Instruksi tugas harus 3-2000 karakter.';
            } elseif (!$tanggal_valid) {
                $pesan = 'Pilih tanggal tenggat yang valid (format: YYYY-MM-DD).';
            } elseif (!in_array($new_status, ['Draf', 'Aktif', 'Selesai'])) {
                $pesan = 'Status hanya boleh Draf, Aktif, atau Selesai.';
            } else {
                $stmt_up = $conn->prepare("UPDATE tugas SET mata_kuliah_id = ?, judul = ?, instruksi = ?, tenggat = ?, status = ? WHERE id = ?");
                $stmt_up->bind_param("issssi", $new_mk_id, $new_judul, $new_instruksi, $new_tenggat, $new_status, $id);
                $stmt_up->execute();

                $_SESSION['pesan'] = 'Data tugas "' . $new_judul . '" berhasil diperbarui.';
                header('Location: tugas.php');
                exit;
            }

            $mata_kuliah_id = $new_mk_id;
            $judul = $new_judul;
            $instruksi = $new_instruksi;
            $tenggat = $new_tenggat;
            $status = $new_status;
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Edit Tugas - LMS EAD</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body><main>
        <div class="menu">
            <a href="index.php">Beranda</a> |
            <a href="mata_kuliah.php">Mata Kuliah</a> |
            <a href="materi.php">Materi</a> |
            <a href="tugas.php">Tugas</a>
        </div>

        <p class="label">LMS EAD / EDIT TUGAS</p>
        <h1>Edit tugas</h1>
        <p><a href="tugas.php">Kembali ke daftar</a></p>

        <?php if ($pesan != ''): ?>
            <div class="kesalahan"><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="tugas.php?aksi=edit&id=<?= $id ?>">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['token'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="mata_kuliah_id">Mata kuliah</label>
            <select id="mata_kuliah_id" name="mata_kuliah_id" required>
                <option value="">Pilih Mata kuliah</option>
                <?php while ($mk = mysqli_fetch_assoc($hasil_mk)): ?>
                    <option value="<?= $mk['id'] ?>" <?= ($mata_kuliah_id == $mk['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($mk['kode'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($mk['nama'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="judul">Judul tugas</label>
            <input id="judul" name="judul" maxlength="150" value="<?= htmlspecialchars($judul, ENT_QUOTES, 'UTF-8') ?>" required>

            <label for="instruksi">Instruksi tugas</label>
            <textarea id="instruksi" name="instruksi" rows="6" required><?= htmlspecialchars($instruksi, ENT_QUOTES, 'UTF-8') ?></textarea>

            <label for="tenggat">Tenggat</label>
            <input id="tenggat" name="tenggat" type="date" value="<?= htmlspecialchars($tenggat, ENT_QUOTES, 'UTF-8') ?>" required>

            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="Draf" <?= ($status === 'Draf') ? 'selected' : '' ?>>Draf</option>
                <option value="Aktif" <?= ($status === 'Aktif') ? 'selected' : '' ?>>Aktif</option>
                <option value="Selesai" <?= ($status === 'Selesai') ? 'selected' : '' ?>>Selesai</option>
            </select>

            <button name="simpan">Simpan data</button>
            <a href="tugas.php">Batal</a>
        </form>
    </main></body>
    </html>
    <?php
    exit;
}

if ($aksi === 'detail') {
    $id = (int) ($_GET['id'] ?? 0);
    $stmt = $conn->prepare("SELECT t.*, mk.kode AS mk_kode, mk.nama AS mk_nama 
                            FROM tugas t 
                            JOIN mata_kuliah mk ON t.mata_kuliah_id = mk.id 
                            WHERE t.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $tugas = $stmt->get_result()->fetch_assoc();

    if (!$tugas) {
        exit('Tugas tidak ditemukan. Kembali ke daftar.');
    }
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Detail Tugas - LMS EAD</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body><main>
        <div class="menu">
            <a href="index.php">Beranda</a> |
            <a href="mata_kuliah.php">Mata Kuliah</a> |
            <a href="materi.php">Materi</a> |
            <a href="tugas.php">Tugas</a>
        </div>

        <p class="label">LMS EAD / TUGAS</p>
        <h1><?= htmlspecialchars($tugas['judul'], ENT_QUOTES, 'UTF-8') ?></h1>

        <dl>
            <dt>Mata kuliah</dt>
            <dd>
                <?= htmlspecialchars($tugas['mk_kode'], ENT_QUOTES, 'UTF-8') ?> - 
                <?= htmlspecialchars($tugas['mk_nama'], ENT_QUOTES, 'UTF-8') ?>
            </dd>

            <dt>Status</dt>
            <dd>
                <span class="status-badge"><?= htmlspecialchars($tugas['status'], ENT_QUOTES, 'UTF-8') ?></span>
            </dd>

            <dt>Tenggat</dt>
            <dd><?= htmlspecialchars($tugas['tenggat'], ENT_QUOTES, 'UTF-8') ?></dd>

            <dt>Judul tugas</dt>
            <dd><?= htmlspecialchars($tugas['judul'], ENT_QUOTES, 'UTF-8') ?></dd>

            <dt>Instruksi tugas</dt>
            <dd><?= nl2br(htmlspecialchars($tugas['instruksi'], ENT_QUOTES, 'UTF-8')) ?></dd>
        </dl>

        <p style="margin-top: 24px;">
            <a class="tombol" href="tugas.php?aksi=edit&id=<?= $id ?>">Edit data</a>
            <a href="tugas.php">Kembali</a>
        </p>
    </main></body>
    </html>
    <?php
    exit;
}

if ($aksi === 'hapus') {
    $id = (int) ($_GET['id'] ?? ($_POST['id'] ?? 0));
    $stmt = $conn->prepare("SELECT * FROM tugas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $tugas = $stmt->get_result()->fetch_assoc();

    if (!$tugas) {
        exit('Tugas tidak ditemukan. Kembali ke daftar.');
    }

    if (isset($_POST['hapus'])) {
        if (!isset($_POST['token']) || !hash_equals($_SESSION['token'], $_POST['token'])) {
            exit('ERROR J05 — Token keamanan tidak valid.');
        }

        $stmt_del = $conn->prepare("DELETE FROM tugas WHERE id = ?");
        $stmt_del->bind_param("i", $id);
        $stmt_del->execute();

        $_SESSION['pesan'] = 'Tugas "' . $tugas['judul'] . '" berhasil dihapus.';
        header('Location: tugas.php');
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Hapus Tugas - LMS EAD</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body><main>
        <div class="menu">
            <a href="index.php">Beranda</a> |
            <a href="mata_kuliah.php">Mata Kuliah</a> |
            <a href="materi.php">Materi</a> |
            <a href="tugas.php">Tugas</a>
        </div>

        <p class="label">LMS EAD / KONFIRMASI</p>
        <h1>Hapus tugas?</h1>
        <p>"<?= htmlspecialchars($tugas['judul'], ENT_QUOTES, 'UTF-8') ?>" akan dihapus.</p>
        <p>Data baru dihapus setelah tombol konfirmasi dikirim.</p>

        <form method="post" action="tugas.php?aksi=hapus&id=<?= $id ?>">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['token'], ENT_QUOTES, 'UTF-8') ?>">

            <button name="hapus" class="bahaya">Ya, hapus data</button>
            <a href="tugas.php">Batal</a>
        </form>
    </main></body>
    </html>
    <?php
    exit;
}

$hasil_mk = mysqli_query($conn, "SELECT id, kode, nama FROM mata_kuliah ORDER BY kode ASC");
$cari = trim($_GET['cari'] ?? '');
$filter_mk = (int) ($_GET['mata_kuliah_id'] ?? 0);
$filter_status = trim($_GET['status'] ?? '');

$sql = "SELECT t.*, mk.kode AS mk_kode, mk.nama AS mk_nama 
        FROM tugas t 
        JOIN mata_kuliah mk ON t.mata_kuliah_id = mk.id";
$syarat = [];
$params = [];
$types = "";

if ($cari !== '') {
    $syarat[] = "t.judul LIKE ?";
    $params[] = '%' . $cari . '%';
    $types .= "s";
}

if ($filter_mk > 0) {
    $syarat[] = "t.mata_kuliah_id = ?";
    $params[] = $filter_mk;
    $types .= "i";
}

if (in_array($filter_status, ['Draf', 'Aktif', 'Selesai'])) {
    $syarat[] = "t.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if (!empty($syarat)) {
    $sql .= " WHERE " . implode(" AND ", $syarat);
}
$sql .= " ORDER BY t.tenggat ASC, t.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$hasil = $stmt->get_result();
$total_data = $hasil->num_rows;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Tugas - LMS EAD</title>
    <link rel="stylesheet" href="style.css">
</head>
<body><main>
    <div class="menu">
        <a href="index.php">Beranda</a> |
        <a href="mata_kuliah.php">Mata Kuliah</a> |
        <a href="materi.php">Materi</a> |
        <a href="tugas.php">Tugas</a>
    </div>

    <p class="label">LMS EAD / TUGAS</p>
    <h1>Tugas</h1>
    <p>Menampilkan <?= $total_data ?> data.</p>

    <?php if (isset($_SESSION['pesan'])): ?>
        <p class="pesan"><?= htmlspecialchars($_SESSION['pesan'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php unset($_SESSION['pesan']); ?>
    <?php endif; ?>

    <p><a class="tombol" href="tugas.php?aksi=tambah">Tambah tugas</a></p>

    <form method="get" class="pencarian">
        <input name="cari" value="<?= htmlspecialchars($cari, ENT_QUOTES, 'UTF-8') ?>" placeholder="Judul tugas...">

        <select name="mata_kuliah_id">
            <option value="">Semua mata kuliah</option>
            <?php while ($mk = mysqli_fetch_assoc($hasil_mk)): ?>
                <option value="<?= $mk['id'] ?>" <?= ($filter_mk == $mk['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($mk['kode'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($mk['nama'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endwhile; ?>
        </select>

        <select name="status">
            <option value="">Semua status</option>
            <option value="Draf" <?= ($filter_status === 'Draf') ? 'selected' : '' ?>>Draf</option>
            <option value="Aktif" <?= ($filter_status === 'Aktif') ? 'selected' : '' ?>>Aktif</option>
            <option value="Selesai" <?= ($filter_status === 'Selesai') ? 'selected' : '' ?>>Selesai</option>
        </select>

        <button type="submit">Cari</button>
        <a href="tugas.php">Reset</a>
    </form>

    <?php if ($total_data == 0): ?>
        <div class="kesalahan">
            <p>Data tidak ditemukan. Coba kata kunci lain.</p>
            <a href="tugas.php">Reset</a>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Judul tugas</th>
                    <th>Mata kuliah</th>
                    <th>Tenggat</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $hasil->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['judul'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <strong><?= htmlspecialchars($row['mk_kode'], ENT_QUOTES, 'UTF-8') ?></strong> - 
                            <?= htmlspecialchars($row['mk_nama'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td><?= htmlspecialchars($row['tenggat'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="status-badge"><?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td>
                            <a href="tugas.php?aksi=detail&id=<?= (int)$row['id'] ?>">Detail</a>
                            <a href="tugas.php?aksi=edit&id=<?= (int)$row['id'] ?>">Edit</a>
                            <a href="tugas.php?aksi=hapus&id=<?= (int)$row['id'] ?>">Hapus</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main></body>
</html>
