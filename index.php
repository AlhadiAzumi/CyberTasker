<?php
session_start();
include 'koneksi.php';
if (isset($_SESSION['user_id'])) { header("Location: dashboard.php"); exit(); }
$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['email']     = $user['email'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['room_code'] = $user['room_code'];
        header("Location: dashboard.php"); exit();
    } else {
        $error = "Email atau password salah.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Cyber-Tasker</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%230f172a'/%3E%3Crect x='6' y='6' width='52' height='52' rx='10' fill='none' stroke='%2300f2fe' stroke-width='2' opacity='0.4'/%3E%3Crect x='18' y='20' width='28' height='4' rx='2' fill='%2300f2fe'/%3E%3Crect x='18' y='30' width='20' height='4' rx='2' fill='%234facfe' opacity='0.8'/%3E%3Crect x='18' y='40' width='24' height='4' rx='2' fill='%234facfe' opacity='0.5'/%3E%3Ccircle cx='48' cy='44' r='10' fill='%230f172a'/%3E%3Ccircle cx='48' cy='44' r='9' fill='%2300f2fe'/%3E%3Ctext x='48' y='49' text-anchor='middle' font-size='13' font-weight='700' font-family='Arial' fill='%230f172a'%3E%E2%9C%93%3C/text%3E%3C/svg%3E">
<style>
:root { --primary:#00f2fe; --secondary:#4facfe; --bg-dark:#0a0c16; --card-bg:rgba(13,18,36,0.65); --text:#e2e8f0; }
*,*::before,*::after { box-sizing:border-box; margin:0; padding:0; font-family:'Poppins',sans-serif; }
body { background:radial-gradient(circle at center,#1a1f3c 0%,var(--bg-dark) 100%); min-height:100vh; display:flex; justify-content:center; align-items:center; overflow:hidden; position:relative; color:var(--text); }
body::before,body::after { content:''; position:absolute; width:300px; height:300px; border-radius:50%; background:linear-gradient(45deg,var(--primary),var(--secondary)); filter:blur(100px); opacity:0.2; z-index:0; }
body::before { top:-50px; left:-50px; } body::after { bottom:-50px; right:-50px; }
.toast-container { position:fixed; top:-100px; left:50%; transform:translateX(-50%); background:#1e293b; border:1px solid var(--primary); box-shadow:0 0 20px rgba(0,242,254,0.3); padding:14px 28px; border-radius:12px; display:flex; align-items:center; gap:12px; z-index:9999; transition:top 0.5s cubic-bezier(0.68,-0.55,0.265,1.55); white-space:nowrap; }
.toast-container.show { top:30px; }
.toast-message { color:#fff; font-size:0.95rem; font-weight:500; }
.card { background:var(--card-bg); backdrop-filter:blur(12px); -webkit-backdrop-filter:blur(12px); padding:40px; border-radius:20px; width:100%; max-width:400px; box-shadow:0 15px 35px rgba(0,0,0,.5),0 0 15px rgba(0,242,254,.1); border:1px solid rgba(0,242,254,0.2); z-index:1; text-align:center; transition:box-shadow 0.3s; }
.card:hover { box-shadow:0 20px 40px rgba(0,0,0,.6),0 0 25px rgba(0,242,254,.2); }
.brand { display:flex; align-items:center; justify-content:center; gap:10px; margin-bottom:28px; }
.brand-icon { width:40px; height:40px; flex-shrink:0; }
h2 { font-size:2rem; letter-spacing:2px; background:linear-gradient(45deg,#fff,var(--primary)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; text-transform:uppercase; font-weight:700; line-height:1; }
.input-group { margin-bottom:20px; text-align:left; }
input[type="email"],input[type="password"],input[type="text"] { width:100%; padding:14px 16px; background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.1); outline:none; border-radius:10px; color:#fff; font-size:1rem; transition:all .3s; }
input:focus { background:rgba(255,255,255,.08); border-color:var(--primary); box-shadow:0 0 10px rgba(0,242,254,.3); }
input::placeholder { color:#64748b; }
.error-box { background:rgba(239,68,68,.12); border:1px solid rgba(239,68,68,.3); border-radius:10px; padding:12px 16px; font-size:0.88rem; color:#fca5a5; margin-bottom:18px; text-align:left; }
.checkbox-container { display:flex; align-items:center; gap:8px; margin:15px 0 25px; cursor:pointer; font-size:0.9rem; color:#a0aec0; user-select:none; }
.checkbox-container input { cursor:pointer; accent-color:var(--primary); }
.btn-main { width:100%; padding:14px; background:linear-gradient(45deg,var(--secondary),var(--primary)); border:none; outline:none; border-radius:10px; color:#000; font-size:1rem; font-weight:700; letter-spacing:1px; cursor:pointer; text-transform:uppercase; transition:all .3s; box-shadow:0 4px 15px rgba(0,242,254,.3); }
.btn-main:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,242,254,.5); filter:brightness(1.1); }
.btn-main:active { transform:translateY(1px); }
p.foot { margin-top:25px; font-size:0.9rem; color:#a0aec0; }
a { color:var(--primary); text-decoration:none; font-weight:600; transition:.3s; }
a:hover { color:#fff; text-shadow:0 0 8px var(--primary); }
</style>
</head>
<body>
<div id="toast" class="toast-container">
    <span id="toastIcon">🚀</span>
    <span class="toast-message" id="toastMsg">Selamat datang!</span>
</div>
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
        <h2>Login</h2>
    </div>
    <?php if ($error): ?>
    <div class="error-box">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="input-group">
            <input type="email" name="email" placeholder="Email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="input-group">
            <input type="password" name="password" id="pwdField" placeholder="Password" required>
        </div>
        <label class="checkbox-container">
            <input type="checkbox" onchange="document.getElementById('pwdField').type=this.checked?'text':'password'">
            <span>Tampilkan Password</span>
        </label>
        <button type="submit" class="btn-main">Sign In</button>
    </form>
    <p class="foot">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
</div>
<script>
function showToast(icon,msg){document.getElementById('toastIcon').textContent=icon;document.getElementById('toastMsg').textContent=msg;var t=document.getElementById('toast');t.classList.add('show');setTimeout(()=>t.classList.remove('show'),2800);}
<?php if($error): ?>window.onload=()=>showToast('❌','<?= addslashes($error) ?>');<?php endif; ?>
</script>
</body>
</html>
