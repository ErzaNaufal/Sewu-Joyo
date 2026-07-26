@extends('layout')

@section('title','Prediksi')

@section('content')

<style>

.summary-grid{
    grid-template-columns:
    repeat(auto-fit,minmax(220px,1fr));

    gap:20px;
    margin-bottom:20px;
}

/* =========================
    FIX LAYOUT
========================= */
.main-grid{
    display:grid;

    grid-template-columns:
    minmax(420px,1fr)
    minmax(350px,420px);

    gap:20px;
    align-items:start;
}

@media(max-width:1000px){

    .main-grid{
        grid-template-columns:1fr;
    }

}

/* =========================
    CARD
========================= */
.stat-card{
    text-align:center;
}

.hasil-card{
    position:sticky;
    top:20px;
}

.hasil-box{
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    text-align:center;
    min-height:520px;
}

/* =========================
    ANGKA PREDIKSI
========================= */
.prediksi-value{
    font-size:70px;
    font-weight:700;
    color:#38bdf8;
    margin:10px 0;
}

/* =========================
    SUMMARY
========================= */
.summary-box{
    margin-top:20px;
    line-height:2;
}

/* =========================
    REKOMENDASI
========================= */
.rekom-box{
    margin-top:15px;
    width:100%;
    padding:12px;
    border-radius:10px;
    background:rgba(56,189,248,0.12);
}

/* =========================
    FEATURE BOX
========================= */
.feature-box{

    margin-top:20px;

    width:100%;

    padding:15px;

    border-radius:12px;

    background:
    rgba(255,255,255,0.04);

    text-align:left;

    line-height:2;

    font-size:14px;
}

.feature-grid{

    display:grid;

    grid-template-columns:
    1fr 1fr;

    gap:10px;
}

.feature-item{

    padding:10px;

    border-radius:10px;

    background:
    rgba(255,255,255,0.03);
}

/* =========================
    BADGE
========================= */
.badge{
    padding:8px 18px;
    border-radius:999px;
    font-weight:700;
    display:inline-block;
    margin-top:10px;
}

.badge.tinggi{
    background:#ef4444;
    color:white;
}

.badge.rendah{
    background:#facc15;
    color:black;
}

.badge.normal{
    background:#22c55e;
    color:white;
}

/* =========================
    TABLE
========================= */
.table{
    width:100%;
    border-collapse:collapse;
    text-align:center;
}

.table th{
    padding:10px;

    border-bottom:
    2px solid rgba(255,255,255,0.1);
}

.table td{
    padding:10px;

    border-bottom:
    1px solid rgba(255,255,255,0.05);
}

.left{
    text-align:left;
}

/* =========================
    CHART
========================= */
.chart-wrapper{

    width:100%;

    overflow-x:auto;

}

.chart-wrapper canvas{

    width:100% !important;

    max-width:100%;

}

/* =========================
    INFO PREDIKSI
========================= */
.info-box{
    margin-top:20px;
    padding:15px;
    border-radius:12px;
    background:rgba(255,255,255,0.04);
    line-height:1.8;
    font-size:14px;
}

body.dark .info-box{
    background:rgba(255,255,255,0.03);
}

</style>

<h1 style="margin-bottom:25px;">
    🤖 Sistem Prediksi Stok
</h1>

{{-- ALERT --}}
@if(session('success'))

<div style="
background:#22c55e;
padding:12px;
border-radius:10px;
margin-bottom:20px;
color:white;
">

    {{ session('success') }}

</div>

@endif

@if(session('error'))
<div style="
background:#ef4444;
padding:12px;
border-radius:10px;
margin-bottom:20px;
color:white;
">
    {{ session('error') }}
</div>
@endif

{{-- =======================
    INFO MODEL
======================= --}}
<div class="grid summary-grid">

    {{-- MODEL --}}
    <div class="card stat-card">
        <p>Model</p>
        <h2 style="color:#38bdf8;">
            Random Forest
        </h2>
    </div>

    {{-- STATUS --}}
    <div class="card stat-card">
        <p>Status</p>
        <h2 style="color:#22c55e;">
            Aktif
        </h2>
    </div>

