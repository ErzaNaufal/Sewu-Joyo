<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Sistem Prediksi Stok</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:'Poppins',sans-serif;
        }

        body{
            min-height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
            background:linear-gradient(135deg,#0f172a,#1e293b);
            padding:20px;
        }

        .login-box{
            width:100%;
            max-width:420px;
            background:#ffffff;
            padding:35px;
            border-radius:15px;
            box-shadow:0 15px 35px rgba(0,0,0,.25);
        }

        .title{
            text-align:center;
            margin-bottom:25px;
        }

        .title h2{
            color:#0f172a;
            margin-bottom:5px;
        }

        .title p{
            color:#64748b;
            font-size:14px;
        }

        .alert-success{
            background:#22c55e;
            color:#fff;
            padding:12px;
            border-radius:8px;
            margin-bottom:15px;
            text-align:center;
        }

        .alert-error{
            background:#ef4444;
            color:#fff;
            padding:12px;
            border-radius:8px;
            margin-bottom:15px;
            text-align:center;
        }

        .form-group{
            margin-bottom:15px;
        }

        .form-group label{
            display:block;
            margin-bottom:6px;
            color:#334155;
            font-size:14px;
            font-weight:500;
        }

        .form-group input{
            width:100%;
            padding:12px;
            border:1px solid #cbd5e1;
            border-radius:8px;
            outline:none;
            transition:.2s;
        }

        .form-group input:focus{
            border-color:#38bdf8;
            box-shadow:0 0 5px rgba(56,189,248,.3);
        }

        button{
            width:100%;
            padding:12px;
            border:none;
            border-radius:8px;
            cursor:pointer;
            color:#fff;
            font-size:15px;
            font-weight:600;
            background:linear-gradient(135deg,#38bdf8,#6366f1);
            transition:.2s;
        }

        button:hover{
            opacity:.9;
            transform:translateY(-2px);
        }

        .footer{
            margin-top:15px;
            text-align:center;
            font-size:12px;
            color:#64748b;
        }

        @media (max-width:480px){

            .login-box{
                padding:25px;
            }

        }

    </style>

</head>
<body>

<div class="login-box">

    <div class="title">
        <h2>📊 Sewu Joyo</h2>
        <p>Sistem Prediksi Stok Barang</p>
    </div>

    @if(session('success'))
        <div id="success-alert" class="alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div id="error-alert" class="alert-error">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ url('/login') }}">

        @csrf

        <div class="form-group">
            <label for="username">Username</label>

            <input
                id="username"
                type="text"
                name="username"
                value="{{ old('username') }}"
                placeholder="Masukkan username"
                autocomplete="username"
                required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>

            <input
                id="password"
                type="password"
                name="password"
                placeholder="Masukkan password"
                autocomplete="current-password"
                required>
        </div>

        <button type="submit">
            Login
        </button>

    </form>

    <div class="footer">
        © {{ date('Y') }} Sistem Prediksi Stok Barang
    </div>

</div>

<script>

function hideAlert(id){

    const alertBox = document.getElementById(id);

    if(alertBox){

        setTimeout(function(){

            alertBox.style.transition = "0.5s";
            alertBox.style.opacity = "0";

            setTimeout(function(){

                alertBox.remove();

            },500);

        },3000);

    }

}

hideAlert('success-alert');
hideAlert('error-alert');

</script>

</body>
</html>