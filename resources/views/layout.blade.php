<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>@yield('title', 'Sewu Joyo')</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

/* =========================
   GLOBAL
========================= */

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
    transition:0.3s;
}

body{
    display:flex;
    background:#f8fafc;
    color:#0f172a;
}

body.dark{
    background:#020617;
    color:#e2e8f0;
}

/* =========================
   SIDEBAR
========================= */

.sidebar{
    width:240px;
    min-height:100vh;
    background:#ffffff;
    position:fixed;
    left:0;
    top:0;
    padding:25px 15px;
    border-right:1px solid rgba(0,0,0,.05);
}

body.dark .sidebar{
    background:#020617;
    border-right:1px solid rgba(255,255,255,.05);
}

.sidebar h2{
    text-align:center;
    color:#38bdf8;
    margin-bottom:10px;
}

.user-info{
    text-align:center;
    margin-bottom:25px;
    color:#64748b;
    font-size:13px;
}

body.dark .user-info{
    color:#94a3b8;
}

/* =========================
   MENU
========================= */

.menu{
    display:flex;
    flex-direction:column;
    gap:8px;
}

.menu a{
    text-decoration:none;
    color:#64748b;
    padding:12px 15px;
    border-radius:10px;
    transition:.2s ease;
}

body.dark .menu a{
    color:#94a3b8;
}

.menu a:hover{
    background:rgba(56,189,248,.1);
    color:#38bdf8;
    transform:translateX(5px);
}

.menu a.active{
    background:linear-gradient(135deg,#38bdf8,#6366f1);
    color:#fff;
}

/* =========================
   LOGOUT
========================= */

.logout-form{
    margin-top:15px;
}

.logout-btn{
    width:100%;
    padding:12px;
    border:none;
    border-radius:10px;
    background:#ef4444;
    color:#fff;
    cursor:pointer;
    font-size:14px;
    font-weight:500;
}

.logout-btn:hover{
    background:#dc2626;
}

/* =========================
   CONTENT
========================= */

.content{
    width:100%;
    margin-left:240px;
    padding:35px;
}

/* =========================
   CARD
========================= */

.card{
    background:#ffffff;
    border-radius:12px;
    padding:20px;
    box-shadow:0 5px 20px rgba(0,0,0,.05);
}

body.dark .card{
    background:#0f172a;
    box-shadow:none;
}

/* =========================
   GRID
========================= */

.grid{
    display:grid;
    gap:15px;
}

/* =========================
   FORM
========================= */

.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:15px;
}

.form-group input,
.form-group select{
    width:100%;
    padding:12px;
    border-radius:8px;
    border:1px solid #e2e8f0;
}

body.dark input,
body.dark select{
    background:#020617;
    color:#fff;
    border:1px solid #1e293b;
}

/* =========================
   BUTTON
========================= */

button{
    background:linear-gradient(135deg,#38bdf8,#6366f1);
    border:none;
    padding:12px;
    color:#fff;
    border-radius:8px;
    cursor:pointer;
}

button:hover{
    opacity:.9;
    transform:translateY(-2px);
}

/* =========================
   DARK MODE
========================= */

.toggle-wrapper{
    position:fixed;
    top:20px;
    right:20px;
    z-index:999;
}

.toggle{
    width:55px;
    height:28px;
    background:#cbd5e1;
    border-radius:20px;
    position:relative;
    cursor:pointer;
}

.toggle-circle{
    width:22px;
    height:22px;
    background:#fff;
    border-radius:50%;
    position:absolute;
    top:3px;
    left:4px;
}

body.dark .toggle{
    background:#6366f1;
}

body.dark .toggle-circle{
    transform:translateX(26px);
}

/* =========================
   RESPONSIVE
========================= */

@media (max-width:768px){

    body{
        flex-direction:column;
    }

    .sidebar{
        position:relative;
        width:100%;
        min-height:auto;
    }

    .content{
        margin-left:0;
        padding:20px;
    }

    .form-grid{
        grid-template-columns:1fr;
    }

}

</style>

</head>

<body>

<div class="toggle-wrapper" onclick="toggleMode()">
    <div class="toggle">
        <div class="toggle-circle"></div>
    </div>
</div>

<div class="sidebar">

    <h2>📊 Sewu Joyo</h2>

    <div class="user-info">
        Login :
        <strong>{{ session('username', 'Admin') }}</strong>
    </div>

    <div class="menu">

        <a href="{{ url('/dashboard') }}"
           class="{{ request()->is('dashboard*') ? 'active' : '' }}">
            📈 Dashboard
        </a>

        <a href="{{ url('/penjualan') }}"
           class="{{ request()->is('penjualan*') ? 'active' : '' }}">
            🛒 Penjualan
        </a>

        <a href="{{ url('/prediksi') }}"
           class="{{ request()->is('prediksi*') ? 'active' : '' }}">
            🤖 Prediksi
        </a>

        <a href="{{ url('/analisis') }}"
           class="{{ request()->is('analisis*') ? 'active' : '' }}">
            📊 Analisis
        </a>

        <a href="{{ url('/laporan') }}"
           class="{{ request()->is('laporan*') ? 'active' : '' }}">
            📄 Laporan
        </a>

        <form action="{{ route('logout') }}"
              method="POST"
              class="logout-form"
              onsubmit="return confirm('Yakin ingin logout?')">

            @csrf

            <button type="submit" class="logout-btn">
                🚪 Logout
            </button>

        </form>

    </div>

</div>

<div class="content">

    @if(session('success'))
        <div style="background:#22c55e;color:white;padding:12px;border-radius:10px;margin-bottom:20px;">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="background:#ef4444;color:white;padding:12px;border-radius:10px;margin-bottom:20px;">
            {{ session('error') }}
        </div>
    @endif

    @yield('content')

</div>

<script>

function toggleMode(){

    document.body.classList.toggle('dark');

    localStorage.setItem(
        'mode',
        document.body.classList.contains('dark') ? 'dark' : 'light'
    );

}

window.addEventListener('load', function(){

    if(localStorage.getItem('mode') === 'dark'){
        document.body.classList.add('dark');
    }

});

</script>

</body>
</html>