{{-- EVALUASI MODEL --}}
<div class="card stat-card">

    <p>Evaluasi Model</p>

    @if(!empty($metrics))

        <h2 style="color:#facc15;">
            R² {{ number_format($metrics['r2'], 4) }}
        </h2>

        <small style="display:block; line-height:1.8;">
            MAE : {{ number_format($metrics['mae'], 2) }} <br>
            MSE : {{ number_format($metrics['mse'], 2) }} <br>
            RMSE : {{ number_format($metrics['rmse'], 2) }}
        </small>

    @else

        <h2 style="opacity:.5;">-</h2>

        <small>
            Belum ada evaluasi
        </small>

    @endif

</div>

</div> {{-- ← Penutup summary-grid --}}

{{-- =======================
    FORM + HASIL
======================= --}}
<div class="main-grid">
    {{-- FORM --}}
    <div class="card">

        <h3 style="margin-bottom:20px;">
            📥 Input Prediksi
        </h3>

        <form method="POST" action="{{ url('/prediksi') }}">
        @csrf

        <div class="form-grid">

            {{-- PRODUK --}}
            <div class="form-group">

                <label>Produk</label>

                <select name="produk" required>

                    <option value="">
                        -- Pilih Produk --
                    </option>

                    @foreach($produk as $p)

                    <option
                        value="{{ $p }}"
                        {{ old('produk') == $p ? 'selected' : '' }}>
                        {{ $p }}
                    </option>

                    @endforeach

                </select>

            </div>

            {{-- TANGGAL --}}
            <div class="form-group">

                <label>Tanggal Prediksi</label>

                <input
                    type="date"
                    name="tanggal"
                    value="{{ old('tanggal') }}"
                    required
                >

            </div>

            {{-- PENJUALAN --}}

        </div>

        <br>

        <button type="submit">
            🚀 Prediksi Sekarang
        </button>

        {{-- INFO --}}
        <div class="info-box">

            <b>📌 Cara Kerja Sistem</b>

            <br><br>

            Sistem akan otomatis:

            <ul>
                <li>Membaca histori penjualan.</li>
                <li>Membuat rekap penjualan harian.</li>
                <li>Menghitung Lag-1, Lag-2, dan Lag-3.</li>
                <li>Menghitung Rolling Mean, Rolling Standard Deviation, Rolling Maximum, dan Rolling Minimum.</li>
                <li>Mengirim fitur ke model Random Forest.</li>
                <li>Menampilkan hasil prediksi kebutuhan stok.</li>
            </ul>

        </div>

        </form>

    </div>

    {{-- HASIL --}}
    <div class="card hasil-card">

        <div class="hasil-box">

            <h3>📊 Hasil Prediksi</h3>

            @if(isset($hasil))
            
                <h2 style="margin-top:10px;">
                    📦 {{ $hasil['produk'] }}
                </h2>

                <div class="prediksi-value">
                    {{ round($hasil['prediksi']) }}
                </div>

                <p style="opacity:0.7;">
                    Prediksi kebutuhan stok
                </p>

                @php
                    $safe = round($hasil['prediksi'] * 0.2);
                    $total = round($hasil['prediksi'] + $safe);
                @endphp

                <div class="summary-box">

                    <div>
                        Prediksi:
                        <b>
                            {{ round($hasil['prediksi']) }}
                        </b>
                    </div>

                    <div>
                        Safety Stock:
                        <b>
                            {{ $safe }}
                        </b>
                    </div>

                    <div>
                        Total Rekomendasi:
                        <b>
                            {{ $total }}
                        </b>
                    </div>

                </div>

                {{-- STATUS --}}
                <div style="margin-top:15px;">

                    <span class="badge
                    {{ strtolower($hasil['kategori']) }}">

                        {{ $hasil['kategori'] }}

                    </span>

                </div>

                {{-- REKOMENDASI --}}
                <div class="rekom-box">

                    📌 {{ $hasil['rekomendasi'] }}

                </div>

                {{-- FEATURE ENGINEERING --}}
                @if(isset($hasil['fitur']))

                <div class="feature-box">

                    <b>
                        📈 Feature Time-Series
                    </b>

                    <br><br>

                    <div class="feature-grid">

                        <div class="feature-item">
                            Lag 1:
                            <b>
                                {{ $hasil['fitur']['lag1'] }}
                            </b>
                        </div>

                        <div class="feature-item">
                            Lag 2:
                            <b>
                                {{ $hasil['fitur']['lag2'] }}
                            </b>
                        </div>

                        <div class="feature-item">
                            Lag 3:
                            <b>
                                {{ $hasil['fitur']['lag3'] }}
                            </b>
                        </div>

                        <div class="feature-item">
                            Trend:
                            <b>
                                {{ $hasil['fitur']['diff_1'] ?? 0 }}
                            </b>
                        </div>

                        <div class="feature-item">
                            Weekend:
                            <b>
                                {{ $hasil['fitur']['weekend'] ?? 0 }}
                            </b>
                        </div>

                        <div class="feature-item">
                            Holiday:
                            <b>
                                {{ $hasil['fitur']['holiday'] ?? 0 }}
                            </b>
                        </div>

                    </div>

                </div>

                @endif

                {{-- CHART --}}
                <div class="chart-wrapper">
                    <canvas id="chart"></canvas>
                </div>

            @else

                <div style="
                opacity:0.5;
                padding:40px 0;
                ">
                    Belum ada prediksi
                </div>

            @endif

        </div>

    </div>

