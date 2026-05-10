<?php
// ============================================================
//  PORTFOLIO DASHBOARD v2 — Muhammad Fahmi Al Kahfi
//  GANTI PASSWORD sebelum upload ke hosting!
// ============================================================
define('DASHBOARD_PASSWORD', 'fahmi2025');
define('DATA_FILE',   __DIR__ . '/../data/portfolio.json');
define('UPLOAD_DIR',  __DIR__ . '/../uploads/');
define('SESSION_KEY', 'pf_admin_logged_in');

session_start();

/* ─── Session timeout: auto-logout setelah 60 menit tidak aktif ─── */
define('SESSION_TIMEOUT', 3600);
if (!empty($_SESSION[SESSION_KEY])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        /* Session expired — hapus dan bersihkan */
        session_unset();
        session_destroy();
    } else {
        /* Masih aktif — perbarui timestamp */
        $_SESSION['last_activity'] = time();
    }
}

if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0755, true);

/* ─── POST handlers ─────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        if ($_POST['password'] === DASHBOARD_PASSWORD) {
            $_SESSION[SESSION_KEY] = true;
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false, 'msg' => 'Password salah.']);
        }
        exit;
    }

    if ($action === 'logout') { session_destroy(); echo json_encode(['ok'=>true]); exit; }

    if ($action === 'save') {
        if (empty($_SESSION[SESSION_KEY])) { http_response_code(403); exit; }
        $decoded = json_decode($_POST['data'] ?? '', true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['ok'=>false,'msg'=>'JSON tidak valid: '.json_last_error_msg()]); exit;
        }
        $bytes = file_put_contents(DATA_FILE, json_encode($decoded, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        echo $bytes===false
            ? json_encode(['ok'=>false,'msg'=>'Gagal menyimpan. Pastikan folder data/ bisa ditulis (klik kanan → Properties → uncheck Read-only di XAMPP).'])
            : json_encode(['ok'=>true,'bytes'=>$bytes]);
        exit;
    }

    if ($action === 'upload') {
        if (empty($_SESSION[SESSION_KEY])) { http_response_code(403); exit; }
        $type = $_POST['type'] ?? 'image';
        $file = $_FILES['file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $em = [1=>'File terlalu besar (PHP limit)',2=>'File terlalu besar',3=>'Upload tidak lengkap',4=>'Tidak ada file dipilih',6=>'Tidak ada folder temp',7=>'Gagal tulis ke disk'];
            echo json_encode(['ok'=>false,'msg'=>$em[$file['error']??0]??'Upload gagal']); exit;
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($type==='pdf' && $ext!=='pdf') { echo json_encode(['ok'=>false,'msg'=>'Hanya PDF yang diizinkan']); exit; }
        if ($type!=='pdf' && !in_array($ext,['jpg','jpeg','png','gif','webp'])) { echo json_encode(['ok'=>false,'msg'=>'Hanya gambar JPG/PNG/WebP yang diizinkan']); exit; }
        if ($file['size'] > 10*1024*1024) { echo json_encode(['ok'=>false,'msg'=>'File terlalu besar (maks 10MB)']); exit; }
        $filename = $type.'_'.time().'_'.substr(md5(rand()),0,6).'.'.$ext;
        if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR.$filename)) {
            echo json_encode(['ok'=>true,'url'=>'uploads/'.$filename,'filename'=>$filename]);
        } else {
            echo json_encode(['ok'=>false,'msg'=>'Gagal menyimpan file. Cek izin folder uploads/.']);
        }
        exit;
    }

    if ($action === 'change_password') {
        if (empty($_SESSION[SESSION_KEY])) { http_response_code(403); exit; }
        $new = trim($_POST['new_password']??'');
        if (strlen($new)<6) { echo json_encode(['ok'=>false,'msg'=>'Minimal 6 karakter']); exit; }
        $content = preg_replace("/define\('DASHBOARD_PASSWORD',\s*'[^']*'\)/",
            "define('DASHBOARD_PASSWORD', '".addslashes($new)."')", file_get_contents(__FILE__));
        echo file_put_contents(__FILE__,$content)!==false
            ? json_encode(['ok'=>true]) : json_encode(['ok'=>false,'msg'=>'Tidak bisa update password.']);
        exit;
    }
}

$loggedIn = !empty($_SESSION[SESSION_KEY]);
$portfolioData = file_exists(DATA_FILE) ? (json_decode(file_get_contents(DATA_FILE),true)??[]) : [];
$json = json_encode($portfolioData, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE);
$sys = [
    'writable'  => is_writable(DATA_FILE)||is_writable(dirname(DATA_FILE)),
    'uploads'   => is_writable(UPLOAD_DIR),
    'php'       => phpversion(),
    'uploadMax' => ini_get('upload_max_filesize'),
    'postMax'   => ini_get('post_max_size'),
    'dataPath'  => realpath(DATA_FILE)
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Portfolio Dashboard — Fahmi</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --navy:#0B1929;--navy2:#0F2744;--navy3:#112D4E;
  --teal:#14B8A6;--teal-d:#0D9488;--teal-pale:#CCFBF1;--teal-faint:rgba(20,184,166,.08);
  --white:#fff;
  --s900:#0F172A;--s800:#1E293B;--s700:#334155;--s600:#475569;--s400:#94A3B8;--s300:#CBD5E1;--s200:#E2E8F0;--s100:#F1F5F9;--s50:#F8FAFC;
  --danger:#EF4444;--danger-pale:#FEE2E2;
  --warn-bg:#FFFBEB;--warn-bd:#FCD34D;--warn-txt:#92400E;
  --ok-bg:#F0FDF4;--ok-bd:#86EFAC;--ok-txt:#15803D;
  --shadow-sm:0 1px 3px rgba(0,0,0,.07),0 1px 2px rgba(0,0,0,.04);
  --shadow:0 4px 16px rgba(0,0,0,.06),0 1px 4px rgba(0,0,0,.04);
  --radius:14px;--radius-sm:10px;
}
html{font-size:14px}
body{font-family:'Inter',-apple-system,sans-serif;background:var(--s100);color:var(--s800);min-height:100vh}
a{text-decoration:none;color:inherit}
input,textarea,select{font-family:inherit;font-size:.88rem}

/* ─── LOGIN ─────────────────────────────────────────────── */
.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--navy) 0%,#0A4044 100%)}
.login-card{background:var(--white);border-radius:20px;padding:2.5rem 2rem;width:100%;max-width:380px;box-shadow:0 24px 60px rgba(0,0,0,.25)}
.login-logo{text-align:center;margin-bottom:2rem}
.logo-circle{width:54px;height:54px;background:var(--teal);border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem}
.login-card h1{font-size:1.1rem;font-weight:700;color:var(--s900)}
.login-card .sub{font-size:.78rem;color:var(--s400);margin-top:3px}
.login-err{color:var(--danger);font-size:.8rem;background:var(--danger-pale);border-radius:8px;padding:.5rem .75rem;margin-bottom:.75rem;display:none}
.login-card input[type=password]{width:100%;padding:.75rem 1rem;border:1.5px solid var(--s200);border-radius:var(--radius-sm);outline:none;font-size:.9rem;margin-bottom:.75rem;transition:border .2s,box-shadow .2s}
.login-card input:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(20,184,166,.12)}
.login-btn{width:100%;padding:.8rem;background:var(--teal);color:var(--white);border:none;border-radius:var(--radius-sm);font-weight:600;font-size:.95rem;cursor:pointer;transition:background .2s;letter-spacing:.01em}
.login-btn:hover{background:var(--teal-d)}

/* ─── SHELL ──────────────────────────────────────────────── */
.shell{display:flex;min-height:100vh}

