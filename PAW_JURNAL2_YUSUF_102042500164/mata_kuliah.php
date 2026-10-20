<?php
session_start();
require 'koneksi.php';

if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

$aksi = $_GET['aksi'] ?? 'daftar';
$pesan = '';
$pesan_kode = '';

if ($aksi === 'tambah') {
    $kode = '';
    $nama = '';
    $dosen = '';
    $semester = '';

    if (isset($_POST['simpan'])) {
        if (!isset($_POST['token']) || !hash_equals($_SESSION['token'], $_POST['token'])) {
            $pesan = 'ERROR J05 — Token keamanan tidak valid atau telah kedaluwarsa.';
        } else {
            $kode = strtoupper(trim($_POST['kode'] ?? ''));
            $nama = trim($_POST['nama'] ?? '');
            $dosen = trim($_POST['dosen'] ?? '');
            $semester_input = trim($_POST['semester'] ?? '');

            if ($kode === '' || $nama === '' || $dosen === '' || $semester_input === '') {
                $pesan = 'Semua kolom wajib diisi.';
            } elseif (strlen($kode) < 3 || strlen($kode) > 20 || !preg_match('/^[A-Z0-9\-]+$/', $kode)) {
                $pesan_kode = 'Kode mata kuliah harus 3-20 karakter (huruf kapital, angka, tanda -).';
            } elseif (strlen($nama) < 3 || strlen($nama) > 100) {
                $pesan = 'Nama mata kuliah harus 3-100 karakter.';
            } elseif (strlen($dosen) < 3 || strlen($dosen) > 100) {
                $pesan = 'Nama dosen pengampu harus 3-100 karakter.';
            } elseif (!is_numeric($semester_input) || (int)$semester_input < 1 || (int)$semester_input > 14) {
                $pesan = 'Semester harus berupa bilangan bulat antara 1 sampai 14.';
            } else {
                $semester = (int)$semester_input;

                $stmt_cek = $conn->prepare("SELECT id FROM mata_kuliah WHERE kode = ?");
                $stmt_cek->bind_param("s", $kode);
                $stmt_cek->execute();
                if ($stmt_cek->get_result()->num_rows > 0) {
                    $pesan_kode = 'Kode mata kuliah sudah digunakan.';
                } else {
                    $stmt_ins = $conn->prepare("INSERT INTO mata_kuliah (kode, nama, dosen, semester) VALUES (?, ?, ?, ?)");
                    $stmt_ins->bind_param("sssi", $kode, $nama, $dosen, $semester);
                    $stmt_ins->execute();

                    $_SESSION['pesan'] = 'Mata kuliah ' . $kode . ' berhasil ditambahkan.';
                    header('Location: mata_kuliah.php');
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
        <title>Tambah Mata Kuliah - LMS EAD</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body><main>
        <div class="menu">
            <a href="index.php">Beranda</a> |
            <a href="mata_kuliah.php">Mata Kuliah</a> |
            <a href="materi.php">Materi</a> |
            <a href="tugas.php">Tugas</a>
        </div>

        <p class="label">LMS EAD / TAMBAH</p>
        <h1>Tambah mata kuliah</h1>
        <p><a href="mata_kuliah.php">Kembali ke daftar</a></p>

        <?php if ($pesan != ''): ?>
            <div class="kesalahan"><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="mata_kuliah.php?aksi=tambah">
            <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['token'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="kode">Kode mata kuliah</label>
            <input id="kode" name="kode" maxlength="20" value="<?= htmlspecialchars($kode, ENT_QUOTES, 'UTF-8') ?>" placeholder="Contoh: PAW-101" required>
            <?php if ($pesan_kode != ''): ?>
                <div class="pesan-field"><?= htmlspecialchars($pesan_kode, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <label for="nama">Nama mata kuliah</label>
            <input id="nama" name="nama" maxlength="100" value="<?= htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') ?>" placeholder="Contoh: Pengembangan Aplikasi Website" required>

            <label for="dosen">Dosen pengampu</label>
            <input id="dosen" name="dosen" maxlength="100" value="<?= htmlspecialchars($dosen, ENT_QUOTES, 'UTF-8') ?>" placeholder="Contoh: Dosen PAW" required>

            <label for="semester">Semester (1 - 14)</label>
            <input id="semester" name="semester" type="number" min="1" max="14" value="<?= htmlspecialchars($semester, ENT_QUOTES, 'UTF-8') ?>" placeholder="Contoh: 3" required>

            <button name="simpan">Simpan data</button>
            <a href="mata_kuliah.php">Batal</a>
        </form>
    </main></body>
    </html>
    <?php
    exit;
}

if ($aksi === 'edit') {
    $id = (int) ($_GET['id'] ?? ($_POST['id'] ?? 0));
    $stmt = $conn->prepare("SELECT * FROM mata_kuliah WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $mk = $stmt->get_result()->fetch_assoc();

    if (!$mk) {
        exit('Mata kuliah tidak ditemukan. Kembali ke daftar.');
    }

    $kode = $mk['kode'];
    $nama = $mk['nama'];
    $dosen = $mk['dosen'];
    $semester = $mk['semester'];

    if (isset($_POST['simpan'])) {
        if (!isset($_POST['token']) || !hash_equals($_SESSION['token'], $_POST['token'])) {
            $pesan = 'ERROR J05 — Token keamanan tidak valid atau telah kedaluwarsa.';
        } else {
            $new_kode = strtoupper(trim($_POST['kode'] ?? ''));
            $new_nama = trim($_POST['nama'] ?? '');
            $new_dosen = trim($_POST['dosen'] ?? '');
            $new_semester = trim($_POST['semester'] ?? '');

            if ($new_kode === $mk['kode'] && $new_nama === $mk['nama'] && $new_dosen === $mk['dosen'] && (int)$new_semester === (int)$mk['semester']) {
                $_SESSION['pesan'] = 'Tidak ada perubahan data.';
                header('Location: mata_kuliah.php');
                exit;
            }

            if ($new_kode === '' || $new_nama === '' || $new_dosen === '' || $new_semester === '') {
                $pesan = 'Semua kolom wajib diisi.';
            } elseif (strlen($new_kode) < 3 || strlen($new_kode) > 20 || !preg_match('/^[A-Z0-9\-]+$/', $new_kode)) {
                $pesan_kode = 'Kode mata kuliah harus 3-20 karakter (huruf kapital, angka, tanda -).';
            } elseif (strlen($new_nama) < 3 || strlen($new_nama) > 100) {
                $pesan = 'Nama mata kuliah harus 3-100 karakter.';
            } elseif (strlen($new_dosen) < 3 || strlen($new_dosen) > 100) {
                $pesan = 'Nama dosen pengampu harus 3-100 karakter.';
            } elseif (!is_numeric($new_semester) || (int)$new_semester < 1 || (int)$new_semester > 14) {
                $pesan = 'Semester harus berupa bilangan bulat antara 1 sampai 14.';
            } else {
                $stmt_cek = $conn->prepare("SELECT id FROM mata_kuliah WHERE kode = ? AND id <> ?");
                $stmt_cek->bind_param("si", $new_kode, $id);
                $stmt_cek->execute();
                if ($stmt_cek->get_result()->num_rows > 0) {
                    $pesan_kode = 'Kode mata kuliah sudah digunakan.';
                } else {
                    $sem_val = (int)$new_semester;
                    $stmt_up = $conn->prepare("UPDATE mata_kuliah SET kode = ?, nama = ?, dosen = ?, semester = ? WHERE id = ?");
                    $stmt_up->bind_param("sssii", $new_kode, $new_nama, $new_dosen, $sem_val, $id);
                    $stmt_up->execute();

                    $_SESSION['pesan'] = 'Data mata kuliah ' . $new_kode . ' berhasil diperbarui.';
                    header('Location: mata_kuliah.php');
                    exit;
                }
            }
            $kode = $new_kode;
            $nama = $new_nama;
            $dosen = $new_dosen;
            $semester = $new_semester;
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Edit Mata Kuliah - LMS EAD</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body><main>
        <div class="menu">
            <a href="index.php">Beranda</a> |
            <a href="mata_kuliah.php">Mata Kuliah</a> |
            <a href="materi.php">Materi</a> |
            <a href="tugas.php">Tugas</a>
        </div>

        <p class="label">LMS EAD / EDIT</p>
        <h1>Edit mata kuliah</h1>
        <p><a href="mata_kuliah.php">Kembali ke daftar</a></p>

        <?php if ($pesan != ''): ?>
            <div class="kesalahan"><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="mata_kuliah.php?aksi=edit&id=<?= $id ?>">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['token'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="kode">Kode mata kuliah</label>
            <input id="kode" name="kode" maxlength="20" value="<?= htmlspecialchars($kode, ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if ($pesan_kode != ''): ?>
                <div class="pesan-field"><?= htmlspecialchars($pesan_kode, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <label for="nama">Nama mata kuliah</label>
            <input id="nama" name="nama" maxlength="100" value="<?= htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') ?>" required>

            <label for="dosen">Dosen pengampu</label>
            <input id="dosen" name="dosen" maxlength="100" value="<?= htmlspecialchars($dosen, ENT_QUOTES, 'UTF-8') ?>" required>

            <label for="semester">Semester</label>
            <input id="semester" name="semester" type="number" min="1" max="14" value="<?= htmlspecialchars($semester, ENT_QUOTES, 'UTF-8') ?>" required>

            <button name="simpan">Simpan data</button>
            <a href="mata_kuliah.php">Batal</a>
        </form>
    </main></body>
    </html>
    <?php
    exit;
}

if ($aksi === 'detail') {
    $id = (int) ($_GET['id'] ?? 0);
    $stmt = $conn->prepare("SELECT * FROM mata_kuliah WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $mk = $stmt->get_result()->fetch_assoc();

    if (!$mk) {
        exit('Mata kuliah tidak ditemukan. Kembali ke daftar.');
    }

    $stmt_mat = $conn->prepare("SELECT id, judul FROM materi WHERE mata_kuliah_id = ? ORDER BY id ASC");
    $stmt_mat->bind_param("i", $id);
    $stmt_mat->execute();
    $materi_terkait = $stmt_mat->get_result();

    $stmt_tug = $conn->prepare("SELECT id, judul, tenggat, status FROM tugas WHERE mata_kuliah_id = ? ORDER BY tenggat ASC");
    $stmt_tug->bind_param("i", $id);
    $stmt_tug->execute();
    $tugas_terkait = $stmt_tug->get_result();
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Detail Mata Kuliah - LMS EAD</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body><main>
        <div class="menu">
            <a href="index.php">Beranda</a> |
            <a href="mata_kuliah.php">Mata Kuliah</a> |
            <a href="materi.php">Materi</a> |
            <a href="tugas.php">Tugas</a>
        </div>

        <p class="label">LMS EAD / DETAIL</p>
        <h1><?= htmlspecialchars($mk['nama'], ENT_QUOTES, 'UTF-8') ?></h1>

        <dl>
            <dt>Kode</dt>
            <dd><?= htmlspecialchars($mk['kode'], ENT_QUOTES, 'UTF-8') ?></dd>

            <dt>Nama mata kuliah</dt>
            <dd><?= htmlspecialchars($mk['nama'], ENT_QUOTES, 'UTF-8') ?></dd>

            <dt>Dosen pengampu</dt>
            <dd><?= htmlspecialchars($mk['dosen'], ENT_QUOTES, 'UTF-8') ?></dd>

            <dt>Semester</dt>
            <dd>Semester <?= (int)$mk['semester'] ?></dd>
        </dl>

        <h2>Materi Pembelajaran</h2>
        <?php if ($materi_terkait->num_rows > 0): ?>
            <ul>
                <?php while ($m = $materi_terkait->fetch_assoc()): ?>
                    <li>
                        <a href="materi.php?aksi=detail&id=<?= $m['id'] ?>">
                            <?= htmlspecialchars($m['judul'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>Belum ada materi untuk mata kuliah ini.</p>
        <?php endif; ?>

        <h2>Tugas Terkait</h2>
        <?php if ($tugas_terkait->num_rows > 0): ?>
            <ul>
                <?php while ($t = $tugas_terkait->fetch_assoc()): ?>
                    <li>
                        <a href="tugas.php?aksi=detail&id=<?= $t['id'] ?>">
                            <?= htmlspecialchars($t['judul'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        (Tenggat: <?= htmlspecialchars($t['tenggat'], ENT_QUOTES, 'UTF-8') ?> | Status: <?= htmlspecialchars($t['status'], ENT_QUOTES, 'UTF-8') ?>)
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>Belum ada tugas untuk mata kuliah ini.</p>
        <?php endif; ?>

        <p style="margin-top: 24px;">
            <a class="tombol" href="mata_kuliah.php?aksi=edit&id=<?= $id ?>">Edit data</a>
            <a class="tombol bahaya" href="mata_kuliah.php?aksi=hapus&id=<?= $id ?>">Hapus</a>
            <a href="mata_kuliah.php">Kembali</a>
        </p>
    </main></body>
    </html>
    <?php
    exit;
}

if ($aksi === 'hapus') {
    $id = (int) ($_GET['id'] ?? ($_POST['id'] ?? 0));
    $stmt = $conn->prepare("SELECT * FROM mata_kuliah WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $mk = $stmt->get_result()->fetch_assoc();

    if (!$mk) {
        exit('Mata kuliah tidak ditemukan. Kembali ke daftar.');
    }

    $stmt_cek = $conn->prepare("SELECT 
        (SELECT COUNT(*) FROM materi WHERE mata_kuliah_id = ?) AS jml_materi,
        (SELECT COUNT(*) FROM tugas WHERE mata_kuliah_id = ?) AS jml_tugas");
    $stmt_cek->bind_param("ii", $id, $id);
    $stmt_cek->execute();
    $relasi = $stmt_cek->get_result()->fetch_assoc();
    $masih_dipakai = ($relasi['jml_materi'] > 0 || $relasi['jml_tugas'] > 0);

    if (isset($_POST['hapus'])) {
        if (!isset($_POST['token']) || !hash_equals($_SESSION['token'], $_POST['token'])) {
            $pesan = 'ERROR J05 — Token keamanan tidak valid.';
        } elseif ($masih_dipakai) {
            $pesan = 'Data belum tersimpan. Mata kuliah masih memiliki materi atau tugas. Hapus data terkait terlebih dahulu.';
        } else {
            $stmt_del = $conn->prepare("DELETE FROM mata_kuliah WHERE id = ?");
            $stmt_del->bind_param("i", $id);
            $stmt_del->execute();

            $_SESSION['pesan'] = 'Mata kuliah ' . $mk['nama'] . ' berhasil dihapus.';
            header('Location: mata_kuliah.php');
            exit;
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Hapus Mata Kuliah - LMS EAD</title>
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
        <h1>Hapus <?= htmlspecialchars($mk['nama'], ENT_QUOTES, 'UTF-8') ?>?</h1>

        <?php if ($masih_dipakai || $pesan != ''): ?>
            <div class="kesalahan">
                Data belum tersimpan. Mata kuliah masih memiliki materi atau tugas. Hapus data terkait terlebih dahulu.
            </div>
        <?php endif; ?>

        <p>Data baru dihapus setelah tombol konfirmasi dikirim.</p>

        <form method="post" action="mata_kuliah.php?aksi=hapus&id=<?= $id ?>">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['token'], ENT_QUOTES, 'UTF-8') ?>">

            <?php if ($masih_dipakai): ?>
                <button type="button" class="bahaya" disabled style="opacity: 0.5; cursor: not-allowed;">Ya, hapus data</button>
            <?php else: ?>
                <button name="hapus" class="bahaya">Ya, hapus data</button>
            <?php endif; ?>
            <a href="mata_kuliah.php">Batal</a>
        </form>
    </main></body>
    </html>
    <?php
    exit;
}

$cari = trim($_GET['cari'] ?? '');

if ($cari !== '') {
    $param = '%' . $cari . '%';
    $stmt = $conn->prepare("SELECT * FROM mata_kuliah WHERE kode LIKE ? OR nama LIKE ? ORDER BY kode ASC");
    $stmt->bind_param("ss", $param, $param);
} else {
    $stmt = $conn->prepare("SELECT * FROM mata_kuliah ORDER BY kode ASC");
}

$stmt->execute();
$hasil = $stmt->get_result();
$total_data = $hasil->num_rows;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Mata Kuliah - LMS EAD</title>
    <link rel="stylesheet" href="style.css">
</head>
<body><main>
    <div class="menu">
        <a href="index.php">Beranda</a> |
        <a href="mata_kuliah.php">Mata Kuliah</a> |
        <a href="materi.php">Materi</a> |
        <a href="tugas.php">Tugas</a>
    </div>

    <p class="label">LMS EAD / MATA KULIAH</p>
    <h1>Mata kuliah</h1>
    <p>Menampilkan <?= $total_data ?> data.</p>

    <?php if (isset($_SESSION['pesan'])): ?>
        <p class="pesan"><?= htmlspecialchars($_SESSION['pesan'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php unset($_SESSION['pesan']); ?>
    <?php endif; ?>

    <p><a class="tombol" href="mata_kuliah.php?aksi=tambah">Tambah mata kuliah</a></p>

    <form method="get" class="pencarian">
        <label for="cari" style="margin-top: 0; display: inline;">Cari:</label>
        <input id="cari" name="cari" value="<?= htmlspecialchars($cari, ENT_QUOTES, 'UTF-8') ?>" placeholder="Kode atau nama...">
        <button type="submit">Cari</button>
        <a href="mata_kuliah.php">Reset</a>
    </form>

    <?php if ($total_data == 0): ?>
        <div class="kesalahan">
            <p>Data tidak ditemukan. Coba kata kunci lain.</p>
            <a href="mata_kuliah.php">Reset</a>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Mata kuliah</th>
                    <th>Dosen / semester</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $hasil->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($row['kode'], ENT_QUOTES, 'UTF-8') ?></strong> - 
                            <?= htmlspecialchars($row['nama'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($row['dosen'], ENT_QUOTES, 'UTF-8') ?> / Semester <?= (int)$row['semester'] ?>
                        </td>
                        <td>
                            <a href="mata_kuliah.php?aksi=detail&id=<?= (int)$row['id'] ?>">Detail</a>
                            <a href="mata_kuliah.php?aksi=edit&id=<?= (int)$row['id'] ?>">Edit</a>
                            <a href="mata_kuliah.php?aksi=hapus&id=<?= (int)$row['id'] ?>">Hapus</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main></body>
</html>