</div>

{{-- =======================
    TREND
======================= --}}
<div class="card" style="margin-top:20px;">

    <h3>📈 Tren Prediksi</h3>

    @if(!empty($trend_labels))

        <canvas id="trendChart"></canvas>

    @else

        <p style="opacity:0.6;">
            Belum ada data trend
        </p>

    @endif

</div>

{{-- =======================
    RIWAYAT
======================= --}}
<div class="card" style="margin-top:20px;">

    <h3>📜 Riwayat Prediksi</h3>

    @if(!empty($history))

    <div style="overflow-x:auto;">

    <table class="table">

        <thead>

            <tr>
                <th>Tanggal</th>
                <th>Produk</th>
                <th>Prediksi</th>
            </tr>

        </thead>

        <tbody>

            @foreach(array_reverse($history) as $h)

            <tr>

                <td>{{ $h['tanggal'] }}</td>

                <td class="left">
                    {{ $h['produk'] }}
                </td>

                <td>
                    {{ round($h['prediksi']) }}
                </td>

            </tr>

            @endforeach

        </tbody>

    </table>

    </div>

    @else

    <p style="opacity:0.6;">
        Belum ada data prediksi
    </p>

    @endif

</div>

{{-- =======================
    SCRIPT
======================= --}}
@if(isset($hasil))
<script>

new Chart(document.getElementById('chart'), {

    type: 'bar',

    data: {

        labels: [

            'Prediksi',
            'Safety Stock',
            'Total'

        ],

        datasets: [{

            label: 'Qty',

            data: [

                {{ round($hasil['prediksi']) }},

                {{ $safe }},

                {{ $total }}

            ],

            backgroundColor: '#38bdf8',

            borderRadius: 6

        }]

    },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            plugins: {

                legend: {
                    display: false
                }

            },

            scales: {

                y: {
                    beginAtZero: true
                }

            }

        }

    });

</script>
@endif

@if(!empty($trend_labels))
<script>

new Chart(document.getElementById('trendChart'), {

    type: 'line',

    data: {

        labels: {!! json_encode($trend_labels) !!},

        datasets: [{

            label: 'Trend Prediksi',

            data: {!! json_encode($trend_data) !!},

            borderColor: '#38bdf8',

            pointBackgroundColor: '#38bdf8',

            pointRadius: 4,

            pointHoverRadius: 6,

            tension: 0.3,

            fill: false

        }]

    },

    options: {

        responsive:true,

        scales:{
            y:{
                beginAtZero:true
            }
        }

    }

});

</script>
@endif

@endsection