/* ─── SIDEBAR ────────────────────────────────────────────── */
.sidebar{width:230px;background:var(--navy);flex-shrink:0;display:flex;flex-direction:column;position:sticky;top:0;height:100vh;overflow-y:auto}
.sidebar-brand{padding:1.5rem 1.25rem .875rem}
.brand-row{display:flex;align-items:center;gap:.75rem}
.brand-icon{width:34px;height:34px;background:var(--teal);border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.brand-text h2{color:var(--white);font-size:.82rem;font-weight:700;line-height:1.25}
.brand-text p{color:var(--s400);font-size:.68rem;margin-top:1px}
.sidebar-user{padding:.875rem 1.25rem;border-top:1px solid rgba(255,255,255,.06);border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;gap:.75rem}
.user-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--teal),#0A4044);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:var(--white);flex-shrink:0}
.user-info .uname{color:var(--white);font-size:.78rem;font-weight:600}
.user-info .urole{color:var(--s400);font-size:.68rem}
.sidebar-nav{padding:.75rem 0;flex:1}
.nav-section{padding:.35rem 1.25rem;font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--s600);margin-top:.4rem}
.nav-btn{display:flex;align-items:center;gap:.6rem;padding:.58rem 1.25rem;width:100%;background:none;border:none;color:var(--s400);font-size:.8rem;cursor:pointer;transition:all .15s;text-align:left;position:relative}
.nav-btn:hover{color:var(--white);background:rgba(255,255,255,.05)}
.nav-btn.active{color:var(--teal);background:var(--teal-faint)}
.nav-btn.active::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--teal);border-radius:0 2px 2px 0}
.nav-btn svg{width:15px;height:15px;flex-shrink:0}
.sidebar-footer{padding:1rem 1.25rem;border-top:1px solid rgba(255,255,255,.06);display:flex;flex-direction:column;gap:.3rem}
.preview-link{display:flex;align-items:center;gap:.45rem;padding:.48rem .75rem;color:var(--s400);font-size:.77rem;border-radius:8px;transition:all .15s}
.preview-link:hover{background:rgba(255,255,255,.05);color:var(--white)}
.logout-btn{display:flex;align-items:center;gap:.45rem;padding:.48rem .75rem;background:none;border:none;color:var(--s400);font-size:.77rem;cursor:pointer;width:100%;border-radius:8px;transition:all .15s;text-align:left}
.logout-btn:hover{background:rgba(239,68,68,.1);color:#FCA5A5}

/* ─── MAIN ───────────────────────────────────────────────── */
.main{flex:1;overflow-y:auto;display:flex;flex-direction:column}
.topbar{background:var(--white);border-bottom:1px solid var(--s200);padding:.875rem 1.75rem;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:100;box-shadow:var(--shadow-sm)}
.topbar h3{font-size:.93rem;font-weight:700;color:var(--s900)}
.topbar-right{display:flex;align-items:center;gap:1rem}
.save-status{font-size:.77rem;font-weight:500;color:var(--s400)}
.save-status.ok{color:var(--teal-d)}
.save-status.err{color:var(--danger)}
.save-btn{display:flex;align-items:center;gap:.45rem;background:var(--teal);color:var(--white);border:none;padding:.55rem 1.25rem;border-radius:20px;font-size:.82rem;font-weight:600;cursor:pointer;transition:background .2s}
.save-btn:hover{background:var(--teal-d)}
.save-btn:disabled{opacity:.6;cursor:not-allowed}
.save-btn svg{width:14px;height:14px}

/* ─── CONTENT ────────────────────────────────────────────── */
.content{padding:1.75rem;display:none;max-width:880px}
.content.active{display:block}

/* ─── CARDS ──────────────────────────────────────────────── */
.db-card{background:var(--white);border:1px solid var(--s200);border-radius:var(--radius);padding:1.5rem;margin-bottom:1.25rem;box-shadow:var(--shadow-sm)}
.db-card-title{font-size:.82rem;font-weight:700;color:var(--s900);margin-bottom:1.25rem;display:flex;align-items:center;gap:.5rem;padding-bottom:.875rem;border-bottom:1px solid var(--s100)}
.db-card-title svg{width:16px;height:16px;color:var(--teal)}

/* ─── FORMS ──────────────────────────────────────────────── */
.field{margin-bottom:1rem}
.field label{display:block;font-size:.7rem;font-weight:700;color:var(--s600);margin-bottom:.35rem;text-transform:uppercase;letter-spacing:.05em}
.field input,.field textarea,.field select{width:100%;padding:.62rem .9rem;border:1.5px solid var(--s200);border-radius:var(--radius-sm);outline:none;color:var(--s900);transition:border .2s,box-shadow .2s;background:var(--white);line-height:1.5}
.field input:focus,.field textarea:focus{border-color:var(--teal);box-shadow:0 0 0 3px rgba(20,184,166,.1)}
.field textarea{resize:vertical;line-height:1.65}
.field-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:0}
.field-row .field{margin-bottom:0}
.field-row+.field,.field-row+.field-row{margin-top:1rem}

