<?php
session_start();
require 'koneksi.php';

if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

$aksi = $_GET['aksi'] ?? 'daftar';
$pesan = '';
$pesan_tautan = '';

if ($aksi === 'tambah') {
    $hasil_mk = mysqli_query($conn, "SELECT id, kode, nama FROM mata_kuliah ORDER BY kode ASC");
    $mata_kuliah_id = (int) ($_GET['mata_kuliah_id'] ?? 0);
    $judul = '';
    $isi = '';
    $tautan = '';

    if (isset($_POST['simpan'])) {
        if (!isset($_POST['token']) || !hash_equals($_SESSION['token'], $_POST['token'])) {
            $pesan = 'ERROR J05 — Token keamanan tidak valid atau telah kedaluwarsa.';
        } else {
            $mata_kuliah_id = (int) ($_POST['mata_kuliah_id'] ?? 0);
            $judul = trim($_POST['judul'] ?? '');
            $isi = trim($_POST['isi'] ?? '');
            $tautan = trim($_POST['tautan'] ?? '');

            if ($mata_kuliah_id <= 0 || $judul === '' || $isi === '') {
                $pesan = 'Mata kuliah, judul, dan isi materi wajib diisi.';
            } elseif (strlen($judul) < 3 || strlen($judul) > 150) {
                $pesan = 'Judul materi harus 3-150 karakter.';
            } elseif (strlen($isi) < 3 || strlen($isi) > 2000) {
                $pesan = 'Isi materi harus 3-2000 karakter.';
            } elseif ($tautan !== '' && (strlen($tautan) > 255 || !preg_match('/^https?:\/\/.+/i', $tautan))) {
                $pesan_tautan = 'Gunakan tautan http/https yang valid.';
            } else {
                $stmt_cek = $conn->prepare("SELECT id FROM mata_kuliah WHERE id = ?");
                $stmt_cek->bind_param("i", $mata_kuliah_id);
                $stmt_cek->execute();
                if ($stmt_cek->get_result()->num_rows === 0) {
                    $pesan = 'Mata kuliah yang dipilih tidak ditemukan.';
                } else {
                    $stmt_ins = $conn->prepare("INSERT INTO materi (mata_kuliah_id, judul, isi, tautan) VALUES (?, ?, ?, ?)");
                    $stmt_ins->bind_param("isss", $mata_kuliah_id, $judul, $isi, $tautan);
                    $stmt_ins->execute();

                    $_SESSION['pesan'] = 'Materi "' . $judul . '" berhasil ditambahkan.';
                    header('Location: materi.php');
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
        <title>Tambah Materi - LMS EAD</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body><main>
        <div class="menu">
            <a href="index.php">Beranda</a> |
            <a href="mata_kuliah.php">Mata Kuliah</a> |
            <a href="materi.php">Materi</a> |
            <a href="tugas.php">Tugas</a>
        </div>

        <p class="label">LMS EAD / TAMBAH MATERI</p>
        <h1>Tambah materi</h1>
        <p><a href="materi.php">Kembali ke daftar</a></p>

        <?php if ($pesan != ''): ?>
            <div class="kesalahan"><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="materi.php?aksi=tambah">
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

            <label for="judul">Judul materi</label>
            <input id="judul" name="judul" maxlength="150" value="<?= htmlspecialchars($judul, ENT_QUOTES, 'UTF-8') ?>" placeholder="Contoh: Transaksi database" required>

            <label for="isi">Isi materi</label>
            <textarea id="isi" name="isi" rows="6" placeholder="Tuliskan isi materi..." required><?= htmlspecialchars($isi, ENT_QUOTES, 'UTF-8') ?></textarea>

            <label for="tautan">Tautan referensi</label>
            <input id="tautan" name="tautan" maxlength="255" value="<?= htmlspecialchars($tautan, ENT_QUOTES, 'UTF-8') ?>" placeholder="https://...">
            <?php if ($pesan_tautan != ''): ?>
                <div class="pesan-field"><?= htmlspecialchars($pesan_tautan, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <button name="simpan">Simpan data</button>
            <a href="materi.php">Batal</a>
        </form>
    </main></body>
    </html>
    <?php
    exit;
}

if ($aksi === 'edit') {
    $id = (int) ($_GET['id'] ?? ($_POST['id'] ?? 0));
    $stmt = $conn->prepare("SELECT * FROM materi WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $materi = $stmt->get_result()->fetch_assoc();

    if (!$materi) {
        exit('Materi tidak ditemukan. Kembali ke daftar.');
    }

    $hasil_mk = mysqli_query($conn, "SELECT id, kode, nama FROM mata_kuliah ORDER BY kode ASC");

    $mata_kuliah_id = $materi['mata_kuliah_id'];
    $judul = $materi['judul'];
    $isi = $materi['isi'];
    $tautan = $materi['tautan'] ?? '';

    if (isset($_POST['simpan'])) {
        if (!isset($_POST['token']) || !hash_equals($_SESSION['token'], $_POST['token'])) {
            $pesan = 'ERROR J05 — Token keamanan tidak valid atau telah kedaluwarsa.';
        } else {
            $new_mk_id = (int) ($_POST['mata_kuliah_id'] ?? 0);
            $new_judul = trim($_POST['judul'] ?? '');
            $new_isi = trim($_POST['isi'] ?? '');
            $new_tautan = trim($_POST['tautan'] ?? '');

            if ($new_mk_id === (int)$materi['mata_kuliah_id'] && $new_judul === $materi['judul'] && $new_isi === $materi['isi'] && $new_tautan === (string)$materi['tautan']) {
                $_SESSION['pesan'] = 'Tidak ada perubahan data.';
                header('Location: materi.php');
                exit;
            }

            if ($new_mk_id <= 0 || $new_judul === '' || $new_isi === '') {
                $pesan = 'Mata kuliah, judul, dan isi materi wajib diisi.';
            } elseif (strlen($new_judul) < 3 || strlen($new_judul) > 150) {
                $pesan = 'Judul materi harus 3-150 karakter.';
            } elseif (strlen($new_isi) < 3 || strlen($new_isi) > 2000) {
                $pesan = 'Isi materi harus 3-2000 karakter.';
            } elseif ($new_tautan !== '' && (strlen($new_tautan) > 255 || !preg_match('/^https?:\/\/.+/i', $new_tautan))) {
                $pesan_tautan = 'Gunakan tautan http/https yang valid.';
            } else {
                $stmt_up = $conn->prepare("UPDATE materi SET mata_kuliah_id = ?, judul = ?, isi = ?, tautan = ? WHERE id = ?");
                $stmt_up->bind_param("isssi", $new_mk_id, $new_judul, $new_isi, $new_tautan, $id);
                $stmt_up->execute();

                $_SESSION['pesan'] = 'Data materi "' . $new_judul . '" berhasil diperbarui.';
                header('Location: materi.php');
                exit;
            }

            $mata_kuliah_id = $new_mk_id;
            $judul = $new_judul;
            $isi = $new_isi;
            $tautan = $new_tautan;
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Edit Materi - LMS EAD</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body><main>
        <div class="menu">
            <a href="index.php">Beranda</a> |
            <a href="mata_kuliah.php">Mata Kuliah</a> |
            <a href="materi.php">Materi</a> |
            <a href="tugas.php">Tugas</a>
        </div>

        <p class="label">LMS EAD / EDIT MATERI</p>
        <h1>Edit materi</h1>
        <p><a href="materi.php">Kembali ke daftar</a></p>

        <?php if ($pesan != ''): ?>
            <div class="kesalahan"><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="materi.php?aksi=edit&id=<?= $id ?>">
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

            <label for="judul">Judul materi</label>
            <input id="judul" name="judul" maxlength="150" value="<?= htmlspecialchars($judul, ENT_QUOTES, 'UTF-8') ?>" required>

            <label for="isi">Isi materi</label>
            <textarea id="isi" name="isi" rows="6" required><?= htmlspecialchars($isi, ENT_QUOTES, 'UTF-8') ?></textarea>

            <label for="tautan">Tautan referensi</label>
            <input id="tautan" name="tautan" maxlength="255" value="<?= htmlspecialchars($tautan, ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($pesan_tautan != ''): ?>
                <div class="pesan-field"><?= htmlspecialchars($pesan_tautan, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <button name="simpan">Simpan data</button>
            <a href="materi.php">Batal</a>
        </form>
    </main></body>
    </html>
    <?php
    exit;
}

if ($aksi === 'detail') {
    $id = (int) ($_GET['id'] ?? 0);
    $stmt = $conn->prepare("SELECT m.*, mk.kode AS mk_kode, mk.nama AS mk_nama 
                            FROM materi m 
                            JOIN mata_kuliah mk ON m.mata_kuliah_id = mk.id 
                            WHERE m.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $materi = $stmt->get_result()->fetch_assoc();

    if (!$materi) {
        exit('Materi tidak ditemukan. Kembali ke daftar.');
    }
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Detail Materi - LMS EAD</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body><main>
        <div class="menu">
            <a href="index.php">Beranda</a> |
            <a href="mata_kuliah.php">Mata Kuliah</a> |
            <a href="materi.php">Materi</a> |
            <a href="tugas.php">Tugas</a>
        </div>

        <p class="label">LMS EAD / MATERI</p>
        <h1><?= htmlspecialchars($materi['judul'], ENT_QUOTES, 'UTF-8') ?></h1>
        <dl>
            <dt>Mata kuliah</dt>
            <dd>
                <?= htmlspecialchars($materi['mk_kode'], ENT_QUOTES, 'UTF-8') ?> - 
                <?= htmlspecialchars($materi['mk_nama'], ENT_QUOTES, 'UTF-8') ?>
            </dd>

            <dt>Judul materi</dt>
            <dd><?= htmlspecialchars($materi['judul'], ENT_QUOTES, 'UTF-8') ?></dd>

            <dt>Isi materi</dt>
            <dd><?= nl2br(htmlspecialchars($materi['isi'], ENT_QUOTES, 'UTF-8')) ?></dd>

            <dt>Tautan referensi</dt>
            <dd>
                <?php if (!empty($materi['tautan'])): ?>
                    <a href="<?= htmlspecialchars($materi['tautan'], ENT_QUOTES, 'UTF-8') ?>" target="_blank">
                        <?= htmlspecialchars($materi['tautan'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php else: ?>
                    -
                <?php endif; ?>
            </dd>
        </dl>

        <p style="margin-top: 24px;">
            <a class="tombol" href="materi.php?aksi=edit&id=<?= $id ?>">Edit data</a>
            <a href="materi.php">Kembali</a>
        </p>
    </main></body>
    </html>
    <?php
    exit;
}

if ($aksi === 'hapus') {
    $id = (int) ($_GET['id'] ?? ($_POST['id'] ?? 0));
    $stmt = $conn->prepare("SELECT * FROM materi WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $materi = $stmt->get_result()->fetch_assoc();

    if (!$materi) {
        exit('Materi tidak ditemukan. Kembali ke daftar.');
    }

    if (isset($_POST['hapus'])) {
        if (!isset($_POST['token']) || !hash_equals($_SESSION['token'], $_POST['token'])) {
            exit('ERROR J05 — Token keamanan tidak valid.');
        }

        $stmt_del = $conn->prepare("DELETE FROM materi WHERE id = ?");
        $stmt_del->bind_param("i", $id);
        $stmt_del->execute();

        $_SESSION['pesan'] = 'Materi "' . $materi['judul'] . '" berhasil dihapus.';
        header('Location: materi.php');
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Hapus Materi - LMS EAD</title>
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
        <h1>Hapus materi?</h1>
        <p>"<?= htmlspecialchars($materi['judul'], ENT_QUOTES, 'UTF-8') ?>" akan dihapus.</p>
        <p>Data baru dihapus setelah tombol konfirmasi dikirim.</p>

        <form method="post" action="materi.php?aksi=hapus&id=<?= $id ?>">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['token'], ENT_QUOTES, 'UTF-8') ?>">

            <button name="hapus" class="bahaya">Ya, hapus data</button>
            <a href="materi.php">Batal</a>
        </form>
    </main></body>
    </html>
    <?php
    exit;
}

$hasil_mk = mysqli_query($conn, "SELECT id, kode, nama FROM mata_kuliah ORDER BY kode ASC");
$cari = trim($_GET['cari'] ?? '');
$filter_mk = (int) ($_GET['mata_kuliah_id'] ?? 0);

if ($cari !== '' && $filter_mk > 0) {
    $param_cari = '%' . $cari . '%';
    $stmt = $conn->prepare("SELECT m.*, mk.kode AS mk_kode, mk.nama AS mk_nama 
                            FROM materi m 
                            JOIN mata_kuliah mk ON m.mata_kuliah_id = mk.id 
                            WHERE m.judul LIKE ? AND m.mata_kuliah_id = ? 
                            ORDER BY m.id DESC");
    $stmt->bind_param("si", $param_cari, $filter_mk);
} elseif ($cari !== '') {
    $param_cari = '%' . $cari . '%';
    $stmt = $conn->prepare("SELECT m.*, mk.kode AS mk_kode, mk.nama AS mk_nama 
                            FROM materi m 
                            JOIN mata_kuliah mk ON m.mata_kuliah_id = mk.id 
                            WHERE m.judul LIKE ? 
                            ORDER BY m.id DESC");
    $stmt->bind_param("s", $param_cari);
} elseif ($filter_mk > 0) {
    $stmt = $conn->prepare("SELECT m.*, mk.kode AS mk_kode, mk.nama AS mk_nama 
                            FROM materi m 
                            JOIN mata_kuliah mk ON m.mata_kuliah_id = mk.id 
                            WHERE m.mata_kuliah_id = ? 
                            ORDER BY m.id DESC");
    $stmt->bind_param("i", $filter_mk);
} else {
    $stmt = $conn->prepare("SELECT m.*, mk.kode AS mk_kode, mk.nama AS mk_nama 
                            FROM materi m 
                            JOIN mata_kuliah mk ON m.mata_kuliah_id = mk.id 
                            ORDER BY m.id DESC");
}

$stmt->execute();
$hasil = $stmt->get_result();
$total_data = $hasil->num_rows;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Materi - LMS EAD</title>
    <link rel="stylesheet" href="style.css">
</head>
<body><main>
    <div class="menu">
        <a href="index.php">Beranda</a> |
        <a href="mata_kuliah.php">Mata Kuliah</a> |
        <a href="materi.php">Materi</a> |
        <a href="tugas.php">Tugas</a>
    </div>

    <p class="label">LMS EAD / MATERI</p>
    <h1>Materi</h1>
    <p>Menampilkan <?= $total_data ?> data.</p>

    <?php if (isset($_SESSION['pesan'])): ?>
        <p class="pesan"><?= htmlspecialchars($_SESSION['pesan'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php unset($_SESSION['pesan']); ?>
    <?php endif; ?>

    <p><a class="tombol" href="materi.php?aksi=tambah">Tambah materi</a></p>

    <form method="get" class="pencarian">
        <input name="cari" value="<?= htmlspecialchars($cari, ENT_QUOTES, 'UTF-8') ?>" placeholder="Kata kunci judul...">
        
        <select name="mata_kuliah_id">
            <option value="">Semua mata kuliah</option>
            <?php while ($mk = mysqli_fetch_assoc($hasil_mk)): ?>
                <option value="<?= $mk['id'] ?>" <?= ($filter_mk == $mk['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($mk['kode'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($mk['nama'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endwhile; ?>
        </select>

        <button type="submit">Cari</button>
        <a href="materi.php">Reset</a>
    </form>

    <?php if ($total_data == 0): ?>
        <div class="kesalahan">
            <p>Data tidak ditemukan. Coba kata kunci lain.</p>
            <a href="materi.php">Reset</a>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Mata kuliah</th>
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
                        <td>
                            <a href="materi.php?aksi=detail&id=<?= (int)$row['id'] ?>">Detail</a>
                            <a href="materi.php?aksi=edit&id=<?= (int)$row['id'] ?>">Edit</a>
                            <a href="materi.php?aksi=hapus&id=<?= (int)$row['id'] ?>">Hapus</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main></body>
</html>
