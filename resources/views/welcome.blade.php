<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sistem Prediksi Stok Obat</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {

            min-height: 100vh;

            display: flex;
            justify-content: center;
            align-items: center;

            background: linear-gradient(135deg, #0f172a, #1e293b);

            color: white;

            font-family: 'Poppins', sans-serif;

            transition: opacity .4s ease;

        }

        /* ===========================
           CONTAINER
        =========================== */

        .box {

            text-align: center;

            animation: fadeIn 1s ease;

            padding: 20px;

        }

        /* ===========================
           ICON
        =========================== */

        .icon {

            font-size: 56px;

            margin-bottom: 15px;

            animation: pulse 1.5s infinite;

        }

        /* ===========================
           TITLE
        =========================== */

        h1 {

            font-size: 28px;

            font-weight: 600;

            margin-bottom: 8px;

        }

        /* ===========================
           SUBTITLE
        =========================== */

        p {

            opacity: .75;

            margin-bottom: 25px;

            font-size: 15px;

        }

        /* ===========================
           LOADING BAR
        =========================== */

        .loading-bar {

            width: 240px;

            max-width: 80vw;

            height: 8px;

            background: rgba(255,255,255,.12);

            border-radius: 999px;

            overflow: hidden;

            margin: auto;

        }

        .loading-bar span {

            display: block;

            width: 0;

            height: 100%;

            border-radius: inherit;

            background: linear-gradient(90deg,#38bdf8,#6366f1);

            animation: load 2s ease forwards;

        }

        /* ===========================
           ANIMATION
        =========================== */

        @keyframes load {

            from {
                width: 0%;
            }

            to {
                width: 100%;
            }

        }

        @keyframes fadeIn {

            from {

                opacity: 0;

                transform: translateY(10px);

            }

            to {

                opacity: 1;

                transform: translateY(0);

            }

        }

        @keyframes pulse {

            0%,
            100% {

                transform: scale(1);

            }

            50% {

                transform: scale(1.08);

            }

        }
    </style>

</head>

<body>

    <div class="box">

        <div class="icon">💊</div>

        <h1>Sistem Prediksi Stok Obat</h1>

        <p>Memuat model Random Forest dan data historis...</p>

        <div class="loading-bar">
            <span></span>
        </div>

    </div>

    <script>

        setTimeout(() => {

            document.body.style.opacity = "0";

            setTimeout(() => {

                window.location.href = "{{ url('/dashboard') }}";

            }, 350);

        }, 2000);

    </script>

</body>

</html>