/* ─── UPLOAD ─────────────────────────────────────────────── */
.upload-row{display:flex;align-items:flex-start;gap:1.5rem;padding:1rem;background:var(--s50);border-radius:var(--radius-sm);border:1px solid var(--s200)}
.upload-box{width:108px;height:108px;border-radius:10px;border:2px dashed var(--s300);display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;background:var(--white);position:relative}
.upload-box img{width:100%;height:100%;object-fit:cover}
.upload-box .no-img{display:flex;flex-direction:column;align-items:center;gap:.35rem;color:var(--s400)}
.upload-box .no-img svg{width:28px;height:28px}
.upload-box .no-img span{font-size:.65rem;text-align:center}
.pdf-box{background:var(--s50);border:2px dashed var(--s300);border-radius:10px;width:108px;height:108px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.4rem;flex-shrink:0}
.pdf-box svg{width:26px;height:26px;color:#EF4444}
.pdf-box .pdf-name{font-size:.65rem;color:var(--s600);text-align:center;padding:0 .4rem;word-break:break-all;line-height:1.3}
.upload-details{flex:1;min-width:0}
.upload-hint{font-size:.79rem;color:var(--s600);line-height:1.65;margin-bottom:.75rem}
.upload-actions{display:flex;gap:.5rem;flex-wrap:wrap}
.upload-btn{display:flex;align-items:center;gap:.35rem;background:var(--teal);color:var(--white);border:none;padding:.48rem 1rem;border-radius:20px;font-size:.77rem;font-weight:600;cursor:pointer;transition:background .2s}
.upload-btn:hover{background:var(--teal-d)}
.upload-btn svg{width:13px;height:13px}
.clear-btn{background:var(--s100);color:var(--s600);border:none;padding:.48rem .875rem;border-radius:20px;font-size:.77rem;font-weight:600;cursor:pointer;transition:all .2s}
.clear-btn:hover{background:var(--danger-pale);color:var(--danger)}

/* ─── MULTI-IMAGE WIDGET ──────────────────────────────────── */
.multi-img-widget{margin-top:.75rem}
.multi-img-widget>label{display:block;font-size:.79rem;font-weight:600;color:var(--s700);margin-bottom:.5rem}
.multi-img-grid{display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:.5rem;min-height:0}
.multi-img-item{position:relative;width:72px;height:72px;border-radius:8px;overflow:hidden;border:1.5px solid var(--s200);background:var(--s100);flex-shrink:0}
.multi-img-item img{width:100%;height:100%;object-fit:cover}
.multi-img-del{position:absolute;top:2px;right:2px;width:18px;height:18px;border-radius:50%;background:rgba(0,0,0,.55);color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:12px;line-height:1;padding:0}
.multi-img-del:hover{background:var(--danger)}
.multi-img-add{display:inline-flex;align-items:center;gap:.3rem;background:var(--s100);color:var(--s600);border:1.5px dashed var(--s300);padding:.35rem .75rem;border-radius:20px;font-size:.76rem;font-weight:600;cursor:pointer;transition:all .2s}
.multi-img-add:hover{background:var(--teal-pale);color:var(--teal-d);border-color:var(--teal)}

/* ─── ARRAY ITEMS ────────────────────────────────────────── */
.array-item{background:var(--s50);border:1px solid var(--s200);border-radius:12px;padding:1rem 1.25rem;margin-bottom:.875rem}
.item-header{display:flex;justify-content:space-between;align-items:center;cursor:pointer;user-select:none}
.item-title{font-size:.85rem;font-weight:600;color:var(--s900)}
.item-actions{display:flex;gap:.35rem}
.del-btn{background:var(--danger-pale);color:var(--danger);border:none;border-radius:8px;width:28px;height:28px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s;flex-shrink:0}
.del-btn:hover{background:#FECACA}
.del-btn svg{width:13px;height:13px}
.toggle-btn{background:var(--s100);color:var(--s600);border:none;border-radius:8px;width:28px;height:28px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:11px}
.item-body{display:none;padding-top:.875rem}
.item-body.open{display:block}
.add-btn{display:flex;align-items:center;gap:.3rem;background:var(--teal);color:var(--white);border:none;padding:.45rem 1rem;border-radius:20px;font-size:.77rem;font-weight:600;cursor:pointer;margin-top:.75rem;transition:background .2s}
.add-btn:hover{background:var(--teal-d)}
.featured-toggle{display:inline-flex;align-items:center;gap:.4rem;margin-top:.75rem;font-size:.8rem;color:var(--s500);cursor:pointer;user-select:none}
.featured-toggle input{accent-color:var(--teal);width:14px;height:14px;cursor:pointer}

/* ─── TAGS ───────────────────────────────────────────────── */
.tags-wrap{display:flex;flex-wrap:wrap;gap:.4rem;padding:.45rem;border:1.5px solid var(--s200);border-radius:var(--radius-sm);min-height:42px;cursor:text;background:var(--white);transition:border .2s,box-shadow .2s}
.tags-wrap:focus-within{border-color:var(--teal);box-shadow:0 0 0 3px rgba(20,184,166,.1)}
.tag-chip{display:inline-flex;align-items:center;gap:.3rem;background:var(--teal-pale);color:var(--teal-d);padding:3px 10px;border-radius:20px;font-size:.77rem;font-weight:500}
.tag-chip button{background:none;border:none;cursor:pointer;color:var(--teal-d);font-size:1rem;line-height:1;padding:0}
.tag-input-inline{border:none;outline:none;font-size:.8rem;min-width:80px;color:var(--s900);background:transparent}

/* ─── PROFICIENCY ────────────────────────────────────────── */
.prof-wrap{display:flex;align-items:center;gap:.875rem}
.prof-wrap input[type=range]{flex:1;accent-color:var(--teal);height:4px}
.prof-val{font-size:.875rem;font-weight:700;color:var(--teal);min-width:40px;text-align:right}

/* ─── PROJECT THUMB ──────────────────────────────────────── */
.proj-thumb-wrap{display:flex;align-items:center;gap:.875rem;padding:.75rem;background:var(--s50);border-radius:8px;border:1px solid var(--s200);margin-bottom:.5rem}
.proj-thumb-box{width:80px;height:58px;border-radius:8px;border:1.5px dashed var(--s300);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;background:var(--white)}
.proj-thumb-box img{width:100%;height:100%;object-fit:cover}
.proj-thumb-box .no-img{color:var(--s400);font-size:.62rem;text-align:center;padding:2px}

/* ─── INFO BOXES ─────────────────────────────────────────── */
.info-box{background:var(--teal-pale);border:1px solid rgba(20,184,166,.25);border-radius:var(--radius-sm);padding:.875rem 1rem;font-size:.8rem;color:var(--teal-d);margin-bottom:1rem;line-height:1.7}
.warn-box{background:var(--warn-bg);border:1px solid var(--warn-bd);border-radius:var(--radius-sm);padding:.875rem 1rem;font-size:.8rem;color:var(--warn-txt);margin-bottom:1rem;line-height:1.7}
.ok-box{background:var(--ok-bg);border:1px solid var(--ok-bd);border-radius:var(--radius-sm);padding:.875rem 1rem;font-size:.8rem;color:var(--ok-txt);line-height:1.7}
.err-box{background:var(--danger-pale);border:1px solid #FCA5A5;border-radius:var(--radius-sm);padding:.875rem 1rem;font-size:.8rem;color:var(--danger);line-height:1.7}

/* ─── STATUS ─────────────────────────────────────────────── */
.stat-row{display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem;font-size:.8rem}
.stat-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.stat-ok{background:#22C55E}
.stat-fail{background:var(--danger)}
.stat-label{color:var(--s600)}
.stat-val{color:var(--s900);font-weight:600}

/* ─── PASSWORD ───────────────────────────────────────────── */
.pwd-row{display:flex;gap:.75rem;align-items:flex-end}
.pwd-row .field{flex:1;margin-bottom:0}
.pwd-btn{background:var(--s900);color:var(--white);border:none;padding:.65rem 1.25rem;border-radius:var(--radius-sm);font-size:.82rem;font-weight:600;cursor:pointer;white-space:nowrap;transition:background .15s}
.pwd-btn:hover{background:var(--navy2)}

/* ─── TOAST ──────────────────────────────────────────────── */
.toast-container{position:fixed;top:1.25rem;right:1.25rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem;pointer-events:none}
.toast{background:var(--white);border-radius:12px;padding:.875rem 1.25rem;box-shadow:0 8px 32px rgba(0,0,0,.13),0 2px 8px rgba(0,0,0,.08);display:flex;align-items:center;gap:.75rem;font-size:.84rem;font-weight:500;color:var(--s900);animation:toastIn .3s ease;border-left:4px solid var(--teal);max-width:340px;pointer-events:auto;transition:opacity .3s,transform .3s}
.toast.error{border-left-color:var(--danger)}
.toast.warning{border-left-color:#F59E0B}
.toast-icon{width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;flex-shrink:0;background:var(--teal-pale);color:var(--teal-d)}
.toast.error .toast-icon{background:var(--danger-pale);color:var(--danger)}
@keyframes toastIn{from{opacity:0;transform:translateX(110%)}to{opacity:1;transform:translateX(0)}}

/* ─── DIVIDER ────────────────────────────────────────────── */
.divider{height:1px;background:var(--s100);margin:1.25rem 0}

/* ─── RESPONSIVE ─────────────────────────────────────────── */
@media(max-width:768px){
  .sidebar{display:none}
  .field-row{grid-template-columns:1fr}
  .upload-row{flex-direction:column}
  .content{padding:1rem}
}
</style>
</head>
<body>

<?php if (!$loggedIn): ?>
<!-- ════ LOGIN ════════════════════════════════════════════ -->
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">
      <div class="logo-circle">
        <svg width="26" height="26" fill="none" stroke="#fff" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
      </div>
      <h1>Portfolio Dashboard</h1>
      <p class="sub">Muhammad Fahmi Al Kahfi</p>
    </div>
    <p class="login-err" id="login-err">Password salah. Coba lagi.</p>
    <input type="password" id="pwd-input" placeholder="Masukkan password" onkeydown="if(event.key==='Enter')doLogin()"/>
    <button class="login-btn" onclick="doLogin()">Masuk</button>
  </div>
</div>
<script>
function doLogin(){
  const pwd=document.getElementById('pwd-input').value;
  fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=login&password='+encodeURIComponent(pwd)})
    .then(r=>r.json()).then(d=>{
      if(d.ok){location.reload();}
      else{const e=document.getElementById('login-err');e.style.display='block';}
    });
}
</script>

<?php else: ?>
<!-- ════ DASHBOARD ════════════════════════════════════════ -->
<div class="toast-container" id="toast-container"></div>
<div class="shell">

<!-- ── Sidebar ─────────────────────────────────────────── -->
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-row">
      <div class="brand-icon">
        <svg width="18" height="18" fill="none" stroke="#fff" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
      </div>
      <div class="brand-text">
        <h2>Dashboard</h2>
        <p>Portfolio Manager</p>
      </div>
    </div>
  </div>

  <div class="sidebar-user">
    <div class="user-avatar">FA</div>
    <div class="user-info">
      <div class="uname">Fahmi Al Kahfi</div>
      <div class="urole">Administrator</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section">Konten</div>
    <button class="nav-btn active" onclick="showTab('hero')" id="tab-hero">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      Hero &amp; About
    </button>
    <button class="nav-btn" onclick="showTab('academic')" id="tab-academic">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 7v-7"/></svg>
      Academic
    </button>
    <button class="nav-btn" onclick="showTab('skills')" id="tab-skills">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
      Skills
    </button>
    <button class="nav-btn" onclick="showTab('experience')" id="tab-experience">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m8 0H8m8 0a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2"/></svg>
      Experiences
    </button>
    <button class="nav-btn" onclick="showTab('projects')" id="tab-projects">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      Projects
    </button>
    <button class="nav-btn" onclick="showTab('testimonials')" id="tab-testimonials">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
      Testimonials
    </button>
    <div class="nav-section">Pengaturan</div>
    <button class="nav-btn" onclick="showTab('labels')" id="tab-labels">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
      Label &amp; Teks UI
    </button>
    <button class="nav-btn" onclick="showTab('settings')" id="tab-settings">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      Settings
    </button>
  </nav>

  <div class="sidebar-footer">
    <a href="../index.html" target="_blank" class="preview-link">
      <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
      Preview Portfolio
    </a>
    <button class="logout-btn" onclick="doLogout()">
      <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
      Logout
    </button>
  </div>
</aside>

<!-- ── Main ─────────────────────────────────────────────── -->
<div class="main">
  <div class="topbar">
    <h3 id="topbar-title">Hero &amp; About</h3>
    <div class="topbar-right">
      <span class="save-status" id="save-status"></span>
      <button class="save-btn" onclick="saveAll()" id="save-btn">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
        Save Changes
      </button>
    </div>
  </div>

  <!-- ══ HERO & ABOUT ══════════════════════════════════════ -->
  <div class="content active" id="content-hero">
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        Identitas &amp; Hero
      </div>
      <div class="field-row">
        <div class="field"><label>Nama Lengkap</label><input type="text" id="p-name"/></div>
        <div class="field"><label>Hero Tagline (judul besar)</label><input type="text" id="p-tagline"/></div>
      </div>
      <div class="field" style="margin-top:1rem"><label>Subtitle / Peran (pisahkan dengan |)</label><input type="text" id="p-title" placeholder="Mechanical Engineer | Researcher | Problem Solver"/></div>
    </div>

    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        Foto Profil
      </div>
      <div class="upload-row">
        <div class="upload-box" id="photo-box">
          <div class="no-img">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span>Belum ada foto</span>
          </div>
        </div>
        <div class="upload-details">
          <p class="upload-hint">Upload foto profil untuk tampil di bagian Hero website.<br/>Format: JPG/PNG/WebP, maks 10MB. Ukuran ideal: 400×400px.</p>
          <div class="upload-actions">
            <button class="upload-btn" onclick="triggerUpload('photo','image','photo-box','p-photo')">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
              Upload Foto
            </button>
            <button class="clear-btn" onclick="clearUpload('p-photo','photo-box','image')">Hapus</button>
          </div>
          <input type="hidden" id="p-photo"/>
        </div>
      </div>
    </div>

    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Teks About Me
      </div>
      <div class="field"><label>Paragraf About</label><textarea id="p-about" rows="7"></textarea></div>
    </div>

    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        Kontak
      </div>
      <div class="field-row">
        <div class="field"><label>Email</label><input type="email" id="p-email"/></div>
        <div class="field"><label>Nomor HP / WhatsApp</label><input type="text" id="p-phone"/></div>
      </div>
      <div class="field-row" style="margin-top:1rem">
        <div class="field"><label>LinkedIn URL</label><input type="text" id="p-linkedin"/></div>
        <div class="field"><label>Lokasi (Kota, Negara)</label><input type="text" id="p-location"/></div>
      </div>
    </div>

    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        CV / Resume (PDF)
      </div>
      <div class="upload-row">
        <div class="pdf-box" id="cv-box">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
          <span class="pdf-name" id="cv-name">Belum ada PDF</span>
        </div>
        <div class="upload-details">
          <p class="upload-hint">Upload CV dalam format PDF agar pengunjung bisa mendownloadnya.<br/>Maks 10MB.</p>
          <div class="upload-actions">
            <button class="upload-btn" onclick="triggerUpload('cv','pdf','cv-box','p-cv')">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
              Upload PDF
            </button>
            <button class="clear-btn" onclick="clearUpload('p-cv','cv-box','pdf')">Hapus</button>
          </div>
          <input type="hidden" id="p-cv"/>
        </div>
      </div>
    </div>
  </div><!-- /content-hero -->

  <!-- ══ ACADEMIC ══════════════════════════════════════════ -->
  <div class="content" id="content-academic">
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 7v-7"/></svg>
        Pendidikan
      </div>
      <div id="education-list"></div>
      <button class="add-btn" onclick="addItem('education')">+ Tambah Pendidikan</button>
    </div>
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18"/></svg>
        Penelitian / Lab
      </div>
      <div id="research-list"></div>
      <button class="add-btn" onclick="addItem('research')">+ Tambah Penelitian</button>
    </div>
  </div>

  <!-- ══ SKILLS ════════════════════════════════════════════ -->
  <div class="content" id="content-skills">
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        Hard Skills
      </div>
      <div id="hardskill-list"></div>
      <button class="add-btn" onclick="addItem('hard')">+ Tambah Hard Skill</button>
    </div>
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Soft Skills
      </div>
      <div id="softskill-list"></div>
      <button class="add-btn" onclick="addItem('soft')">+ Tambah Soft Skill</button>
    </div>
  </div>

  <!-- ══ EXPERIENCE ════════════════════════════════════════ -->
  <div class="content" id="content-experience">
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m8 0H8m8 0a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2"/></svg>
        Pengalaman Profesional
      </div>
      <div id="professional-list"></div>
      <button class="add-btn" onclick="addItem('professional')">+ Tambah Pengalaman</button>
    </div>
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
        Pengalaman Organisasi
      </div>
      <div id="organisational-list"></div>
      <button class="add-btn" onclick="addItem('organisational')">+ Tambah Pengalaman</button>
    </div>
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
        Pengalaman Lainnya
      </div>
      <div id="other-list"></div>
      <button class="add-btn" onclick="addItem('other')">+ Tambah Pengalaman</button>
    </div>
  </div>

  <!-- ══ PROJECTS ══════════════════════════════════════════ -->
  <div class="content" id="content-projects">
    <div class="info-box">💡 Setiap project bisa ditambahkan gambar thumbnail. Gambar akan tampil di halaman Project portfolio.</div>
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        Semua Project
      </div>
      <div id="projects-list"></div>
      <button class="add-btn" onclick="addItem('projects')">+ Tambah Project</button>
    </div>
  </div>

  <!-- ══ TESTIMONIALS ══════════════════════════════════════ -->
  <div class="content" id="content-testimonials">
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        Testimoni
      </div>
      <div id="testimonials-list"></div>
      <button class="add-btn" onclick="addItem('testimonials')">+ Tambah Testimoni</button>
    </div>
  </div>

  <!-- ══ LABELS ════════════════════════════════════════════ -->
  <div class="content" id="content-labels">
    <div class="info-box">✏️ Edit teks judul dan subtitle setiap section di sini. Perubahan akan langsung terlihat di website setelah Save.</div>
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
        Judul &amp; Subtitle Section
      </div>
      <div class="field-row">
        <div class="field"><label>About Me — Judul Section</label><input type="text" id="lbl-aboutTitle"/></div>
        <div class="field"><label>Academic — Judul Halaman</label><input type="text" id="lbl-academicTitle"/></div>
      </div>
      <div class="field-row" style="margin-top:1rem">
        <div class="field"><label>Academic — Subtitle</label><input type="text" id="lbl-academicSubtitle"/></div>
        <div class="field"><label>Skills — Judul Halaman</label><input type="text" id="lbl-skillsTitle"/></div>
      </div>
      <div class="field-row" style="margin-top:1rem">
        <div class="field"><label>Hard Skill — Judul Section</label><input type="text" id="lbl-hardSkillTitle"/></div>
        <div class="field"><label>Hard Skill — Subtitle</label><input type="text" id="lbl-hardSkillSubtitle"/></div>
      </div>
      <div class="field-row" style="margin-top:1rem">
        <div class="field"><label>Soft Skill — Judul Section</label><input type="text" id="lbl-softSkillTitle"/></div>
        <div class="field"><label>Soft Skill — Subtitle</label><input type="text" id="lbl-softSkillSubtitle"/></div>
      </div>
      <div class="field-row" style="margin-top:1rem">
        <div class="field"><label>Experiences — Judul Halaman</label><input type="text" id="lbl-experiencesTitle"/></div>
        <div class="field"><label>Experiences — Subtitle</label><input type="text" id="lbl-experiencesSubtitle"/></div>
      </div>
      <div class="field-row" style="margin-top:1rem">
        <div class="field"><label>Projects — Judul Halaman</label><input type="text" id="lbl-projectsTitle"/></div>
        <div class="field"><label>Projects — Subtitle</label><input type="text" id="lbl-projectsSubtitle"/></div>
      </div>
      <div class="field-row" style="margin-top:1rem">
        <div class="field"><label>Testimonials — Judul Section</label><input type="text" id="lbl-testimonialsTitle"/></div>
        <div class="field"><label>Let's Connect — Judul Section</label><input type="text" id="lbl-connectTitle"/></div>
      </div>
      <div class="field" style="margin-top:1rem"><label>Let's Connect — Deskripsi</label><textarea id="lbl-connectDesc" rows="3"></textarea></div>
    </div>
  </div>

  <!-- ══ SETTINGS ══════════════════════════════════════════ -->
  <div class="content" id="content-settings">
    <div class="warn-box">⚠️ <strong>Ganti password</strong> sebelum upload ke hosting agar dashboard tidak bisa diakses orang lain!</div>
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        Ganti Password Dashboard
      </div>
      <div class="pwd-row">
        <div class="field"><label>Password Baru (min 6 karakter)</label><input type="password" id="new-password" placeholder="Masukkan password baru"/></div>
        <button class="pwd-btn" onclick="changePassword()">Update</button>
      </div>
      <p id="pwd-msg" style="font-size:.79rem;margin-top:.75rem;display:none"></p>
    </div>
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Status Sistem
      </div>
      <div class="stat-row">
        <div class="stat-dot <?= $sys['writable'] ? 'stat-ok' : 'stat-fail' ?>"></div>
        <span class="stat-label">portfolio.json:</span>
        <span class="stat-val"><?= $sys['writable'] ? 'Writable ✓' : 'TIDAK BISA DITULIS — klik kanan folder data, Properties, hilangkan centang Read-only' ?></span>
      </div>
      <div class="stat-row">
        <div class="stat-dot <?= $sys['uploads'] ? 'stat-ok' : 'stat-fail' ?>"></div>
        <span class="stat-label">Folder uploads/:</span>
        <span class="stat-val"><?= $sys['uploads'] ? 'Writable ✓' : 'TIDAK BISA DITULIS — buat folder uploads/ di sebelah folder dashboard/' ?></span>
      </div>
      <div class="divider"></div>
      <div class="stat-row"><span class="stat-label">PHP Version:</span><span class="stat-val"><?= $sys['php'] ?></span></div>
      <div class="stat-row"><span class="stat-label">upload_max_filesize:</span><span class="stat-val"><?= $sys['uploadMax'] ?></span></div>
      <div class="stat-row"><span class="stat-label">post_max_size:</span><span class="stat-val"><?= $sys['postMax'] ?></span></div>
      <div class="stat-row"><span class="stat-label">Path data file:</span><span class="stat-val" style="font-size:.72rem;word-break:break-all"><?= htmlspecialchars($sys['dataPath']) ?></span></div>
    </div>
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        Tips Keamanan
      </div>
      <div class="info-box">
        <strong>1. Ganti password default</strong> — Gunakan form di atas sebelum upload ke Hostinger.<br/>
        <strong>2. URL dashboard</strong> — <code>yourdomain.com/dashboard/</code> — jangan share ke siapapun.<br/>
        <strong>3. Double security via cPanel</strong> — Login cPanel → "Directory Privacy" → pilih folder <code>dashboard</code> → aktifkan password protection. Ini menambahkan layer keamanan ekstra.<br/>
        <strong>4. Setelah upload file</strong> — Tekan Ctrl+Shift+R (hard refresh) di halaman portfolio untuk melihat perubahan terbaru.
      </div>
    </div>
  </div><!-- /content-settings -->

</div><!-- /main -->
</div><!-- /shell -->

<script>
// ══════════════════════════════════════════════════════════
//  DATA STATE
// ══════════════════════════════════════════════════════════
let D = <?= $json ?>;

const defaultLabels = {
  aboutTitle:'About Me', academicTitle:'Academic Background',
  academicSubtitle:'Building expertise through rigorous education and hands-on research',
  skillsTitle:'My Skills', hardSkillTitle:'Hard Skills',
  hardSkillSubtitle:'Technical competencies in engineering software and methods',
  softSkillTitle:'Soft Skills',
  softSkillSubtitle:'Interpersonal abilities that drive collaboration and project success',
  experiencesTitle:'Experiences',
  experiencesSubtitle:'Professional work and organisational leadership that shaped my career',
  projectsTitle:'Projects', projectsSubtitle:'Showcasing engineering solutions and research',
  testimonialsTitle:'What People Think of Me', connectTitle:"Let's Connect",
  connectDesc:"Whether you have a project in mind, a research collaboration, or just want to connect — I am always open to meaningful conversations."
};
if (!D.labels) D.labels = {};
Object.keys(defaultLabels).forEach(k=>{ if(!D.labels[k]) D.labels[k]=defaultLabels[k]; });
if (!D.personal)    D.personal    = {};
if (!D.academic)    D.academic    = {education:[],research:[]};
if (!D.skills)      D.skills      = {hard:[],soft:[]};
if (!D.experiences) D.experiences = {professional:[],organisational:[],other:[]};
if (!D.projects)    D.projects    = [];
if (!D.testimonials) D.testimonials = [];

// ── TAB NAVIGATION ────────────────────────────────────────
function showTab(name){
  document.querySelectorAll('.content').forEach(c=>c.classList.remove('active'));
  document.querySelectorAll('.nav-btn').forEach(b=>b.classList.remove('active'));
  document.getElementById('content-'+name).classList.add('active');
  document.getElementById('tab-'+name).classList.add('active');
  const titles={hero:'Hero & About',academic:'Academic',skills:'Skills',experience:'Experiences',projects:'Projects',testimonials:'Testimonials',labels:'Label & Teks UI',settings:'Settings'};
  document.getElementById('topbar-title').textContent=titles[name]||name;
}

// ── HELPERS ───────────────────────────────────────────────
function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function v(cls,el){ return (el||document).querySelector(cls)?.value?.trim()||''; }
function nv(cls,el){ return parseInt((el||document).querySelector(cls)?.value||0); }

// ── TOAST NOTIFICATIONS ───────────────────────────────────
function showToast(msg, type='success'){
  const c=document.getElementById('toast-container');
  const t=document.createElement('div');
  t.className='toast '+(type==='success'?'':type);
  t.innerHTML=`<div class="toast-icon">${type==='error'?'✗':type==='warning'?'!':'✓'}</div><span>${msg}</span>`;
  c.appendChild(t);
  setTimeout(()=>{ t.style.opacity='0'; t.style.transform='translateX(110%)'; setTimeout(()=>t.remove(),350); },3500);
}

// ── UPLOAD ────────────────────────────────────────────────
function triggerUpload(prefix, type, boxId, inputId){
  const inp=document.createElement('input');
  inp.type='file';
  inp.accept=type==='pdf'?'.pdf':'image/*';
  inp.onchange=function(){
    const file=this.files[0]; if(!file) return;
    const fd=new FormData();
    fd.append('action','upload');
    fd.append('type',type);
    fd.append('file',file);
    showToast('Mengupload '+file.name+'…','warning');
    fetch('',{method:'POST',body:fd})
      .then(r=>r.json()).then(d=>{
        if(d.ok){
          document.getElementById(inputId).value=d.url;
          refreshPreview(boxId,d.url,type,d.filename);
          showToast('Upload berhasil: '+d.filename,'success');
        } else {
          showToast('Upload gagal: '+d.msg,'error');
        }
      }).catch(()=>showToast('Koneksi error','error'));
  };
  inp.click();
}

function refreshPreview(boxId,url,type,filename){
  const box=document.getElementById(boxId);
  if(!box) return;
  if(type==='image'){
    box.innerHTML=`<img src="../${url}" alt="preview" style="width:100%;height:100%;object-fit:cover;">`;
  } else {
    box.querySelector('.pdf-name').textContent=filename||url.split('/').pop();
  }
}

function clearUpload(inputId,boxId,type){
  document.getElementById(inputId).value='';
  const box=document.getElementById(boxId);
  if(!box) return;
  if(type==='image'){
    box.innerHTML=`<div class="no-img"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:28px;height:28px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg><span>Belum ada foto</span></div>`;
  } else {
    box.querySelector('.pdf-name').textContent='Belum ada PDF';
  }
}

function uploadProjectThumb(idx){
  const inp=document.createElement('input');
  inp.type='file'; inp.accept='image/*';
  inp.onchange=function(){
    const file=this.files[0]; if(!file) return;
    const fd=new FormData();
    fd.append('action','upload'); fd.append('type','image'); fd.append('file',file);
    showToast('Mengupload thumbnail…','warning');
    fetch('',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
      if(d.ok){
        const hid=document.getElementById('proj-thumb-'+idx);
        if(hid) hid.value=d.url;
        const box=document.getElementById('proj-thumb-box-'+idx);
        if(box) box.innerHTML=`<img src="../${d.url}" alt="thumb">`;
        showToast('Thumbnail berhasil diupload','success');
      } else {
        showToast('Upload gagal: '+d.msg,'error');
      }
    }).catch(()=>showToast('Koneksi error','error'));
  };
  inp.click();
}

function uploadTestiPhoto(idx){
  const inp=document.createElement('input');
  inp.type='file'; inp.accept='image/*';
  inp.onchange=function(){
    const file=this.files[0]; if(!file) return;
    const fd=new FormData();
    fd.append('action','upload'); fd.append('type','image'); fd.append('file',file);
    showToast('Mengupload foto…','warning');
    fetch('',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
      if(d.ok){
        const urlInp=document.getElementById('testi-photo-url-'+idx);
        if(urlInp) urlInp.value=d.url;
        const box=document.getElementById('testi-photo-box-'+idx);
        if(box) box.innerHTML=`<img src="../${d.url}" alt="foto" style="width:100%;height:100%;object-fit:cover;">`;
        showToast('Foto berhasil diupload','success');
      } else { showToast('Upload gagal: '+d.msg,'error'); }
    }).catch(()=>showToast('Koneksi error','error'));
  };
  inp.click();
}

// ── MULTI-IMAGE WIDGET ────────────────────────────────────
function renderMultiImgWidget(uid, images){
  const imgs=(images||[]).filter(Boolean);
  const previews=imgs.map((url,idx)=>{
    const itemId='mii-'+uid+'-'+idx;
    return `<div class="multi-img-item" id="${itemId}">
      <img src="../${url}" alt="img">
      <input type="hidden" class="multi-img-url" value="${esc(url)}">
      <button class="multi-img-del" onclick="removeMultiImg('${itemId}')" title="Hapus">×</button>
    </div>`;
  }).join('');
  return `<div class="multi-img-widget">
    <label>Foto / Gambar Tambahan</label>
    <div class="multi-img-grid" id="mig-${uid}">${previews}</div>
    <button type="button" class="multi-img-add" onclick="addMultiImg('${uid}')">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:12px;height:12px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      Tambah Foto
    </button>
  </div>`;
}

function addMultiImg(uid){
  const inp=document.createElement('input');
  inp.type='file'; inp.accept='image/*';
  inp.onchange=function(){
    const file=this.files[0]; if(!file) return;
    const fd=new FormData();
    fd.append('action','upload'); fd.append('type','image'); fd.append('file',file);
    showToast('Mengupload '+file.name+'…','warning');
    fetch('',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
      if(d.ok){
        const grid=document.getElementById('mig-'+uid);
        if(!grid) return;
        const idx=grid.querySelectorAll('.multi-img-item').length;
        const itemId='mii-'+uid+'-'+idx;
        const div=document.createElement('div');
        div.className='multi-img-item'; div.id=itemId;
        div.innerHTML=`<img src="../${d.url}" alt="img"><input type="hidden" class="multi-img-url" value="${esc(d.url)}"><button class="multi-img-del" onclick="removeMultiImg('${itemId}')" title="Hapus">×</button>`;
        grid.appendChild(div);
        showToast('Foto berhasil diupload','success');
      } else { showToast('Upload gagal: '+d.msg,'error'); }
    }).catch(()=>showToast('Koneksi error','error'));
  };
  inp.click();
}

function removeMultiImg(itemId){
  document.getElementById(itemId)?.remove();
}

function getMultiImgs(gridEl){
  if(!gridEl) return [];
  return [...gridEl.querySelectorAll('.multi-img-url')].map(h=>h.value).filter(Boolean);
}

// ── TAGS WIDGET ───────────────────────────────────────────
function renderTagsWidget(uid, tags){
  const chips=(tags||[]).map(t=>`<span class="tag-chip">${esc(t)}<button onclick="removeTag('${uid}',this)">×</button></span>`).join('');
  return `<div class="field"><label>Tags (Enter untuk tambah)</label>
    <div class="tags-wrap" id="tagw-${uid}" onclick="focusTag('${uid}')">
      ${chips}<input class="tag-input-inline" id="tagi-${uid}" placeholder="Tambah tag..." onkeydown="onTagKey(event,'${uid}')"/>
    </div></div>`;
}
function focusTag(uid){ document.getElementById('tagi-'+uid)?.focus(); }
function onTagKey(e,uid){
  if(e.key==='Enter'||e.key===','){
    e.preventDefault();
    const inp=document.getElementById('tagi-'+uid);
    const val=inp.value.trim().replace(',','');
    if(val){ addTagChip(uid,val); inp.value=''; }
  }
}
function addTagChip(uid,tag){
  const wrap=document.getElementById('tagw-'+uid);
  const inp=document.getElementById('tagi-'+uid);
  const chip=document.createElement('span');
  chip.className='tag-chip';
  chip.innerHTML=`${esc(tag)}<button onclick="removeTag('${uid}',this)">×</button>`;
  wrap.insertBefore(chip,inp);
}
function removeTag(uid,btn){ btn.closest('.tag-chip').remove(); }
function getTags(uid){
  return [...document.querySelectorAll(`#tagw-${uid} .tag-chip`)].map(c=>c.textContent.trim().slice(0,-1));
}

function profSlider(cls,val){
  return `<div class="field"><label>Proficiency (%)</label>
    <div class="prof-wrap">
      <input type="range" min="0" max="100" value="${val||80}" class="${cls}" oninput="this.nextElementSibling.textContent=this.value+'%'"/>
      <span class="prof-val">${val||80}%</span>
    </div></div>`;
}

function toggleItem(btn){
  const body=btn.closest('.array-item').querySelector('.item-body');
  body.classList.toggle('open');
  btn.textContent=body.classList.contains('open')?'▲':'▼';
}

// ── RENDER FUNCTIONS ──────────────────────────────────────
// All items use CSS classes (.f-xxx) for collection, not ID indices.

function renderPersonal(){
  const P=D.personal||{};
  document.getElementById('p-name').value=P.name||'';
  document.getElementById('p-tagline').value=P.tagline||'';
  document.getElementById('p-title').value=P.title||'';
  document.getElementById('p-about').value=P.about||'';
  document.getElementById('p-email').value=P.email||'';
  document.getElementById('p-phone').value=P.phone||'';
  document.getElementById('p-linkedin').value=P.linkedin||'';
  document.getElementById('p-location').value=P.location||'';
  document.getElementById('p-photo').value=P.photo||'';
  document.getElementById('p-cv').value=P.cvLink||'';
  if(P.photo) refreshPreview('photo-box','../' + P.photo,'image','');
  if(P.cvLink){ document.getElementById('cv-name').textContent=P.cvLink.split('/').pop(); }
}

function renderEducation(){
  document.getElementById('education-list').innerHTML=(D.academic?.education||[]).map((e,i)=>`
    <div class="array-item">
      <div class="item-header">
        <span class="item-title">${esc(e.institution||'Pendidikan Baru')}</span>
        <div class="item-actions">
          <button class="toggle-btn" onclick="toggleItem(this)">▼</button>
          <button class="del-btn" onclick="delItem(this,'education')">${trashSvg()}</button>
        </div>
      </div>
      <div class="item-body open">
        <div class="field-row">
          <div class="field"><label>Institusi</label><input class="f-institution" value="${esc(e.institution)}"/></div>
          <div class="field"><label>Gelar / Program Studi</label><input class="f-degree" value="${esc(e.degree)}"/></div>
        </div>
        <div class="field-row" style="margin-top:1rem">
          <div class="field"><label>Periode</label><input class="f-period" value="${esc(e.period)}"/></div>
          <div class="field"><label>GPA / Nilai</label><input class="f-gpa" value="${esc(e.gpa)}"/></div>
        </div>
        <div class="field" style="margin-top:1rem"><label>Deskripsi</label><textarea class="f-description" rows="3">${esc(e.description)}</textarea></div>
        ${renderMultiImgWidget('edu-'+i, e.images||[])}
        <label class="featured-toggle"><input type="checkbox" class="f-featured" ${e.featured !== false ? 'checked' : ''}/> Tampilkan di Landing Page</label>
      </div>
    </div>`).join('');
}

function renderResearch(){
  document.getElementById('research-list').innerHTML=(D.academic?.research||[]).map((r,i)=>`
    <div class="array-item">
      <div class="item-header">
        <span class="item-title">${esc(r.title||'Penelitian Baru')}</span>
        <div class="item-actions">
          <button class="toggle-btn" onclick="toggleItem(this)">▼</button>
          <button class="del-btn" onclick="delItem(this,'research')">${trashSvg()}</button>
        </div>
      </div>
      <div class="item-body open">
        <div class="field-row">
          <div class="field"><label>Nama Lab / Judul</label><input class="f-title" value="${esc(r.title)}"/></div>
          <div class="field"><label>Peran Anda</label><input class="f-role" value="${esc(r.role)}"/></div>
        </div>
        <div class="field-row" style="margin-top:1rem">
          <div class="field"><label>Universitas / Organisasi</label><input class="f-institution" value="${esc(r.institution)}"/></div>
          <div class="field"><label>Periode</label><input class="f-period" value="${esc(r.period)}"/></div>
        </div>
        <div class="field" style="margin-top:1rem"><label>Kontribusi</label><input class="f-contribution" value="${esc(r.contribution)}"/></div>
        <div class="field" style="margin-top:1rem"><label>Deskripsi</label><textarea class="f-description" rows="3">${esc(r.description)}</textarea></div>
        <div class="field" style="margin-top:1rem"><label>Link Paper (opsional)</label><input class="f-paperLink" value="${esc(r.paperLink||'')}"/></div>
        ${renderMultiImgWidget('res-'+i, r.images||[])}
        <label class="featured-toggle"><input type="checkbox" class="f-featured" ${r.featured !== false ? 'checked' : ''}/> Tampilkan di Landing Page</label>
      </div>
    </div>`).join('');
}

function renderHardSkills(){
  document.getElementById('hardskill-list').innerHTML=(D.skills?.hard||[]).map((s,i)=>`
    <div class="array-item">
      <div class="item-header">
        <span class="item-title">${esc(s.title||'Skill Baru')}</span>
        <div class="item-actions">
          <button class="toggle-btn" onclick="toggleItem(this)">▼</button>
          <button class="del-btn" onclick="delItem(this,'hard')">${trashSvg()}</button>
        </div>
      </div>
      <div class="item-body open">
        <div class="field"><label>Nama Skill</label><input class="f-title" value="${esc(s.title)}"/></div>
        ${profSlider('f-proficiency',s.proficiency)}
        <div class="field" style="margin-top:1rem"><label>Deskripsi</label><textarea class="f-description" rows="3">${esc(s.description)}</textarea></div>
        <div class="field" style="margin-top:1rem"><label>Link Sertifikat (opsional)</label><input class="f-certLink" value="${esc(s.certLink||'')}"/></div>
        ${renderMultiImgWidget('skill-'+i, s.images||[])}
      </div>
    </div>`).join('');
}

function renderSoftSkills(){
  document.getElementById('softskill-list').innerHTML=(D.skills?.soft||[]).map((s,i)=>`
    <div class="array-item">
      <div class="item-header">
        <span class="item-title">${esc(s.title||'Skill Baru')}</span>
        <div class="item-actions">
          <button class="toggle-btn" onclick="toggleItem(this)">▼</button>
          <button class="del-btn" onclick="delItem(this,'soft')">${trashSvg()}</button>
        </div>
      </div>
      <div class="item-body open">
        <div class="field"><label>Nama Skill</label><input class="f-title" value="${esc(s.title)}"/></div>
        <div class="field" style="margin-top:.75rem"><label>Deskripsi</label><textarea class="f-description" rows="3">${esc(s.description)}</textarea></div>
      </div>
    </div>`).join('');
}

function renderExp(type,listId){
  const items=D.experiences?.[type]||[];
  document.getElementById(listId).innerHTML=items.map((e,i)=>`
    <div class="array-item">
      <div class="item-header">
        <span class="item-title">${esc(e.title||'Entry Baru')}</span>
        <div class="item-actions">
          <button class="toggle-btn" onclick="toggleItem(this)">▼</button>
          <button class="del-btn" onclick="delItem(this,'${type}')">${trashSvg()}</button>
        </div>
      </div>
      <div class="item-body open">
        <div class="field-row">
          <div class="field"><label>Jabatan / Posisi</label><input class="f-title" value="${esc(e.title)}"/></div>
          <div class="field"><label>Organisasi / Perusahaan</label><input class="f-organization" value="${esc(e.organization||e.organisation||'')}"/></div>
        </div>
        <div class="field-row" style="margin-top:1rem">
          <div class="field"><label>Periode</label><input class="f-period" value="${esc(e.period)}"/></div>
          <div class="field"><label>Lokasi</label><input class="f-location" value="${esc(e.location||'')}"/></div>
        </div>
        <div class="field" style="margin-top:1rem"><label>Deskripsi</label><textarea class="f-description" rows="4">${esc(e.description)}</textarea></div>
        <div class="field" style="margin-top:1rem"><label>Link (opsional)</label><input class="f-link" value="${esc(e.link||'')}"/></div>
        ${renderMultiImgWidget('exp-'+type+'-'+i, e.images||[])}
        <label class="featured-toggle"><input type="checkbox" class="f-featured" ${e.featured !== false ? 'checked' : ''}/> Tampilkan di Landing Page</label>
      </div>
    </div>`).join('');
}

function renderProjects(){
  document.getElementById('projects-list').innerHTML=(D.projects||[]).map((p,i)=>`
    <div class="array-item">
      <div class="item-header">
        <span class="item-title">${esc(p.title||'Project Baru')}</span>
        <div class="item-actions">
          <button class="toggle-btn" onclick="toggleItem(this)">▼</button>
          <button class="del-btn" onclick="delItem(this,'projects')">${trashSvg()}</button>
        </div>
      </div>
      <div class="item-body open">
        <div class="field-row">
          <div class="field"><label>Judul Project</label><input class="f-title" value="${esc(p.title)}"/></div>
          <div class="field"><label>Tahun</label><input class="f-year" value="${esc(p.year)}"/></div>
        </div>
        <div class="field" style="margin-top:1rem"><label>Headline (deskripsi singkat)</label><input class="f-headline" value="${esc(p.headline)}"/></div>
        <div class="field" style="margin-top:1rem"><label>Deskripsi Lengkap</label><textarea class="f-description" rows="4">${esc(p.description)}</textarea></div>
        ${renderTagsWidget('proj-'+i, p.tags)}
        <div class="proj-thumb-wrap" style="margin-top:.75rem">
          <div class="proj-thumb-box" id="proj-thumb-box-${i}">
            ${p.thumbnail
              ? `<img src="../${p.thumbnail}" alt="thumb">`
              : `<div class="no-img" style="font-size:.6rem;color:#94A3B8;text-align:center;padding:4px">Belum ada<br>gambar</div>`}
          </div>
          <div>
            <p style="font-size:.78rem;color:#475569;margin-bottom:.5rem">Thumbnail ditampilkan di halaman Projects.</p>
            <button class="upload-btn" onclick="uploadProjectThumb(${i})">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:13px;height:13px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
              Upload Gambar
            </button>
          </div>
          <input type="hidden" id="proj-thumb-${i}" class="f-thumbnail" value="${esc(p.thumbnail||'')}"/>
        </div>
        ${renderMultiImgWidget('proj-imgs-'+i, p.images||[])}
        <p style="font-size:.74rem;color:#94A3B8;margin-top:.25rem;">↑ Gambar tambahan di atas ditampilkan di slider modal project (selain thumbnail)</p>
        <div class="field" style="margin-top:1rem"><label>Link Eksternal (opsional)</label><input class="f-externalLink" value="${esc(p.externalLink||'')}"/></div>
        <label class="featured-toggle"><input type="checkbox" class="f-featured" ${p.featured !== false ? 'checked' : ''}/> Tampilkan di Landing Page</label>
      </div>
    </div>`).join('');
}

function renderTestimonials(){
  document.getElementById('testimonials-list').innerHTML=(D.testimonials||[]).map((t,i)=>`
    <div class="array-item">
      <div class="item-header">
        <span class="item-title">${esc(t.name||'Testimoni Baru')}</span>
        <div class="item-actions">
          <button class="toggle-btn" onclick="toggleItem(this)">▼</button>
          <button class="del-btn" onclick="delItem(this,'testimonials')">${trashSvg()}</button>
        </div>
      </div>
      <div class="item-body open">
        <div class="field-row">
          <div class="field"><label>Nama</label><input class="f-name" value="${esc(t.name)}"/></div>
          <div class="field"><label>Jabatan / Posisi</label><input class="f-title" value="${esc(t.title)}"/></div>
        </div>
        <div class="field" style="margin-top:1rem"><label>Kutipan / Testimoni</label><textarea class="f-quote" rows="3">${esc(t.quote)}</textarea></div>
        <div class="field" style="margin-top:1rem">
          <label>Foto Testimoni (opsional)</label>
          <div style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;padding:.75rem;background:var(--s50);border:1px solid var(--s200);border-radius:8px;">
            <div id="testi-photo-box-${i}" style="width:52px;height:52px;border-radius:50%;overflow:hidden;border:2px solid var(--s200);background:var(--s100);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:.7rem;color:#94a3b8;">
              ${t.photo ? `<img src="../${t.photo}" alt="foto" style="width:100%;height:100%;object-fit:cover;">` : 'foto'}
            </div>
            <div style="flex:1;min-width:160px;">
              <input class="f-photo" id="testi-photo-url-${i}" value="${esc(t.photo||'')}" placeholder="URL foto..." style="width:100%;margin-bottom:.4rem;"/>
              <button type="button" class="upload-btn" onclick="uploadTestiPhoto(${i})" style="font-size:.74rem;padding:.35rem .75rem;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:12px;height:12px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Upload Foto
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>`).join('');
}

function renderLabels(){
  const L=D.labels||{};
  // Read from nested JSON structure (matching portfolio.json format)
  const g = (obj, key, fb) => (obj&&obj[key])||fb||'';
  const setV = (id, v) => { const el=document.getElementById(id); if(el) el.value=v; };
  setV('lbl-aboutTitle',        g(L.index,'aboutTitle','About Me'));
  setV('lbl-academicTitle',     g(L.index,'academicTitle','')||g(L.academic,'pageTitle','Academic Background'));
  setV('lbl-academicSubtitle',  g(L.academic,'pageSub','Building expertise through rigorous education and hands-on research'));
  setV('lbl-skillsTitle',       g(L.index,'skillsTitle','')||g(L.skill,'pageTitle','My Skills'));
  setV('lbl-hardSkillTitle',    g(L.skill,'hardTitle','Hard Skills'));
  setV('lbl-hardSkillSubtitle', g(L.skill,'hardSub','Technical competencies in engineering software and computational methods'));
  setV('lbl-softSkillTitle',    g(L.skill,'softTitle','Soft Skills'));
  setV('lbl-softSkillSubtitle', g(L.skill,'softSub','Interpersonal and professional competencies'));
  setV('lbl-experiencesTitle',  g(L.experience,'pageTitle','')||g(L.index,'experienceTitle','Experiences'));
  setV('lbl-experiencesSubtitle',g(L.experience,'pageSub','Professional work and organisational leadership that shaped my career journey'));
  setV('lbl-projectsTitle',     g(L.index,'projectsTitle','')||g(L.project,'pageTitle','Projects'));
  setV('lbl-projectsSubtitle',  g(L.project,'pageSub','Showcasing engineering solutions and research'));
  setV('lbl-testimonialsTitle', g(L.index,'testimonialsTitle','What People Think of Me'));
  setV('lbl-connectTitle',      g(L.index,'connectTitle',"Let's Connect"));
  setV('lbl-connectDesc',       g(L.index,'connectDesc','Whether you have a project in mind, a research collaboration, or just want to connect — I am always open to meaningful conversations.'));
}

// ── SVG helper ────────────────────────────────────────────
function trashSvg(){
  return '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>';
}

// ── ADD ITEM ──────────────────────────────────────────────
const newItem = {
  education: {institution:'',degree:'',period:'',gpa:'',description:'',featured:true},
  research:  {title:'',role:'',institution:'',period:'',contribution:'',description:'',paperLink:null,featured:true},
  hard:      {id:'new',title:'',type:'Hard Skill',proficiency:75,description:'',certLink:null,images:[]},
  soft:      {id:'new',title:'',type:'Soft Skill',description:'',images:[]},
  professional:   {id:'new',title:'',organization:'',location:'',period:'',description:'',link:null,featured:true},
  organisational: {id:'new',title:'',organization:'',location:'',period:'',description:'',link:null,featured:true},
  other:          {id:'new',title:'',organization:'',period:'',description:'',link:null},
  projects:    {id:'new',title:'',headline:'',year:new Date().getFullYear().toString(),description:'',tags:[],thumbnail:null,externalLink:null,featured:true},
  testimonials:{id:'new',name:'',title:'',quote:''}
};

function addItem(type){
  switch(type){
    case 'education':     D.academic.education.push({...newItem.education}); renderEducation(); break;
    case 'research':      D.academic.research.push({...newItem.research});   renderResearch();  break;
    case 'hard':          D.skills.hard.push({...newItem.hard});             renderHardSkills();break;
    case 'soft':          D.skills.soft.push({...newItem.soft});             renderSoftSkills();break;
    case 'professional':  D.experiences.professional.push({...newItem.professional});   renderExp('professional','professional-list');   break;
    case 'organisational':D.experiences.organisational.push({...newItem.organisational});renderExp('organisational','organisational-list');break;
    case 'other':         D.experiences.other.push({...newItem.other});      renderExp('other','other-list'); break;
    case 'projects':      D.projects.push({...newItem.projects});            renderProjects();  break;
    case 'testimonials':  D.testimonials.push({...newItem.testimonials});    renderTestimonials(); break;
  }
}

// ── DELETE ITEM ───────────────────────────────────────────
// Reads current DOM index, removes from D, then re-renders — ensures IDs stay in sync
function delItem(btn, type){
  if(!confirm('Hapus entry ini?')) return;
  const item=btn.closest('.array-item');
  const allItems=[...item.parentElement.querySelectorAll('.array-item')];
  const idx=allItems.indexOf(item);
  switch(type){
    case 'education':     D.academic.education.splice(idx,1);     renderEducation(); break;
    case 'research':      D.academic.research.splice(idx,1);      renderResearch();  break;
    case 'hard':          D.skills.hard.splice(idx,1);            renderHardSkills();break;
    case 'soft':          D.skills.soft.splice(idx,1);            renderSoftSkills();break;
    case 'professional':  D.experiences.professional.splice(idx,1);   renderExp('professional','professional-list');   break;
    case 'organisational':D.experiences.organisational.splice(idx,1); renderExp('organisational','organisational-list');break;
    case 'other':         D.experiences.other.splice(idx,1);      renderExp('other','other-list'); break;
    case 'projects':      D.projects.splice(idx,1);               renderProjects();  break;
    case 'testimonials':  D.testimonials.splice(idx,1);           renderTestimonials(); break;
  }
  showToast('Entry dihapus','success');
}

// ── COLLECT ALL DATA FROM DOM ─────────────────────────────
function collectData(){
  // Personal
  D.personal.name     = document.getElementById('p-name').value.trim();
  D.personal.tagline  = document.getElementById('p-tagline').value.trim();
  D.personal.title    = document.getElementById('p-title').value.trim();
  D.personal.about    = document.getElementById('p-about').value.trim();
  D.personal.email    = document.getElementById('p-email').value.trim();
  D.personal.phone    = document.getElementById('p-phone').value.trim();
  D.personal.linkedin = document.getElementById('p-linkedin').value.trim();
  D.personal.location = document.getElementById('p-location').value.trim();
  D.personal.photo    = document.getElementById('p-photo').value||null;
  D.personal.cvLink   = document.getElementById('p-cv').value||null;

  // Labels — write to nested structure matching portfolio.json
  // Also sync labels.hero with personal so loader.js sees changes
  if (!D.labels) D.labels = {};
  if (!D.labels.hero)  D.labels.hero  = {};
  if (!D.labels.index) D.labels.index = {};
  if (!D.labels.academic) D.labels.academic = {};
  if (!D.labels.skill)    D.labels.skill    = {};
  if (!D.labels.experience) D.labels.experience = {};
  if (!D.labels.project)    D.labels.project    = {};

  // Sync hero labels with personal fields (critical for loader.js)
  D.labels.hero.tagline = D.personal.tagline;
  D.labels.hero.roles   = D.personal.title;

  // Index page labels
  const gi = (id,fb) => document.getElementById(id)?.value?.trim()||fb;
  D.labels.index.aboutTitle       = gi('lbl-aboutTitle','About Me');
  D.labels.index.academicTitle    = gi('lbl-academicTitle','Academic Background');
  D.labels.index.skillsTitle      = gi('lbl-skillsTitle','Skills & Expertise');
  D.labels.index.experienceTitle  = gi('lbl-experiencesTitle','Experience Overview');
  D.labels.index.projectsTitle    = gi('lbl-projectsTitle','Featured Projects');
  D.labels.index.testimonialsTitle= gi('lbl-testimonialsTitle','What People Think of Me');
  D.labels.index.connectTitle     = gi('lbl-connectTitle',"Let's Connect");
  D.labels.index.connectDesc      = gi('lbl-connectDesc','');

  // Academic labels
  D.labels.academic.pageTitle  = gi('lbl-academicTitle','Academic Background');
  D.labels.academic.pageSub    = gi('lbl-academicSubtitle','Building expertise through rigorous education and hands-on research');

  // Skill labels
  D.labels.skill.pageTitle = gi('lbl-skillsTitle','My Skills');
  D.labels.skill.hardTitle = gi('lbl-hardSkillTitle','Hard Skills');
  D.labels.skill.hardSub   = gi('lbl-hardSkillSubtitle','Technical competencies in engineering software and computational methods');
  D.labels.skill.softTitle = gi('lbl-softSkillTitle','Soft Skills');
  D.labels.skill.softSub   = gi('lbl-softSkillSubtitle','Interpersonal and professional competencies');

  // Experience labels
  D.labels.experience.pageTitle = gi('lbl-experiencesTitle','Experiences');
  D.labels.experience.pageSub   = gi('lbl-experiencesSubtitle','Professional work and organisational leadership that shaped my career journey');

  // Project labels
  D.labels.project.pageTitle = gi('lbl-projectsTitle','Projects');
  D.labels.project.pageSub   = gi('lbl-projectsSubtitle','Showcasing engineering solutions and research');

  // Education — read from DOM using classes
  D.academic.education=[...document.querySelectorAll('#education-list .array-item')].map((el,i)=>({
    institution: el.querySelector('.f-institution')?.value?.trim()||'',
    degree:      el.querySelector('.f-degree')?.value?.trim()||'',
    period:      el.querySelector('.f-period')?.value?.trim()||'',
    gpa:         el.querySelector('.f-gpa')?.value?.trim()||'',
    description: el.querySelector('.f-description')?.value?.trim()||'',
    images:      getMultiImgs(el.querySelector('#mig-edu-'+i)||el),
    featured:    el.querySelector('.f-featured')?.checked !== false ? true : false
  }));

  // Research
  D.academic.research=[...document.querySelectorAll('#research-list .array-item')].map((el,i)=>({
    title:        el.querySelector('.f-title')?.value?.trim()||'',
    role:         el.querySelector('.f-role')?.value?.trim()||'',
    institution:  el.querySelector('.f-institution')?.value?.trim()||'',
    period:       el.querySelector('.f-period')?.value?.trim()||'',
    contribution: el.querySelector('.f-contribution')?.value?.trim()||'',
    description:  el.querySelector('.f-description')?.value?.trim()||'',
    paperLink:    el.querySelector('.f-paperLink')?.value?.trim()||null,
    images:       getMultiImgs(el.querySelector('#mig-res-'+i)||el),
    featured:     el.querySelector('.f-featured')?.checked !== false ? true : false
  }));

  // Hard Skills
  D.skills.hard=[...document.querySelectorAll('#hardskill-list .array-item')].map((el,i)=>({
    id:          D.skills.hard[i]?.id||'skill-'+i,
    title:       el.querySelector('.f-title')?.value?.trim()||'',
    type:        'Hard Skill',
    proficiency: parseInt(el.querySelector('.f-proficiency')?.value||80),
    description: el.querySelector('.f-description')?.value?.trim()||'',
    certLink:    el.querySelector('.f-certLink')?.value?.trim()||null,
    images:      getMultiImgs(el.querySelector('#mig-skill-'+i)||el)
  }));

  // Soft Skills
  D.skills.soft=[...document.querySelectorAll('#softskill-list .array-item')].map((el,i)=>({
    id:          D.skills.soft[i]?.id||'soft-'+i,
    title:       el.querySelector('.f-title')?.value?.trim()||'',
    type:        'Soft Skill',
    description: el.querySelector('.f-description')?.value?.trim()||'',
    images:      D.skills.soft[i]?.images||[]
  }));

  // Experiences
  ['professional','organisational','other'].forEach(type=>{
    D.experiences[type]=[...document.querySelectorAll('#'+type+'-list .array-item')].map((el,i)=>({
      id:           D.experiences[type][i]?.id||type+'-'+i,
      title:        el.querySelector('.f-title')?.value?.trim()||'',
      organization: el.querySelector('.f-organization')?.value?.trim()||'',
      location:     el.querySelector('.f-location')?.value?.trim()||'',
      period:       el.querySelector('.f-period')?.value?.trim()||'',
      description:  el.querySelector('.f-description')?.value?.trim()||'',
      link:         el.querySelector('.f-link')?.value?.trim()||null,
      images:       getMultiImgs(el.querySelector('#mig-exp-'+type+'-'+i)||el),
      featured:     el.querySelector('.f-featured') ? el.querySelector('.f-featured').checked : true
    }));
  });

  // Projects
  D.projects=[...document.querySelectorAll('#projects-list .array-item')].map((el,i)=>{
    var title = el.querySelector('.f-title')?.value?.trim()||'';
    var existingId = D.projects[i]?.id;
    // If ID is missing or a generic placeholder ('new'), generate a slug from the title.
    // This prevents multiple new projects from sharing the same id='new' which breaks the modal.
    var id;
    if (existingId && existingId !== 'new') {
      id = existingId;
    } else if (title) {
      id = title.toLowerCase().replace(/[^a-z0-9\s]/g,'').trim().replace(/\s+/g,'-').substring(0,50) || ('proj-'+i);
    } else {
      id = 'proj-'+i;
    }
    return {
      id,
      title,
      headline:     el.querySelector('.f-headline')?.value?.trim()||'',
      year:         el.querySelector('.f-year')?.value?.trim()||'',
      description:  el.querySelector('.f-description')?.value?.trim()||'',
      tags:         getTags('proj-'+i),
      thumbnail:    el.querySelector('.f-thumbnail')?.value?.trim()||null,
      images:       getMultiImgs(el.querySelector('#mig-proj-imgs-'+i)||el),
      externalLink: el.querySelector('.f-externalLink')?.value?.trim()||null,
      featured:     el.querySelector('.f-featured') ? el.querySelector('.f-featured').checked : true
    };
  });

  // Testimonials
  D.testimonials=[...document.querySelectorAll('#testimonials-list .array-item')].map((el,i)=>({
    id:    D.testimonials[i]?.id||'test-'+i,
    name:  el.querySelector('.f-name')?.value?.trim()||'',
    title: el.querySelector('.f-title')?.value?.trim()||'',
    quote: el.querySelector('.f-quote')?.value?.trim()||'',
    photo: el.querySelector('.f-photo')?.value?.trim()||null
  }));

  return D;
}

// ── SAVE ──────────────────────────────────────────────────
function saveAll(){
  const data=collectData();
  const btn=document.getElementById('save-btn');
  const status=document.getElementById('save-status');
  btn.disabled=true;
  btn.innerHTML='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:14px;height:14px;animation:spin 1s linear infinite"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Menyimpan…';
  status.className='save-status'; status.textContent='';

  fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'action=save&data='+encodeURIComponent(JSON.stringify(data))})
    .then(r=>r.json()).then(d=>{
      if(d.ok){
        status.className='save-status ok'; status.textContent='✓ Tersimpan!';
        showToast('Data berhasil disimpan! Refresh halaman portfolio untuk melihat perubahan.','success');
      } else {
        status.className='save-status err'; status.textContent='Error';
        showToast('Gagal menyimpan: '+d.msg,'error');
      }
    }).catch(()=>{
      status.className='save-status err'; status.textContent='Error koneksi';
      showToast('Koneksi error — pastikan XAMPP berjalan','error');
    }).finally(()=>{
      btn.disabled=false;
      btn.innerHTML='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:14px;height:14px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg> Save Changes';
      setTimeout(()=>status.textContent='',5000);
    });
}

