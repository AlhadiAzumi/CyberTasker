<?php
session_start();
include 'koneksi.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$user_id   = $_SESSION['user_id'];
$role      = $_SESSION['role'];
$room_code = $_SESSION['room_code'];
$email     = $_SESSION['email'];
$nickname  = ucfirst(explode('@', $email)[0]);

// Stats
$r = $conn->prepare("SELECT COUNT(*) as c FROM tasks WHERE room_code = ?");
$r->bind_param("s",$room_code); $r->execute();
$total_tasks = $r->get_result()->fetch_assoc()['c'];

if ($role == 'dosen') {
    $r2=$conn->prepare("SELECT COUNT(*) as c FROM submissions s JOIN tasks t ON s.task_id=t.id WHERE t.room_code=?");
    $r2->bind_param("s",$room_code); $r2->execute();
    $total_sub = $r2->get_result()->fetch_assoc()['c'];
    $r3=$conn->prepare("SELECT COUNT(DISTINCT mahasiswa_id) as c FROM submissions s JOIN tasks t ON s.task_id=t.id WHERE t.room_code=?");
    $r3->bind_param("s",$room_code); $r3->execute();
    $total_mhs = $r3->get_result()->fetch_assoc()['c'];
} else {
    $r2=$conn->prepare("SELECT COUNT(*) as c FROM submissions WHERE mahasiswa_id=?");
    $r2->bind_param("i",$user_id); $r2->execute();
    $my_sub = $r2->get_result()->fetch_assoc()['c'];
}

// Daftar tugas
$tasks_list = [];
$stmt=$conn->prepare("SELECT * FROM tasks WHERE room_code=? ORDER BY id DESC");
$stmt->bind_param("s",$room_code); $stmt->execute();
$res=$stmt->get_result(); while($t=$res->fetch_assoc()) $tasks_list[]=$t;

// Submissions
$submissions_list = [];
if ($role=='dosen') {
    $s=$conn->prepare("SELECT s.*,u.email as mhs_email,t.title as task_title FROM submissions s JOIN users u ON s.mahasiswa_id=u.id JOIN tasks t ON s.task_id=t.id WHERE t.room_code=? ORDER BY s.id DESC");
    $s->bind_param("s",$room_code);
} else {
    $s=$conn->prepare("SELECT s.*,t.title as task_title FROM submissions s JOIN tasks t ON s.task_id=t.id WHERE s.mahasiswa_id=? ORDER BY s.id DESC");
    $s->bind_param("i",$user_id);
}
$s->execute(); $sr=$s->get_result(); while($sub=$sr->fetch_assoc()) $submissions_list[]=$sub;

// Dosen: tambah tugas
$task_msg="";
if ($role=='dosen' && $_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['add_task'])) {
    $title=trim($_POST['title']); $desc=trim($_POST['description']);
    if ($title!=='') {
        $ins=$conn->prepare("INSERT INTO tasks (title, description, room_code) VALUES (?,?,?)");
        $ins->bind_param("sss",$title,$desc,$room_code);
        if ($ins->execute()) { header("Location: dashboard.php?added=1"); exit(); }
    }
}
$added   = isset($_GET['added']);
$uploaded= isset($_GET['upload']) && $_GET['upload']=='success';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Cyber-Tasker</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%230f172a'/%3E%3Crect x='6' y='6' width='52' height='52' rx='10' fill='none' stroke='%2300f2fe' stroke-width='2' opacity='0.4'/%3E%3Crect x='18' y='20' width='28' height='4' rx='2' fill='%2300f2fe'/%3E%3Crect x='18' y='30' width='20' height='4' rx='2' fill='%234facfe' opacity='0.8'/%3E%3Crect x='18' y='40' width='24' height='4' rx='2' fill='%234facfe' opacity='0.5'/%3E%3Ccircle cx='48' cy='44' r='10' fill='%230f172a'/%3E%3Ccircle cx='48' cy='44' r='9' fill='%2300f2fe'/%3E%3Ctext x='48' y='49' text-anchor='middle' font-size='13' font-weight='700' font-family='Arial' fill='%230f172a'%3E%E2%9C%93%3C/text%3E%3C/svg%3E">
<style>
:root {
    --primary:#00f2fe; --secondary:#4facfe;
    --bg:#0f172a; --card:#1e293b; --border:#334155;
    --text:#f8fafc; --muted:#94a3b8; --input-bg:#0f172a;
    --danger:#ef4444; --safe:#22c55e; --warn:#f59e0b; --purple:#a855f7;
}
*,*::before,*::after { box-sizing:border-box; margin:0; padding:0; font-family:'Poppins',sans-serif; }
body { overflow-y:auto; min-height:100vh; padding:20px; background:var(--bg); color:var(--text); }

