<?php
// ============================================================
//  PORTFOLIO DASHBOARD — Muhammad Fahmi Al Kahfi
//  Single-file admin panel. Password-protected via PHP session.
//  Change PASSWORD below before uploading to hosting!
// ============================================================
define('DASHBOARD_PASSWORD', 'fahmi2025');   // <-- GANTI PASSWORD DI SINI
define('DATA_FILE', __DIR__ . '/../data/portfolio.json');
define('SESSION_KEY', 'pf_admin_logged_in');

session_start();

/* ---- AJAX / POST handlers ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        if ($_POST['password'] === DASHBOARD_PASSWORD) {
            $_SESSION[SESSION_KEY] = true;
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false, 'msg' => 'Wrong password']);
        }
        exit;
    }

    if ($action === 'logout') {
        session_destroy();
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'save') {
        if (!$_SESSION[SESSION_KEY]) { http_response_code(403); exit; }
        $raw = $_POST['data'] ?? '';
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['ok' => false, 'msg' => 'Invalid JSON']);
            exit;
        }
        file_put_contents(DATA_FILE, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'change_password') {
        if (!$_SESSION[SESSION_KEY]) { http_response_code(403); exit; }
        $new = $_POST['new_password'] ?? '';
        if (strlen($new) < 6) { echo json_encode(['ok'=>false,'msg'=>'Min 6 characters']); exit; }
        // Rewrite this file with new password
        $content = file_get_contents(__FILE__);
        $content = preg_replace(
            "/define\('DASHBOARD_PASSWORD',\s*'[^']*'\)/",
            "define('DASHBOARD_PASSWORD', '" . addslashes($new) . "')",
            $content
        );
        file_put_contents(__FILE__, $content);
        echo json_encode(['ok' => true]);
        exit;
    }
}

/* ---- Auth gate ---- */
$loggedIn = !empty($_SESSION[SESSION_KEY]);