// ── LOGOUT ────────────────────────────────────────────────
function doLogout(){
  fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=logout'})
    .then(()=>location.reload());
}

// ── CHANGE PASSWORD ───────────────────────────────────────
function changePassword(){
  const p=document.getElementById('new-password').value;
  if(p.length<6){ showToast('Minimal 6 karakter','error'); return; }
  fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'action=change_password&new_password='+encodeURIComponent(p)})
    .then(r=>r.json()).then(d=>{
      const msg=document.getElementById('pwd-msg');
      if(d.ok){
        msg.style.color='#0D9488'; msg.textContent='✓ Password berhasil diupdate!'; msg.style.display='block';
        document.getElementById('new-password').value='';
        showToast('Password berhasil diubah','success');
      } else {
        msg.style.color='#EF4444'; msg.textContent=d.msg; msg.style.display='block';
      }
    });
}

// ── SPINNING ANIMATION ────────────────────────────────────
const spinStyle=document.createElement('style');
spinStyle.textContent='@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}';
document.head.appendChild(spinStyle);

// ── INIT ──────────────────────────────────────────────────
renderPersonal();
renderEducation();
renderResearch();
renderHardSkills();
renderSoftSkills();
renderExp('professional','professional-list');
renderExp('organisational','organisational-list');
renderExp('other','other-list');
renderProjects();
renderTestimonials();
renderLabels();
</script>

<?php endif; ?>
</body>
</html>
