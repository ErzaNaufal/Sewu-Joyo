<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Memuat Sistem...</title>

<style>
body {
    margin:0;
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background: linear-gradient(135deg, #0f172a, #1e293b);
    color:white;
    font-family: 'Poppins', sans-serif;
}

/* CONTAINER */
.box {
    text-align:center;
    animation: fadeIn 1s ease;
}

/* ICON */
.icon {
    font-size:50px;
    margin-bottom:10px;
    animation: pulse 1.5s infinite;
}

/* TITLE */
h1 {
    margin:0;
    font-size:22px;
    letter-spacing:1px;
}

/* SUBTEXT */
p {
    opacity:0.7;
    margin:8px 0 20px;
}

/* LOADING BAR */
.loading-bar {
    width:200px;
    height:6px;
    background:#1e293b;
    border-radius:10px;
    overflow:hidden;
    margin:auto;
}

.loading-bar span {
    display:block;
    height:100%;
    width:0%;
    background: linear-gradient(90deg,#38bdf8,#6366f1);
    animation: load 2s ease forwards;
}

/* ANIMATIONS */
@keyframes load {
    from { width:0%; }
    to { width:100%; }
}

@keyframes fadeIn {
    from { opacity:0; transform:translateY(10px); }
    to { opacity:1; transform:translateY(0); }
}

@keyframes pulse {
    0%,100% { transform:scale(1); }
    50% { transform:scale(1.1); }
}

</style>
</head>

<body>

<div class="box">

    <div class="icon">📊</div>

    <h1>Sistem Prediksi Stok</h1>
    <p>Menyiapkan data dan model...</p>

    <div class="loading-bar">
        <span></span>
    </div>

</div>

<script>
// redirect setelah loading selesai
setTimeout(()=>{
    window.location.href="/dashboard"
}, 2000);
</script>

</body>
</html>