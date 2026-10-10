<?php
session_start();
include 'koneksi.php';
if (isset($_SESSION['user_id'])) { header("Location: dashboard.php"); exit(); }
$error = ""; $success = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $role     = $_POST['role'];
    $room_code= trim($_POST['room_code']);
    if (strlen($password) < 8)              { $error = "Password minimal 8 karakter."; }
    elseif (!preg_match('/[a-zA-Z]/',$password)||!preg_match('/[0-9]/',$password)) { $error = "Password harus kombinasi huruf dan angka!"; }
    elseif ($password !== $confirm)         { $error = "Konfirmasi password tidak cocok."; }
    elseif (!in_array($role,['mahasiswa','dosen'])) { $error = "Role tidak valid."; }
    else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt   = $conn->prepare("INSERT INTO users (email, password, role, room_code) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $email, $hashed, $role, $room_code);
        if ($stmt->execute()) { $success = "Pendaftaran berhasil!"; }
        else                  { $error   = "Email sudah terdaftar atau terjadi kesalahan."; }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Akun — Cyber-Tasker</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%230f172a'/%3E%3Crect x='6' y='6' width='52' height='52' rx='10' fill='none' stroke='%2300f2fe' stroke-width='2' opacity='0.4'/%3E%3Crect x='18' y='20' width='28' height='4' rx='2' fill='%2300f2fe'/%3E%3Crect x='18' y='30' width='20' height='4' rx='2' fill='%234facfe' opacity='0.8'/%3E%3Crect x='18' y='40' width='24' height='4' rx='2' fill='%234facfe' opacity='0.5'/%3E%3Ccircle cx='48' cy='44' r='10' fill='%230f172a'/%3E%3Ccircle cx='48' cy='44' r='9' fill='%2300f2fe'/%3E%3Ctext x='48' y='49' text-anchor='middle' font-size='13' font-weight='700' font-family='Arial' fill='%230f172a'%3E%E2%9C%93%3C/text%3E%3C/svg%3E">
<style>
:root { --primary:#ff007f; --secondary:#7928ca; --bg-dark:#0a0c16; --card-bg:rgba(18,13,36,0.65); --text:#e2e8f0; }
*,*::before,*::after { box-sizing:border-box; margin:0; padding:0; font-family:'Poppins',sans-serif; }
body { background:radial-gradient(circle at center,#25163c 0%,var(--bg-dark) 100%); min-height:100vh; display:flex; justify-content:center; align-items:center; overflow:hidden; position:relative; color:var(--text); padding:24px; }
body::before,body::after { content:''; position:absolute; width:300px; height:300px; border-radius:50%; background:linear-gradient(45deg,var(--primary),var(--secondary)); filter:blur(100px); opacity:0.15; z-index:0; }
body::before { top:-50px; right:-50px; } body::after { bottom:-50px; left:-50px; }
.card { background:var(--card-bg); backdrop-filter:blur(12px); -webkit-backdrop-filter:blur(12px); padding:40px; border-radius:20px; width:100%; max-width:440px; box-shadow:0 15px 35px rgba(0,0,0,.5),0 0 15px rgba(255,0,127,.05); border:1px solid rgba(255,0,127,0.2); z-index:1; text-align:center; transition:box-shadow 0.3s; }
.card:hover { box-shadow:0 20px 40px rgba(0,0,0,.6),0 0 25px rgba(255,0,127,.15); }
.brand { display:flex; align-items:center; justify-content:center; gap:10px; margin-bottom:28px; }
.brand-icon { width:40px; height:40px; flex-shrink:0; }
h2 { font-size:1.8rem; letter-spacing:2px; background:linear-gradient(45deg,#fff,var(--primary)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; text-transform:uppercase; font-weight:700; line-height:1; }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.input-group { margin-bottom:18px; text-align:left; }
input[type="email"],input[type="password"],input[type="text"],select { width:100%; padding:14px 16px; background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.1); outline:none; border-radius:10px; color:#fff; font-size:1rem; transition:all .3s; font-family:'Poppins',sans-serif; }
input:focus,select:focus { background:rgba(255,255,255,.08); border-color:var(--primary); box-shadow:0 0 10px rgba(255,0,127,.3); }
input::placeholder { color:#64748b; }
input.is-invalid { border-color:#ff4d4d!important; box-shadow:0 0 10px rgba(255,77,77,.25)!important; }
input.is-valid   { border-color:#00ff88!important; box-shadow:0 0 10px rgba(0,255,136,.25)!important; }
select option { background:#120d24; color:#fff; }
.validation-note { font-size:0.75rem; color:#ff4d4d; margin-top:4px; display:none; text-align:left; padding-left:4px; }
.validation-note.show { display:block; }
.alert { border-radius:10px; padding:12px 16px; font-size:0.88rem; margin-bottom:18px; text-align:left; }
.alert-error   { background:rgba(239,68,68,.12);  border:1px solid rgba(239,68,68,.3);  color:#fca5a5; }
.alert-success { background:rgba(0,255,136,.10);  border:1px solid rgba(0,255,136,.3);  color:#6ee7b7; }
.checkbox-container { display:flex; align-items:center; gap:8px; margin:10px 0 22px; cursor:pointer; font-size:0.9rem; color:#a0aec0; user-select:none; text-align:left; }
.checkbox-container input { cursor:pointer; accent-color:var(--primary); }
.btn-main { width:100%; padding:14px; background:linear-gradient(45deg,var(--secondary),var(--primary)); border:none; outline:none; border-radius:10px; color:#fff; font-size:1rem; font-weight:700; letter-spacing:1px; cursor:pointer; text-transform:uppercase; transition:all .3s; box-shadow:0 4px 15px rgba(255,0,127,.3); }
.btn-main:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(255,0,127,.5); filter:brightness(1.1); }
.btn-main:active { transform:translateY(1px); }
p.foot { margin-top:25px; font-size:0.9rem; color:#a0aec0; }
a { color:var(--primary); text-decoration:none; font-weight:600; transition:.3s; }
a:hover { color:#fff; text-shadow:0 0 8px var(--primary); }
</style>
</head>
<body>
<div class="card">
    <div class="brand">
        <svg class="brand-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
            <rect width="64" height="64" rx="14" fill="#0f172a"/>
            <rect x="6" y="6" width="52" height="52" rx="10" fill="none" stroke="#00f2fe" stroke-width="2" opacity="0.4"/>
            <rect x="18" y="20" width="28" height="4" rx="2" fill="#00f2fe"/>
            <rect x="18" y="30" width="20" height="4" rx="2" fill="#4facfe" opacity="0.8"/>
            <rect x="18" y="40" width="24" height="4" rx="2" fill="#4facfe" opacity="0.5"/>
            <circle cx="48" cy="44" r="10" fill="#0f172a"/>
            <circle cx="48" cy="44" r="9" fill="#00f2fe"/>
            <text x="48" y="49" text-anchor="middle" font-size="13" font-weight="700" font-family="Arial" fill="#0f172a">✓</text>
        </svg>
        <h2>Daftar Akun</h2>
    </div>

    <?php if ($error):   ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?><br><a href="index.php" style="color:inherit;font-weight:700;">Login sekarang →</a></div>
    <?php else: ?>
    <form method="POST" id="regForm">
        <div class="input-group">
            <input type="email" name="email" id="regEmail" placeholder="Email" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" oninput="validateEmail()">
            <span class="validation-note" id="emailNote">Format email harus benar (contoh: nama@email.com)</span>
        </div>
        <div class="input-group">
            <input type="password" name="password" id="regPwd" placeholder="Password (min. 8 karakter, huruf+angka)" required>
        </div>
        <div class="input-group">
            <input type="password" name="confirm_password" id="regConfirm" placeholder="Konfirmasi Password" required>
        </div>
        <div class="form-row">
            <div class="input-group" style="margin-bottom:0">
                <select name="role">
                    <option value="mahasiswa" <?= ($_POST['role']??'')=='mahasiswa'?'selected':'' ?>>🎓 Mahasiswa</option>
                    <option value="dosen"     <?= ($_POST['role']??'')=='dosen'    ?'selected':'' ?>>👨‍🏫 Dosen</option>
                </select>
            </div>
            <div class="input-group" style="margin-bottom:0">
                <input type="text" name="room_code" placeholder="Kode Kelas" required
                       value="<?= htmlspecialchars($_POST['room_code'] ?? '') ?>">
            </div>
        </div>
        <label class="checkbox-container" style="margin-top:18px">
            <input type="checkbox" id="showPwds" onchange="togglePasswords(this.checked)">
            <span>Tampilkan Password</span>
        </label>
        <button type="button" class="btn-main" onclick="submitForm()">Sign Up</button>
    </form>
    <?php endif; ?>
    <p class="foot">Sudah punya akun? <a href="index.php">Login di sini</a></p>
</div>
<script>
function validateEmail(){
    const el=document.getElementById('regEmail'), v=el.value.trim(), note=document.getElementById('emailNote');
    const ok=/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
    if(!v){el.classList.remove('is-valid','is-invalid');note.classList.remove('show');return false;}
    if(ok){el.classList.add('is-valid');el.classList.remove('is-invalid');note.classList.remove('show');return true;}
    else{el.classList.add('is-invalid');el.classList.remove('is-valid');note.classList.add('show');return false;}
}
function togglePasswords(show){
    document.getElementById('regPwd').type    = show?'text':'password';
    document.getElementById('regConfirm').type= show?'text':'password';
}
function submitForm(){
    const pwd=document.getElementById('regPwd').value, conf=document.getElementById('regConfirm').value;
    if(!validateEmail())return;
    if(pwd.length<8){alert('Password minimal 8 karakter!');return;}
    if(!/[a-zA-Z]/.test(pwd)||!/[0-9]/.test(pwd)){alert('Password harus kombinasi huruf dan angka!');return;}
    if(pwd!==conf){alert('Konfirmasi password tidak cocok!');return;}
    document.getElementById('regForm').submit();
}
</script>
</body>
</html>