/* Toast */
.toast-container { position:fixed; top:-100px; left:50%; transform:translateX(-50%); background:var(--card); border:1px solid var(--primary); box-shadow:0 0 20px rgba(0,242,254,.3); padding:12px 24px; border-radius:12px; display:flex; align-items:center; gap:10px; z-index:10000; transition:top .45s cubic-bezier(0.68,-0.55,0.265,1.55); white-space:nowrap; }
.toast-container.show { top:24px; }
.toast-message { color:#fff; font-size:.9rem; font-weight:500; }

/* Wrapper */
.wrapper { width:100%; max-width:960px; margin:0 auto; }

/* Header */
header { display:flex; justify-content:space-between; align-items:center; background:var(--card); padding:16px 20px; border-radius:12px; margin-bottom:20px; border:1px solid var(--border); gap:12px; flex-wrap:wrap; }
.user-welcome h1 { font-size:1.3rem; font-weight:700; }
.user-welcome p  { color:var(--muted); font-size:.85rem; font-weight:300; margin-top:2px; }
.user-welcome span { color:var(--primary); font-weight:700; }
.header-actions { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
.role-badge { padding:6px 14px; border-radius:8px; font-size:.82rem; font-weight:600; letter-spacing:.3px; text-transform:uppercase; }
.role-dosen    { background:rgba(245,158,11,.15); color:var(--warn); border:1px solid rgba(245,158,11,.3); }
.role-mahasiswa{ background:rgba(0,242,254,.12);  color:var(--primary); border:1px solid rgba(0,242,254,.3); }
.room-badge { background:rgba(168,85,247,.15); color:var(--purple); border:1px solid rgba(168,85,247,.3); padding:6px 14px; border-radius:8px; font-size:.82rem; font-weight:600; }
.logout-btn { padding:9px 16px; background:var(--danger); color:#fff; font-size:.85rem; border-radius:8px; border:none; cursor:pointer; font-weight:500; transition:.2s; font-family:inherit; }
.logout-btn:hover { background:#dc2626; }

/* Stats */
.stats-container { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:20px; }
.stats-card { background:var(--card); border:1px solid var(--border); padding:14px; border-radius:12px; text-align:center; }
.stats-count { font-size:1.8rem; font-weight:700; color:var(--primary); display:block; }
.stats-count.red    { color:var(--danger); }
.stats-count.green  { color:var(--safe); }
.stats-count.orange { color:var(--warn); }
.stats-label { font-size:.78rem; color:var(--muted); font-weight:300; margin-top:2px; }

/* Cards */
.form-card,.list-card { background:var(--card); padding:20px; border-radius:12px; border:1px solid var(--border); margin-bottom:20px; }
.form-card h3,.list-card h3 { font-size:1rem; font-weight:500; margin-bottom:16px; color:var(--text); }

/* Inputs */
input[type="text"],input[type="date"],textarea,select {
    background:var(--input-bg); border:1px solid #475569; padding:11px 14px;
    border-radius:8px; color:var(--text); font-size:.9rem; width:100%;
    transition:border-color .2s; font-family:'Poppins',sans-serif; outline:none;
}
input:focus,textarea:focus,select:focus { border-color:var(--primary); box-shadow:0 0 0 2px rgba(0,242,254,.15); }
input::placeholder,textarea::placeholder { font-weight:300; color:#64748b; }
select option { background:#0f172a; color:#fff; }
input[type="date"]::-webkit-calendar-picker-indicator { filter:invert(1); cursor:pointer; }
textarea { resize:vertical; min-height:80px; }

.input-row { display:flex; flex-direction:column; gap:10px; margin-bottom:14px; }
.submit-btn { padding:11px 22px; background:linear-gradient(45deg,var(--secondary),var(--primary)); color:#000; border-radius:8px; border:none; cursor:pointer; font-weight:700; font-size:.9rem; font-family:inherit; transition:.2s; }
.submit-btn:hover { filter:brightness(1.1); }

/* Alert banners */
.alert { padding:12px 16px; border-radius:10px; font-size:.88rem; margin-bottom:18px; }
.alert-success { background:rgba(34,197,94,.12); border:1px solid rgba(34,197,94,.3); color:#86efac; }
.alert-error   { background:rgba(239,68,68,.12); border:1px solid rgba(239,68,68,.3); color:#fca5a5; }

/* Table */
table { width:100%; border-collapse:collapse; font-size:.88rem; }
thead th { padding:10px 14px; text-align:left; font-size:.75rem; font-weight:500; color:var(--muted); text-transform:uppercase; letter-spacing:.4px; border-bottom:1px solid var(--border); }
tbody tr { transition:background .15s; }
tbody tr:hover { background:rgba(255,255,255,.03); }
tbody td { padding:12px 14px; border-bottom:1px solid var(--border); color:var(--text); font-weight:300; }
tbody tr:last-child td { border-bottom:none; }

.tag { display:inline-block; padding:3px 10px; border-radius:99px; font-size:.72rem; font-weight:600; letter-spacing:.3px; }
.tag-cyan   { background:rgba(0,242,254,.12); color:var(--primary); border:1px solid rgba(0,242,254,.3); }
.tag-green  { background:rgba(34,197,94,.12); color:var(--safe);    border:1px solid rgba(34,197,94,.3); }
.tag-orange { background:rgba(245,158,11,.12); color:var(--warn);   border:1px solid rgba(245,158,11,.3); }
.tag-purple { background:rgba(168,85,247,.12); color:var(--purple); border:1px solid rgba(168,85,247,.3); }

.link-dl { color:var(--primary); font-size:.85rem; font-weight:500; text-decoration:none; }
.link-dl:hover { text-decoration:underline; }

.empty-state { text-align:center; padding:36px 0; color:var(--muted); font-size:.9rem; font-weight:300; }

/* Upload zone */
.file-zone { border:2px dashed var(--border); border-radius:10px; padding:28px; text-align:center; cursor:pointer; transition:border-color .2s; }
.file-zone:hover { border-color:var(--primary); }
.file-zone input[type="file"] { display:none; }
.file-zone-label { font-size:.85rem; color:var(--muted); font-weight:300; cursor:pointer; display:block; }
.file-zone-name  { font-size:.85rem; color:var(--primary); font-weight:500; margin-top:6px; display:none; }

/* Grid */
.grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; }

/* List header with filter tabs */
.list-header { display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border); padding-bottom:12px; margin-bottom:16px; gap:10px; flex-wrap:wrap; }
.filter-tabs { display:flex; gap:6px; background:var(--input-bg); padding:4px; border-radius:8px; border:1px solid var(--border); }
.filter-btn { background:transparent; border:none; color:#64748b; padding:5px 11px; font-size:.78rem; border-radius:6px; cursor:pointer; font-weight:500; transition:.2s; font-family:inherit; }
.filter-btn:hover { color:var(--text); }
.filter-btn.active { background:var(--card); color:var(--primary); box-shadow:0 0 10px rgba(0,242,254,.2); border:1px solid rgba(0,242,254,.3); }

/* Avatar */
.avatar { width:28px; height:28px; background:var(--primary); border-radius:50%; display:inline-grid; place-items:center; font-size:.72rem; font-weight:700; color:#000; vertical-align:middle; margin-right:8px; flex-shrink:0; }

@media (max-width:640px) {
    .grid-2 { grid-template-columns:1fr; }
    .stats-container { gap:8px; }
    .stats-card { padding:10px 6px; }
    .stats-count { font-size:1.4rem; }
    .stats-label { font-size:.7rem; }
    header { padding:12px; }
    .header-actions { width:100%; }
    .header-actions button,.header-actions .role-badge,.header-actions .room-badge { flex:1; text-align:center; font-size:.78rem; padding:8px 8px; }
}
</style>
</head>
<body>

<!-- Toast -->
<div id="toast" class="toast-container">
    <span id="toastIcon">🚀</span>
    <span class="toast-message" id="toastMsg"></span>
</div>

<div class="wrapper">

    <!-- Header -->
    <header>
        <div class="user-welcome">
            <h1>Dashboard Tugas</h1>
            <p>Selamat datang kembali, <span><?= htmlspecialchars($nickname) ?></span></p>
        </div>
        <div class="header-actions">
            <span class="role-badge <?= $role=='dosen'?'role-dosen':'role-mahasiswa' ?>">
                <?= $role=='dosen'?'👨‍🏫 Dosen':'🎓 Mahasiswa' ?>
            </span>
            <span class="room-badge">🔑 <?= htmlspecialchars($room_code) ?></span>
            <a href="logout.php"><button class="logout-btn">Keluar Akun</button></a>
        </div>
    </header>

    <?php if($added):   ?><div class="alert alert-success">✅ Tugas berhasil ditambahkan!</div><?php endif; ?>
    <?php if($uploaded): ?><div class="alert alert-success">✅ Tugas berhasil dikumpulkan!</div><?php endif; ?>

    <!-- Stats -->
    <div class="stats-container">
        <div class="stats-card">
            <span class="stats-count"><?= $total_tasks ?></span>
            <span class="stats-label">Total Tugas</span>
        </div>
        <?php if($role=='dosen'): ?>
        <div class="stats-card">
            <span class="stats-count green"><?= $total_sub ?></span>
            <span class="stats-label">Total Pengumpulan</span>
        </div>
        <div class="stats-card">
            <span class="stats-count orange"><?= $total_mhs ?></span>
            <span class="stats-label">Mahasiswa Aktif</span>
        </div>
        <?php else: ?>
        <div class="stats-card">
            <span class="stats-count green"><?= $my_sub ?></span>
            <span class="stats-label">Sudah Dikumpul</span>
        </div>
        <div class="stats-card">
            <span class="stats-count red"><?= max(0,$total_tasks-$my_sub) ?></span>
            <span class="stats-label">Belum Dikumpul</span>
        </div>
        <?php endif; ?>
    </div>

    <?php if($role=='dosen'): ?>
    <!-- ═══ DOSEN ═══ -->
    <div class="grid-2">
        <!-- Tambah Tugas -->
        <div class="form-card">
            <h3>➕ Tambah Tugas Baru</h3>
            <form method="POST">
                <div class="input-row">
                    <input type="text" name="title" placeholder="Judul tugas..." required>
                    <textarea name="description" placeholder="Deskripsi / instruksi tugas (opsional)..."></textarea>
                </div>
                <button type="submit" name="add_task" class="submit-btn">Simpan Tugas</button>
            </form>
        </div>

        <!-- Daftar Tugas -->
        <div class="list-card">
            <h3>📋 Daftar Tugas (<?= count($tasks_list) ?>)</h3>
            <?php if(empty($tasks_list)): ?>
                <div class="empty-state">📭 Belum ada tugas dibuat.</div>
            <?php else: ?>
            <table>
                <thead><tr><th>#</th><th>Judul Tugas</th></tr></thead>
                <tbody>
                <?php foreach($tasks_list as $i=>$t): ?>
                <tr>
                    <td style="color:var(--muted);width:40px"><?= $i+1 ?></td>
                    <td>
                        <div style="font-weight:500"><?= htmlspecialchars($t['title']) ?></div>
                        <?php if(!empty($t['description'])): ?>
                        <div style="font-size:.78rem;color:var(--muted);margin-top:2px"><?= htmlspecialchars(substr($t['description'],0,65)) ?>...</div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pengumpulan -->
    <div class="list-card">
        <div class="list-header">
            <h3>📂 Daftar Pengumpulan Mahasiswa</h3>
            <span class="tag tag-purple"><?= count($submissions_list) ?> file</span>
        </div>
        <?php if(empty($submissions_list)): ?>
            <div class="empty-state">📭 Belum ada mahasiswa yang mengumpulkan tugas.</div>
        <?php else: ?>
        <table>
            <thead><tr><th>#</th><th>Mahasiswa</th><th>Tugas</th><th>Unduh</th></tr></thead>
            <tbody>
            <?php foreach($submissions_list as $i=>$sub): ?>
            <tr>
                <td style="color:var(--muted);width:40px"><?= $i+1 ?></td>
                <td>
                    <span class="avatar"><?= strtoupper(substr($sub['mhs_email'],0,1)) ?></span>
                    <?= htmlspecialchars($sub['mhs_email']) ?>
                </td>
                <td><span class="tag tag-cyan"><?= htmlspecialchars($sub['task_title']) ?></span></td>
                <td><a href="<?= htmlspecialchars($sub['file_path']) ?>" class="link-dl" download>⬇️ Unduh</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- ═══ MAHASISWA ═══ -->
    <div class="grid-2">

        <!-- Upload -->
        <div class="form-card">
            <h3>⬆️ Kumpulkan Tugas</h3>
            <?php if(empty($tasks_list)): ?>
                <div class="empty-state">📭 Belum ada tugas dari dosen.</div>
            <?php else: ?>
            <form method="POST" action="upload.php" enctype="multipart/form-data">
                <div class="input-row">
                    <div>
                        <label style="font-size:.8rem;color:var(--muted);display:block;margin-bottom:6px">Pilih Tugas</label>
                        <select name="task_id" required>
                            <?php foreach($tasks_list as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:.8rem;color:var(--muted);display:block;margin-bottom:6px">Upload File</label>
                        <div class="file-zone" onclick="document.getElementById('fileInput').click()">
                            <div style="font-size:2rem;margin-bottom:8px">📁</div>
                            <label class="file-zone-label">Klik untuk memilih file<br><small>PDF, DOC, DOCX, ZIP — Maks. 10MB</small></label>
                            <div class="file-zone-name" id="fName"></div>
                            <input type="file" id="fileInput" name="file" accept=".pdf,.doc,.docx,.zip" required
                                   onchange="document.getElementById('fName').style.display='block';document.getElementById('fName').textContent=this.files[0].name">
                        </div>
                    </div>
                </div>
                <button type="submit" class="submit-btn">📤 Kirim Tugas</button>
            </form>
            <?php endif; ?>
        </div>

        <!-- Riwayat -->
        <div class="list-card">
            <h3>📚 Riwayat Pengumpulan</h3>
            <?php if(empty($submissions_list)): ?>
                <div class="empty-state">📭 Kamu belum mengumpulkan tugas apapun.</div>
            <?php else: ?>
            <table>
                <thead><tr><th>#</th><th>Tugas</th><th>File</th></tr></thead>
                <tbody>
                <?php foreach($submissions_list as $i=>$sub): ?>
                <tr>
                    <td style="color:var(--muted);width:40px"><?= $i+1 ?></td>
                    <td><span class="tag tag-cyan"><?= htmlspecialchars($sub['task_title']) ?></span></td>
                    <td><a href="<?= htmlspecialchars($sub['file_path']) ?>" class="link-dl" download>⬇️ Unduh</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Semua Tugas -->
    <div class="list-card">
        <div class="list-header">
            <h3>📋 Semua Tugas di Kelas</h3>
            <div class="filter-tabs">
                <button class="filter-btn active" id="fAll"  onclick="filterRows('all')">Semua</button>
                <button class="filter-btn" id="fDone"        onclick="filterRows('done')">Selesai</button>
                <button class="filter-btn" id="fPending"     onclick="filterRows('pending')">Belum</button>
            </div>
        </div>
        <?php if(empty($tasks_list)): ?>
            <div class="empty-state">📭 Belum ada tugas dari dosen.</div>
        <?php else: ?>
        <?php $submitted_ids = array_column($submissions_list,'task_id'); ?>
        <table id="taskTable">
            <thead><tr><th>#</th><th>Judul Tugas</th><th>Deskripsi</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach($tasks_list as $i=>$t):
                $done = in_array($t['id'],$submitted_ids); ?>
            <tr data-status="<?= $done?'done':'pending' ?>">
                <td style="color:var(--muted);width:40px"><?= $i+1 ?></td>
                <td style="font-weight:500"><?= htmlspecialchars($t['title']) ?></td>
                <td style="color:var(--muted);font-size:.82rem"><?= !empty($t['description'])?htmlspecialchars(substr($t['description'],0,70)).'...':'—' ?></td>
                <td><?php if($done): ?><span class="tag tag-green">✅ Sudah Kumpul</span><?php else: ?><span class="tag tag-orange">⏳ Belum</span><?php endif; ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div><!-- /wrapper -->

<script>
function showToast(icon,msg,dur=3000){
    document.getElementById('toastIcon').textContent=icon;
    document.getElementById('toastMsg').textContent=msg;
    var t=document.getElementById('toast'); t.classList.add('show');
    setTimeout(()=>t.classList.remove('show'),dur);
}
function filterRows(f){
    ['All','Done','Pending'].forEach(x=>document.getElementById('f'+x).classList.remove('active'));
    document.getElementById('f'+f.charAt(0).toUpperCase()+f.slice(1)).classList.add('active');
    document.querySelectorAll('#taskTable tbody tr').forEach(r=>{
        r.style.display=(f==='all'||r.dataset.status===f)?'':'none';
    });
}
window.onload=function(){
    <?php if($added):   ?>showToast('✅','Tugas berhasil ditambahkan!');<?php endif; ?>
    <?php if($uploaded):?>showToast('📤','Tugas berhasil dikumpulkan!');<?php endif; ?>
    <?php if(!$added && !$uploaded): ?>showToast('🚀','Selamat datang kembali, <?= addslashes($nickname) ?>!');<?php endif; ?>
};
</script>
</body>
</html>