/* ---- Load current data ---- */
$portfolioData = [];
if (file_exists(DATA_FILE)) {
    $portfolioData = json_decode(file_get_contents(DATA_FILE), true) ?? [];
}
$json = json_encode($portfolioData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Portfolio Dashboard</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{font-size:14px}
body{font-family:-apple-system,BlinkMacSystemFont,'Inter',sans-serif;background:#F1F5F9;color:#1E293B;min-height:100vh}
a{text-decoration:none;color:inherit}
input,textarea,select{font-family:inherit;font-size:0.9rem}

/* Login */
.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#0f2744,#0D9488)}
.login-card{background:#fff;border-radius:20px;padding:2.5rem 2rem;width:100%;max-width:380px;box-shadow:0 20px 60px rgba(0,0,0,.25)}
.login-logo{text-align:center;margin-bottom:1.5rem}
.login-logo h1{font-size:1.2rem;font-weight:700;color:#1E293B;margin-top:.5rem}
.login-logo p{font-size:.8rem;color:#94A3B8;margin-top:2px}
.login-card input[type=password]{width:100%;padding:.75rem 1rem;border:1.5px solid #E2E8F0;border-radius:10px;outline:none;margin-bottom:.75rem;transition:border .2s}
.login-card input[type=password]:focus{border-color:#14B8A6}
.login-btn{width:100%;padding:.75rem;background:#14B8A6;color:#fff;border:none;border-radius:10px;font-weight:600;cursor:pointer;font-size:1rem;transition:background .2s}
.login-btn:hover{background:#0D9488}
.login-err{color:#E24B4A;font-size:.82rem;margin-bottom:.5rem;display:none}

/* Shell */
.shell{display:flex;min-height:100vh}
.sidebar{width:220px;background:#0f2744;flex-shrink:0;display:flex;flex-direction:column;position:sticky;top:0;height:100vh;overflow-y:auto}
.sidebar-brand{padding:1.25rem 1rem;border-bottom:1px solid rgba(255,255,255,.08)}
.sidebar-brand h2{color:#fff;font-size:.95rem;font-weight:700;line-height:1.3}
.sidebar-brand p{color:#64748B;font-size:.72rem;margin-top:2px}
.sidebar-nav{padding:.75rem 0;flex:1}
.nav-section{padding:.4rem .75rem;font-size:.65rem;font-weight:600;text-transform:uppercase;letter-spacing:.1em;color:#475569;margin-top:.5rem}
.nav-btn{display:flex;align-items:center;gap:.5rem;padding:.6rem 1rem;width:100%;background:none;border:none;color:#94A3B8;font-size:.85rem;cursor:pointer;border-radius:0;transition:all .15s;text-align:left}
.nav-btn:hover{color:#fff;background:rgba(255,255,255,.05)}
.nav-btn.active{color:#14B8A6;background:rgba(20,184,166,.1);border-right:3px solid #14B8A6}
.nav-btn svg{width:15px;height:15px;flex-shrink:0}
.sidebar-footer{padding:.75rem;border-top:1px solid rgba(255,255,255,.08)}
.logout-btn{display:flex;align-items:center;gap:.5rem;padding:.5rem .75rem;background:none;border:none;color:#64748B;font-size:.8rem;cursor:pointer;width:100%;border-radius:8px;transition:all .15s}
.logout-btn:hover{background:rgba(255,255,255,.05);color:#fff}

/* Main */
.main{flex:1;overflow-y:auto}
.topbar{background:#fff;border-bottom:1px solid #E2E8F0;padding:.75rem 1.5rem;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:100}
.topbar h3{font-size:1rem;font-weight:700;color:#1E293B}
.topbar-right{display:flex;align-items:center;gap:.75rem}
.save-btn{display:flex;align-items:center;gap:.4rem;background:#14B8A6;color:#fff;border:none;padding:.5rem 1.25rem;border-radius:20px;font-size:.85rem;font-weight:600;cursor:pointer;transition:background .2s}
.save-btn:hover{background:#0D9488}
.save-btn svg{width:15px;height:15px}
.save-status{font-size:.78rem;color:#94A3B8}
.save-status.ok{color:#0D9488}
.save-status.err{color:#E24B4A}

/* Content */
.content{padding:1.5rem;display:none}
.content.active{display:block}

/* Card */
.db-card{background:#fff;border:1px solid #E2E8F0;border-radius:16px;padding:1.5rem;margin-bottom:1.25rem}
.db-card-title{font-size:.85rem;font-weight:700;color:#1E293B;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem;padding-bottom:.75rem;border-bottom:1px solid #F1F5F9}
.db-card-title svg{width:16px;height:16px;color:#14B8A6}

/* Form */
.field{margin-bottom:1rem}
.field label{display:block;font-size:.78rem;font-weight:600;color:#475569;margin-bottom:.35rem;text-transform:uppercase;letter-spacing:.04em}
.field input,.field textarea,.field select{width:100%;padding:.6rem .85rem;border:1.5px solid #E2E8F0;border-radius:10px;outline:none;font-size:.88rem;color:#1E293B;transition:border .2s;background:#fff}
.field input:focus,.field textarea:focus{border-color:#14B8A6}
.field textarea{resize:vertical;line-height:1.6}
.field-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.field-row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:.75rem}

/* Array items */
.array-section{margin-bottom:1.25rem}
.array-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem}
.array-header h4{font-size:.85rem;font-weight:700;color:#1E293B}
.add-btn{display:flex;align-items:center;gap:.3rem;background:#14B8A6;color:#fff;border:none;padding:.4rem .9rem;border-radius:20px;font-size:.78rem;font-weight:600;cursor:pointer}
.add-btn:hover{background:#0D9488}
.array-item{background:#F8FAFC;border:1px solid #E2E8F0;border-radius:12px;padding:1rem;margin-bottom:.75rem;position:relative}
.item-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;cursor:pointer;user-select:none}
.item-title{font-size:.85rem;font-weight:600;color:#1E293B}
.item-actions{display:flex;gap:.4rem}
.del-btn{background:#FEE2E2;color:#E24B4A;border:none;border-radius:8px;width:26px;height:26px;cursor:pointer;display:flex;align-items:center;justify-content:center}
.del-btn:hover{background:#FECACA}
.del-btn svg{width:13px;height:13px}
.move-btn{background:#F1F5F9;color:#64748B;border:none;border-radius:8px;width:26px;height:26px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:12px}
.item-body{display:none}
.item-body.open{display:block}

/* Tags editor */
.tags-wrap{display:flex;flex-wrap:wrap;gap:.4rem;padding:.5rem;border:1.5px solid #E2E8F0;border-radius:10px;min-height:42px;cursor:text;background:#fff}
.tag-chip{display:inline-flex;align-items:center;gap:.3rem;background:#CCFBF1;color:#0D9488;padding:3px 10px;border-radius:20px;font-size:.78rem;font-weight:500}
.tag-chip button{background:none;border:none;cursor:pointer;color:#0D9488;font-size:1rem;line-height:1;padding:0}
.tag-input-inline{border:none;outline:none;font-size:.82rem;min-width:80px;color:#1E293B;background:transparent}

/* Proficiency slider */
.prof-wrap{display:flex;align-items:center;gap:.75rem}
.prof-wrap input[type=range]{flex:1;accent-color:#14B8A6;height:4px}
.prof-val{font-size:.85rem;font-weight:700;color:#14B8A6;min-width:36px;text-align:right}

/* Password change */
.pwd-row{display:flex;gap:.75rem;align-items:flex-end}
.pwd-row .field{flex:1;margin-bottom:0}
.pwd-btn{background:#1E293B;color:#fff;border:none;padding:.6rem 1.25rem;border-radius:10px;font-size:.85rem;font-weight:600;cursor:pointer;white-space:nowrap}
.pwd-btn:hover{background:#0f2744}

/* Info box */
.info-box{background:#CCFBF1;border:1px solid #14B8A6;border-radius:10px;padding:.85rem 1rem;font-size:.82rem;color:#0D9488;margin-bottom:1rem;line-height:1.6}
.warn-box{background:#FEF9C3;border:1px solid #FDE047;border-radius:10px;padding:.85rem 1rem;font-size:.82rem;color:#854D0E;margin-bottom:1rem;line-height:1.6}

/* Mobile */
@media(max-width:700px){.sidebar{display:none}.field-row,.field-row-3{grid-template-columns:1fr}}
</style>
</head>
<body>

<?php if (!$loggedIn): ?>
<!-- ============ LOGIN ============ -->
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">
      <svg width="44" height="44" viewBox="0 0 44 44" fill="none"><rect width="44" height="44" rx="12" fill="#14B8A6"/><path d="M14 22h16M22 14l8 8-8 8" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <h1>Portfolio Dashboard</h1>
      <p>Muhammad Fahmi Al Kahfi</p>
    </div>
    <p class="login-err" id="login-err">Wrong password. Try again.</p>
    <input type="password" id="pwd-input" placeholder="Enter password" onkeydown="if(event.key==='Enter')doLogin()"/>
    <button class="login-btn" onclick="doLogin()">Sign In</button>
  </div>
</div>
<script>
function doLogin(){
  const pwd=document.getElementById('pwd-input').value;
  fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=login&password='+encodeURIComponent(pwd)})
    .then(r=>r.json()).then(d=>{
      if(d.ok){location.reload();}
      else{document.getElementById('login-err').style.display='block';}
    });
}
</script>

<?php else: ?>
<!-- ============ DASHBOARD ============ -->
<div class="shell">

<!-- Sidebar -->
<aside class="sidebar">
  <div class="sidebar-brand">
    <h2>Portfolio Dashboard</h2>
    <p>Content Manager</p>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Content</div>
    <button class="nav-btn active" onclick="showTab('hero')" id="tab-hero">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
      Hero & About
    </button>
    <button class="nav-btn" onclick="showTab('academic')" id="tab-academic">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
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
    <div class="nav-section">UI Labels</div>
    <button class="nav-btn" onclick="showTab('labels')" id="tab-labels">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
      Section Labels
    </button>
    <div class="nav-section">Settings</div>
    <button class="nav-btn" onclick="showTab('settings')" id="tab-settings">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      Settings
    </button>
  </nav>
  <div class="sidebar-footer">
    <a href="../index.html" target="_blank" style="display:flex;align-items:center;gap:.4rem;padding:.5rem .75rem;color:#64748B;font-size:.8rem;">
      <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
      Preview Portfolio
    </a>
    <button class="logout-btn" onclick="doLogout()">
      <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
      Logout
    </button>
  </div>
</aside>

<!-- Main -->
<div class="main">
  <div class="topbar">
    <h3 id="topbar-title">Hero & About</h3>
    <div class="topbar-right">
      <span class="save-status" id="save-status"></span>
      <button class="save-btn" onclick="saveAll()">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
        Save Changes
      </button>
    </div>
  </div>

  <!-- ======= HERO & ABOUT ======= -->
  <div class="content active" id="content-hero">
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        Personal Identity
      </div>
      <div class="field-row">
        <div class="field"><label>Full Name</label><input type="text" id="personal.name"/></div>
        <div class="field"><label>Hero Tagline (big title)</label><input type="text" id="personal.tagline" placeholder="Welcome to my portfolio"/></div>
      </div>
      <div class="field"><label>Subtitle / Roles (below tagline)</label><input type="text" id="personal.title" placeholder="Mechanical Engineer | Researcher | Problem Solver"/></div>
    </div>
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        About Me Text
      </div>
      <div class="field"><label>About Paragraph</label><textarea id="personal.about" rows="7"></textarea></div>
    </div>
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        Contact Information
      </div>
      <div class="field-row">
        <div class="field"><label>Email</label><input type="email" id="personal.email"/></div>
        <div class="field"><label>Phone</label><input type="text" id="personal.phone"/></div>
      </div>
      <div class="field-row">
        <div class="field"><label>LinkedIn URL</label><input type="text" id="personal.linkedin"/></div>
        <div class="field"><label>Location</label><input type="text" id="personal.location"/></div>
      </div>
    </div>
  </div>

  <!-- ======= ACADEMIC ======= -->
  <div class="content" id="content-academic">
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/></svg>
        Education
      </div>
      <div id="education-list"></div>
      <button class="add-btn" onclick="addItem('education')">+ Add Education</button>
    </div>
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18"/></svg>
        Research / Laboratory Experience
      </div>
      <div id="research-list"></div>
      <button class="add-btn" onclick="addItem('research')">+ Add Research Entry</button>
    </div>
  </div>

  <!-- ======= SKILLS ======= -->
  <div class="content" id="content-skills">
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        Hard Skills
      </div>
      <div id="hardskill-list"></div>
      <button class="add-btn" onclick="addItem('hard')">+ Add Hard Skill</button>
    </div>
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Soft Skills
      </div>
      <div id="softskill-list"></div>
      <button class="add-btn" onclick="addItem('soft')">+ Add Soft Skill</button>
    </div>
  </div>

  <!-- ======= EXPERIENCE ======= -->
  <div class="content" id="content-experience">
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m8 0H8m8 0a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2"/></svg>
        Professional Experience
      </div>
      <div id="professional-list"></div>
      <button class="add-btn" onclick="addItem('professional')">+ Add Professional Entry</button>
    </div>
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
        Organisational Experience
      </div>
      <div id="organisational-list"></div>
      <button class="add-btn" onclick="addItem('organisational')">+ Add Organisational Entry</button>
    </div>
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
        Other Experience
      </div>
      <div id="other-list"></div>
      <button class="add-btn" onclick="addItem('other')">+ Add Other Entry</button>
    </div>
  </div>

  <!-- ======= PROJECTS ======= -->
  <div class="content" id="content-projects">
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        All Projects
      </div>
      <div id="projects-list"></div>
      <button class="add-btn" onclick="addItem('projects')">+ Add Project</button>
    </div>
  </div>

  <!-- ======= TESTIMONIALS ======= -->
  <div class="content" id="content-testimonials">
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        Testimonials — What People Think of Me
      </div>
      <div id="testimonials-list"></div>
      <button class="add-btn" onclick="addItem('testimonials')">+ Add Testimonial</button>
    </div>
  </div>

  <!-- ======= UI LABELS ======= -->
  <div class="content" id="content-labels">
    <div class="info-box">💡 Ini adalah label / teks yang muncul di judul dan subtitle setiap section di website. Anda bisa mengubah semua teks di sini.</div>
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
        Section Titles & Subtitles
      </div>
      <div class="field-row">
        <div class="field"><label>About Me — Section Title</label><input type="text" id="labels.aboutTitle"/></div>
        <div class="field"><label>Academic — Page Title</label><input type="text" id="labels.academicTitle"/></div>
      </div>
      <div class="field-row">
        <div class="field"><label>Academic — Page Subtitle</label><input type="text" id="labels.academicSubtitle"/></div>
        <div class="field"><label>Skills — Page Title</label><input type="text" id="labels.skillsTitle"/></div>
      </div>
      <div class="field-row">
        <div class="field"><label>Hard Skill — Section Title</label><input type="text" id="labels.hardSkillTitle"/></div>
        <div class="field"><label>Hard Skill — Subtitle</label><input type="text" id="labels.hardSkillSubtitle"/></div>
      </div>
      <div class="field-row">
        <div class="field"><label>Soft Skill — Section Title</label><input type="text" id="labels.softSkillTitle"/></div>
        <div class="field"><label>Soft Skill — Subtitle</label><input type="text" id="labels.softSkillSubtitle"/></div>
      </div>
      <div class="field-row">
        <div class="field"><label>Experiences — Page Title</label><input type="text" id="labels.experiencesTitle"/></div>
        <div class="field"><label>Experiences — Page Subtitle</label><input type="text" id="labels.experiencesSubtitle"/></div>
      </div>
      <div class="field-row">
        <div class="field"><label>Projects — Page Title</label><input type="text" id="labels.projectsTitle"/></div>
        <div class="field"><label>Projects — Page Subtitle</label><input type="text" id="labels.projectsSubtitle"/></div>
      </div>
      <div class="field-row">
        <div class="field"><label>Testimonials — Section Title</label><input type="text" id="labels.testimonialsTitle"/></div>
        <div class="field"><label>Let's Connect — Section Title</label><input type="text" id="labels.connectTitle"/></div>
      </div>
      <div class="field"><label>Let's Connect — Description Text</label><textarea id="labels.connectDesc" rows="3"></textarea></div>
    </div>
  </div>

  <!-- ======= SETTINGS ======= -->
  <div class="content" id="content-settings">
    <div class="warn-box">⚠️ Ganti password sebelum mengupload ke hosting agar dashboard tidak bisa diakses orang lain.</div>
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        Change Dashboard Password
      </div>
      <div class="pwd-row">
        <div class="field"><label>New Password (min 6 characters)</label><input type="password" id="new-password" placeholder="Enter new password"/></div>
        <button class="pwd-btn" onclick="changePassword()">Update Password</button>
      </div>
      <p id="pwd-msg" style="font-size:.8rem;margin-top:.5rem;color:#0D9488;display:none"></p>
    </div>
    <div class="db-card">
      <div class="db-card-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Security Tips
      </div>
      <div class="info-box" style="margin-bottom:.75rem">
        <strong>1. Ganti password default</strong> — Gunakan form di atas sebelum upload ke hosting.<br/>
        <strong>2. Akses dashboard</strong> — Dashboard ada di <code>yourdomain.com/dashboard/</code> — jangan bagikan URL ini ke siapapun.<br/>
        <strong>3. Double security via cPanel</strong> — Login ke cPanel hosting → "Password Protect Directories" → pilih folder <code>dashboard</code> → aktifkan password. Ini menambahkan layer keamanan ekstra di level server.
      </div>
    </div>
  </div>

</div><!-- /main -->
</div><!-- /shell -->

<script>
// ============ DATA STATE ============
let D = <?= $json ?>;

// Default labels if not in JSON
const defaultLabels = {
  aboutTitle: 'About Me',
  academicTitle: 'Academic background',
  academicSubtitle: 'Building expertise through rigorous education and hands-on research experience',
  skillsTitle: 'My Skills',
  hardSkillTitle: 'Hard skill',
  hardSkillSubtitle: 'Technical competencies in engineering software and computational methods',
  softSkillTitle: 'Soft skill',
  softSkillSubtitle: 'Interpersonal abilities that drive collaboration and project success',
  experiencesTitle: 'Experiences',
  experiencesSubtitle: 'Professional work and organisational leadership that shaped my career journey',
  projectsTitle: 'Projects',
  projectsSubtitle: 'Showcasing engineering solutions and research contributions',
  testimonialsTitle: 'What People Think of Me',
  connectTitle: "Let's Connect",
  connectDesc: "Whether you have a project in mind, a research collaboration, or just want to connect — I am always open to meaningful conversations."
};
if (!D.labels) D.labels = {};
Object.keys(defaultLabels).forEach(k => {
  if (!D.labels[k]) D.labels[k] = defaultLabels[k];
});

// ============ TAB NAVIGATION ============
function showTab(name) {
  document.querySelectorAll('.content').forEach(c => c.classList.remove('active'));
  document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('content-'+name).classList.add('active');
  document.getElementById('tab-'+name).classList.add('active');
  const titles = {hero:'Hero & About',academic:'Academic',skills:'Skills',experience:'Experiences',projects:'Projects',testimonials:'Testimonials',labels:'Section Labels',settings:'Settings'};
  document.getElementById('topbar-title').textContent = titles[name] || name;
}

// ============ RENDER HELPERS ============
function field(label, id, value, type='text', rows=3){
  if(type==='textarea')
    return `<div class="field"><label>${label}</label><textarea id="${id}" rows="${rows}">${esc(value||'')}</textarea></div>`;
  return `<div class="field"><label>${label}</label><input type="${type}" id="${id}" value="${esc(value||'')}"/></div>`;
}
function fieldRow(...fields){ return `<div class="field-row">${fields.join('')}</div>`; }
function esc(s){ return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// Tags widget
function renderTagsWidget(id, tags){
  const chips = (tags||[]).map(t=>`<span class="tag-chip">${esc(t)}<button onclick="removeTag('${id}',this)" title="Remove">×</button></span>`).join('');
  return `<div class="field"><label>Tags (press Enter to add)</label>
    <div class="tags-wrap" id="tags-${id}" onclick="focusTagInput('${id}')">
      ${chips}
      <input class="tag-input-inline" id="taginput-${id}" placeholder="Add tag..." onkeydown="onTagKey(event,'${id}')"/>
    </div></div>`;
}
function focusTagInput(id){ document.getElementById('taginput-'+id).focus(); }
function onTagKey(e,id){
  if(e.key==='Enter'||e.key===','){
    e.preventDefault();
    const inp=document.getElementById('taginput-'+id);
    const val=inp.value.trim().replace(',','');
    if(val){ addTagChip(id,val); inp.value=''; }
  }
}
function addTagChip(wid, tag){
  const wrap=document.getElementById('tags-'+wid);
  const inp=document.getElementById('taginput-'+wid);
  const chip=document.createElement('span');
  chip.className='tag-chip';
  chip.innerHTML=`${esc(tag)}<button onclick="removeTag('${wid}',this)" title="Remove">×</button>`;
  wrap.insertBefore(chip,inp);
}
function removeTag(wid,btn){ btn.closest('.tag-chip').remove(); }
function getTagsFromWidget(wid){
  return [...document.querySelectorAll(`#tags-${wid} .tag-chip`)].map(c=>c.textContent.trim().slice(0,-1));
}

// Proficiency slider
function profSlider(id, val){
  return `<div class="field"><label>Proficiency (%)</label>
    <div class="prof-wrap">
      <input type="range" min="0" max="100" value="${val||80}" id="${id}" oninput="this.nextElementSibling.textContent=this.value+'%'"/>
      <span class="prof-val">${val||80}%</span>
    </div></div>`;
}

// Collapse / expand item
function toggleItem(btn){
  const body=btn.closest('.array-item').querySelector('.item-body');
  body.classList.toggle('open');
  btn.textContent = body.classList.contains('open') ? '▲' : '▼';
}

// Delete item
function delItem(btn, listId){
  if(confirm('Delete this entry?')) btn.closest('.array-item').remove();
}

// ============ RENDER SECTIONS ============
function renderEducation(){
  const list=document.getElementById('education-list');
  list.innerHTML=(D.academic?.education||[]).map((e,i)=>`
    <div class="array-item">
      <div class="item-header">
        <span class="item-title">${esc(e.institution||'New Education')}</span>
        <div class="item-actions">
          <button class="move-btn" onclick="toggleItem(this)">▼</button>
          <button class="del-btn" onclick="delItem(this,'education-list')"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
        </div>
      </div>
      <div class="item-body open">
        ${fieldRow(field('Institution','edu-institution-'+i,e.institution),field('Degree','edu-degree-'+i,e.degree))}
        ${fieldRow(field('Period','edu-period-'+i,e.period),field('GPA / Score','edu-gpa-'+i,e.gpa))}
        ${field('Brief Description','edu-desc-'+i,e.description,'textarea',4)}
      </div>
    </div>`).join('');
}

function renderResearch(){
  const list=document.getElementById('research-list');
  list.innerHTML=(D.academic?.research||[]).map((r,i)=>`
    <div class="array-item">
      <div class="item-header">
        <span class="item-title">${esc(r.title||'New Research')}</span>
        <div class="item-actions">
          <button class="move-btn" onclick="toggleItem(this)">▼</button>
          <button class="del-btn" onclick="delItem(this,'research-list')"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
        </div>
      </div>
      <div class="item-body open">
        ${fieldRow(field('Lab / Institution Name','res-title-'+i,r.title),field('Your Role','res-role-'+i,r.role))}
        ${fieldRow(field('University / Org','res-institution-'+i,r.institution),field('Period','res-period-'+i,r.period))}
        ${field('Contribution','res-contribution-'+i,r.contribution)}
        ${field('Description','res-desc-'+i,r.description,'textarea',4)}
        ${field('Paper Link (optional)','res-paperLink-'+i,r.paperLink||'')}
      </div>
    </div>`).join('');
}

function renderHardSkills(){
  const list=document.getElementById('hardskill-list');
  list.innerHTML=(D.skills?.hard||[]).map((s,i)=>`
    <div class="array-item">
      <div class="item-header">
        <span class="item-title">${esc(s.title||'New Skill')}</span>
        <div class="item-actions">
          <button class="move-btn" onclick="toggleItem(this)">▼</button>
          <button class="del-btn" onclick="delItem(this,'hardskill-list')"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
        </div>
      </div>
      <div class="item-body open">
        ${field('Skill Title','hs-title-'+i,s.title)}
        ${profSlider('hs-proficiency-'+i,s.proficiency)}
        ${field('Description','hs-desc-'+i,s.description,'textarea',4)}
        ${field('Certificate Link (optional)','hs-certLink-'+i,s.certLink||'')}
      </div>
    </div>`).join('');
}

function renderSoftSkills(){
  const list=document.getElementById('softskill-list');
  list.innerHTML=(D.skills?.soft||[]).map((s,i)=>`
    <div class="array-item">
      <div class="item-header">
        <span class="item-title">${esc(s.title||'New Skill')}</span>
        <div class="item-actions">
          <button class="move-btn" onclick="toggleItem(this)">▼</button>
          <button class="del-btn" onclick="delItem(this,'softskill-list')"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
        </div>
      </div>
      <div class="item-body open">
        ${field('Skill Title','ss-title-'+i,s.title)}
        ${field('Description','ss-desc-'+i,s.description,'textarea',3)}
      </div>
    </div>`).join('');
}

function renderExperience(type, listId, label){
  const list=document.getElementById(listId);
  const items = D.experiences?.[type]||[];
  list.innerHTML=items.map((e,i)=>`
    <div class="array-item">
      <div class="item-header">
        <span class="item-title">${esc(e.title||'New Entry')}</span>
        <div class="item-actions">
          <button class="move-btn" onclick="toggleItem(this)">▼</button>
          <button class="del-btn" onclick="delItem(this,'${listId}')"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
        </div>
      </div>
      <div class="item-body open">
        ${fieldRow(field('Title / Position','exp-'+type+'-title-'+i,e.title),field('Organisation / Company','exp-'+type+'-org-'+i,e.organization||e.organisation||''))}
        ${fieldRow(field('Period','exp-'+type+'-period-'+i,e.period),field('Location','exp-'+type+'-location-'+i,e.location||''))}
        ${field('Description','exp-'+type+'-desc-'+i,e.description,'textarea',4)}
        ${field('Link (optional)','exp-'+type+'-link-'+i,e.link||'')}
      </div>
    </div>`).join('');
}

function renderProjects(){
  const list=document.getElementById('projects-list');
  list.innerHTML=(D.projects||[]).map((p,i)=>`
    <div class="array-item">
      <div class="item-header">
        <span class="item-title">${esc(p.title||'New Project')}</span>
        <div class="item-actions">
          <button class="move-btn" onclick="toggleItem(this)">▼</button>
          <button class="del-btn" onclick="delItem(this,'projects-list')"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
        </div>
      </div>
      <div class="item-body open">
        ${fieldRow(field('Project Title','proj-title-'+i,p.title),field('Year','proj-year-'+i,p.year))}
        ${field('Headline (short description)','proj-headline-'+i,p.headline)}
        ${field('Full Description','proj-desc-'+i,p.description,'textarea',5)}
        ${renderTagsWidget('proj-'+i, p.tags)}
        ${field('External Link (optional)','proj-link-'+i,p.externalLink||'')}
      </div>
    </div>`).join('');
}

function renderTestimonials(){
  const list=document.getElementById('testimonials-list');
  list.innerHTML=(D.testimonials||[]).map((t,i)=>`
    <div class="array-item">
      <div class="item-header">
        <span class="item-title">${esc(t.name||'New Testimonial')}</span>
        <div class="item-actions">
          <button class="move-btn" onclick="toggleItem(this)">▼</button>
          <button class="del-btn" onclick="delItem(this,'testimonials-list')"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
        </div>
      </div>
      <div class="item-body open">
        ${fieldRow(field('Name','test-name-'+i,t.name),field('Title / Position','test-title-'+i,t.title))}
        ${field('Quote','test-quote-'+i,t.quote,'textarea',3)}
      </div>
    </div>`).join('');
}

function renderLabels(){
  const L=D.labels||{};
  Object.keys(defaultLabels).forEach(k=>{
    const el=document.getElementById('labels.'+k);
    if(el) el.value=L[k]||defaultLabels[k];
  });
}

function renderPersonal(){
  const P=D.personal||{};
  ['name','tagline','title','about','email','phone','linkedin','location'].forEach(k=>{
    const el=document.getElementById('personal.'+k);
    if(el) el.value=P[k]||'';
  });
}

// ============ ADD NEW ITEM ============
const newItems = {
  education: {institution:'',degree:'',period:'',gpa:'',description:''},
  research: {title:'',role:'',institution:'',period:'',contribution:'',description:'',paperLink:null},
  hard: {id:'new',title:'',type:'Hard Skill',proficiency:75,description:'',certLink:null,images:[]},
  soft: {id:'new',title:'',type:'Soft Skill',description:'',images:[]},
  professional: {id:'new',title:'',organization:'',location:'',period:'',description:'',image:null,link:null},
  organisational: {id:'new',title:'',organization:'',location:'',period:'',description:'',image:null,link:null},
  other: {id:'new',title:'',organization:'',period:'',description:'',image:null,link:null},
  projects: {id:'new',title:'',headline:'',year:new Date().getFullYear().toString(),description:'',tags:[],thumbnail:null,externalLink:null},
  testimonials: {id:'new',name:'',title:'',quote:''}
};

function addItem(type){
  const maps = {
    education: ()=>{ D.academic.education.push({...newItems.education}); renderEducation(); },
    research: ()=>{ D.academic.research.push({...newItems.research}); renderResearch(); },
    hard: ()=>{ D.skills.hard.push({...newItems.hard}); renderHardSkills(); },
    soft: ()=>{ D.skills.soft.push({...newItems.soft}); renderSoftSkills(); },
    professional: ()=>{ D.experiences.professional.push({...newItems.professional}); renderExperience('professional','professional-list'); },
    organisational: ()=>{ D.experiences.organisational.push({...newItems.organisational}); renderExperience('organisational','organisational-list'); },
    other: ()=>{ D.experiences.other.push({...newItems.other}); renderExperience('other','other-list'); },
    projects: ()=>{ D.projects.push({...newItems.projects}); renderProjects(); },
    testimonials: ()=>{ D.testimonials.push({...newItems.testimonials}); renderTestimonials(); }
  };
  if(maps[type]) maps[type]();
}

// ============ COLLECT DATA ============
function val(id){ const el=document.getElementById(id); return el?el.value.trim():''; }
function numVal(id){ const el=document.getElementById(id); return el?parseInt(el.value):0; }

function collectData(){
  // Personal
  D.personal.name = val('personal.name');
  D.personal.tagline = val('personal.tagline');
  D.personal.title = val('personal.title');
  D.personal.about = val('personal.about');
  D.personal.email = val('personal.email');
  D.personal.phone = val('personal.phone');
  D.personal.linkedin = val('personal.linkedin');
  D.personal.location = val('personal.location');

  // Labels
  Object.keys(defaultLabels).forEach(k=>{
    const el=document.getElementById('labels.'+k);
    if(el) D.labels[k]=el.value.trim()||defaultLabels[k];
  });

  // Education
  const eduItems=document.querySelectorAll('#education-list .array-item');
  D.academic.education = [...eduItems].map((_,i)=>({
    institution: val('edu-institution-'+i), degree: val('edu-degree-'+i),
    period: val('edu-period-'+i), gpa: val('edu-gpa-'+i),
    description: val('edu-desc-'+i)
  }));

  // Research
  const resItems=document.querySelectorAll('#research-list .array-item');
  D.academic.research = [...resItems].map((_,i)=>({
    title: val('res-title-'+i), role: val('res-role-'+i),
    institution: val('res-institution-'+i), period: val('res-period-'+i),
    contribution: val('res-contribution-'+i), description: val('res-desc-'+i),
    paperLink: val('res-paperLink-'+i)||null
  }));

  // Hard Skills
  const hsItems=document.querySelectorAll('#hardskill-list .array-item');
  D.skills.hard = [...hsItems].map((_,i)=>({
    id: D.skills.hard[i]?.id||'skill-'+i,
    title: val('hs-title-'+i), type:'Hard Skill',
    proficiency: numVal('hs-proficiency-'+i),
    description: val('hs-desc-'+i),
    certLink: val('hs-certLink-'+i)||null, images:[]
  }));

  // Soft Skills
  const ssItems=document.querySelectorAll('#softskill-list .array-item');
  D.skills.soft = [...ssItems].map((_,i)=>({
    id: D.skills.soft[i]?.id||'soft-'+i,
    title: val('ss-title-'+i), type:'Soft Skill',
    description: val('ss-desc-'+i), images:[]
  }));

  // Experiences
  ['professional','organisational','other'].forEach(type=>{
    const listId = type+'-list';
    const items = document.querySelectorAll('#'+listId+' .array-item');
    D.experiences[type] = [...items].map((_,i)=>({
      id: D.experiences[type][i]?.id||type+'-'+i,
      title: val('exp-'+type+'-title-'+i),
      organization: val('exp-'+type+'-org-'+i),
      location: val('exp-'+type+'-location-'+i),
      period: val('exp-'+type+'-period-'+i),
      description: val('exp-'+type+'-desc-'+i),
      link: val('exp-'+type+'-link-'+i)||null, image:null
    }));
  });

  // Projects
  const projItems=document.querySelectorAll('#projects-list .array-item');
  D.projects = [...projItems].map((_,i)=>({
    id: D.projects[i]?.id||'proj-'+i,
    title: val('proj-title-'+i),
    headline: val('proj-headline-'+i),
    year: val('proj-year-'+i),
    description: val('proj-desc-'+i),
    tags: getTagsFromWidget('proj-'+i),
    externalLink: val('proj-link-'+i)||null, thumbnail:null
  }));

  // Testimonials
  const testItems=document.querySelectorAll('#testimonials-list .array-item');
  D.testimonials = [...testItems].map((_,i)=>({
    id: D.testimonials[i]?.id||'test-'+i,
    name: val('test-name-'+i),
    title: val('test-title-'+i),
    quote: val('test-quote-'+i)
  }));

  return D;
}

// ============ SAVE ============
function saveAll(){
  const data = collectData();
  const btn = document.querySelector('.save-btn');
  const status = document.getElementById('save-status');
  btn.disabled=true; btn.textContent='Saving…';
  status.className='save-status'; status.textContent='';

  fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'action=save&data='+encodeURIComponent(JSON.stringify(data))})
    .then(r=>r.json()).then(d=>{
      if(d.ok){ status.className='save-status ok'; status.textContent='✓ Saved successfully!'; }
      else { status.className='save-status err'; status.textContent='Error: '+d.msg; }
    }).catch(()=>{ status.className='save-status err'; status.textContent='Connection error'; })
    .finally(()=>{ btn.disabled=false; btn.innerHTML='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:15px;height:15px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg> Save Changes'; setTimeout(()=>status.textContent='',4000); });
}

// ============ LOGOUT ============
function doLogout(){
  fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=logout'})
    .then(()=>location.reload());
}

// ============ CHANGE PASSWORD ============
function changePassword(){
  const p=document.getElementById('new-password').value;
  if(p.length<6){ alert('Minimum 6 characters'); return; }
  fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=change_password&new_password='+encodeURIComponent(p)})
    .then(r=>r.json()).then(d=>{
      const msg=document.getElementById('pwd-msg');
      if(d.ok){ msg.textContent='✓ Password updated!'; msg.style.display='block'; document.getElementById('new-password').value=''; }
      else { msg.style.color='#E24B4A'; msg.textContent=d.msg; msg.style.display='block'; }
    });
}

// ============ INIT ============
renderPersonal();
renderEducation();
renderResearch();
renderHardSkills();
renderSoftSkills();
renderExperience('professional','professional-list');
renderExperience('organisational','organisational-list');
renderExperience('other','other-list');
renderProjects();
renderTestimonials();
renderLabels();
</script>

<?php endif; ?>
</body>
</html>
