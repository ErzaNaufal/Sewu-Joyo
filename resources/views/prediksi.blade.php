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
    FORM INPUT
========================= */

.form-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:20px;
    align-items:start;
}

.form-group{
    display:flex;
    flex-direction:column;
}

.form-group label{
    display:block;
    margin-bottom:10px;
    font-size:16px;
    font-weight:600;
    color:#ffffff;
    line-height:1.4;
}

.form-group input,
.form-group select{
    width:100%;
    height:48px;
    padding:0 14px;
    border-radius:10px;
    border:1px solid rgba(255,255,255,.15);
    background:#0b1120;
    color:#ffffff;
    font-size:15px;
    outline:none;
    box-sizing:border-box;
}

.form-group input:focus,
.form-group select:focus{
    border-color:#38bdf8;
}

@media(max-width:768px){

    .form-grid{
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

/* =========================
    FILTER GRAFIK TREND
========================= */
.trend-filter{
    margin:15px 0 20px 0;
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
}

.trend-filter label{
    font-weight:600;
    color:#ffffff;
}

.trend-filter select{
    padding:10px 14px;
    border-radius:10px;
    background:#0b1120;
    color:#ffffff;
    border:1px solid rgba(255,255,255,.15);
    font-size:14px;
    outline:none;
}

.trend-filter select:focus{
    border-color:#38bdf8;
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

                <label for="produk">
                    Produk
                </label>

                <select
                    id="produk"
                    name="produk"
                    required>

                    <option value="">-- Pilih Produk --</option>

                    @foreach($produk as $p)
                        <option
                            value="{{ $p }}"
                            {{ old('produk') == $p ? 'selected' : '' }}>
                            {{ ucfirst($p) }}
                        </option>
                    @endforeach

                </select>

            </div>

            {{-- TANGGAL --}}
            <div class="form-group">

                <label for="tanggal">
                    Tanggal Prediksi
                </label>

                <input
                    id="tanggal"
                    type="date"
                    name="tanggal"
                    value="{{ old('tanggal') }}"
                    required>

            </div>

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
                    {{ $hasil['prediksi_model'] }} {{ $hasil['satuan'] }}
                </div>

                <p style="opacity:.7;">
                    Rekomendasi Stok
                </p>

                @php
                    $safe = round($hasil['prediksi_model'] * 0.2);
                    $total = $hasil['rekomendasi_stok'];
                @endphp

                <div class="summary-box">

                    <div>
                        Prediksi Model
                        <b>{{ $hasil['prediksi_model'] }} {{ $hasil['satuan'] }}</b>
                    </div>

                    <div>
                        Hari Libur
                        <b>{{ $hasil['holiday_name'] }}</b>
                    </div>

                    <div>
                        Posisi

                        <b>
                            @if(!is_null($hasil['days_before_holiday']))
                                H-{{ $hasil['days_before_holiday'] }}
                            @else
                                -
                            @endif
                        </b>

                    </div>

                    <div>
                        Holiday Boost
                        <b>+{{ $hasil['holiday_boost'] }}%</b>
                    </div>

                    <div>
                        Penyesuaian
                        <b>+{{ $hasil['penyesuaian'] }} {{ $hasil['satuan'] }}</b>
                    </div>

                    <div>
                        Safety Stock
                        <b>{{ $safe }} {{ $hasil['satuan'] }}</b>
                    </div>

                    <hr style="margin:12px 0;opacity:.2;">

                    <div style="font-size:18px;">

                        <b>Rekomendasi Stok</b>

                        <div style="margin-top:8px;font-size:28px;color:#38bdf8;">

                            {{ $hasil['rekomendasi_stok'] }} {{ $hasil['satuan'] }}

                        </div>

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

                @if(isset($hasil['fitur']))

                <div class="card" style="margin-top:20px; text-align:left;">

                    <h4>🧮 Detail Perhitungan Prediksi</h4>
                    <hr style="opacity:.2; margin:12px 0;">

                    <table class="table">

                        <tr>
                            <td>Lag-1 (Penjualan H-1)</td>
                            <td><b>{{ $hasil['fitur']['lag1'] ?? '-' }}</b></td>
                        </tr>

                        <tr>
                            <td>Lag-2 (Penjualan H-2)</td>
                            <td><b>{{ $hasil['fitur']['lag2'] ?? '-' }}</b></td>
                        </tr>

                        <tr>
                            <td>Lag-3 (Penjualan H-3)</td>
                            <td><b>{{ $hasil['fitur']['lag3'] ?? '-' }}</b></td>
                        </tr>

                        <tr>
                            <td>Trend Penjualan</td>
                            <td><b>{{ $hasil['fitur']['diff_1'] ?? '-' }}</b></td>
                        </tr>

                        <tr>
                            <td>Weekend</td>
                            <td>
                                <b>{{ ($hasil['fitur']['weekend'] ?? 0) ? 'Ya' : 'Tidak' }}</b>
                            </td>
                        </tr>

                        <tr>
                            <td>Hari Libur</td>
                            <td>
                                <b>{{ ($hasil['fitur']['holiday'] ?? 0) ? 'Ya' : 'Tidak' }}</b>
                            </td>
                        </tr>

                    </table>

                    <hr style="margin:18px 0; opacity:.2;">

                    <h5>📌 Proses Perhitungan</h5>

                    <table class="table">

                        <tr>
                            <td style="width:40%;"><b>Langkah 1</b></td>
                            <td>
                                Sistem membaca nilai fitur
                                <b>Lag-1</b>, <b>Lag-2</b>, <b>Lag-3</b>,
                                <b>Trend</b>, <b>Weekend</b>, dan
                                <b>Hari Libur</b>.
                            </td>
                        </tr>

                        <tr>
                            <td><b>Langkah 2</b></td>
                            <td>
                                Seluruh fitur digunakan sebagai input ke model
                                <b>Random Forest Regressor</b>.
                            </td>
                        </tr>

                        <tr>
                            <td><b>Langkah 3</b></td>
                            <td>
                                Model melakukan prediksi menggunakan banyak
                                <b>Decision Tree</b>.
                            </td>
                        </tr>

                        <tr>
                            <td><b>Langkah 4</b></td>
                            <td>
                                Seluruh hasil prediksi dari setiap Decision Tree
                                digabungkan menggunakan metode
                                <b>Average (Rata-rata)</b>.
                            </td>
                        </tr>

                        <tr style="border-top:2px solid rgba(255,255,255,.2);">
                            <td><b>Output Prediksi</b></td>
                            <td>
                                <span style="font-size:18px;color:#38bdf8;font-weight:bold;">
                                    {{ $hasil['prediksi_model'] }} {{ $hasil['satuan'] }}
                                </span>
                            </td>
                        </tr>


                        <tr>
                            <td>Hari Libur</td>
                            <td><b>{{ $hasil['holiday_name'] }}</b></td>
                        </tr>

                        <tr>
                            <td>Posisi</td>
                            <td>
                                @if(!is_null($hasil['days_before_holiday']))
                                    H-{{ $hasil['days_before_holiday'] }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>

                        <tr>
                            <td>Holiday Boost</td>
                            <td><b>+{{ $hasil['holiday_boost'] }}%</b></td>
                        </tr>

                        <tr>
                            <td>Penyesuaian</td>
                            <td><b>+{{ $hasil['penyesuaian'] }} {{ $hasil['satuan'] }}</b></td>
                        </tr>

                        <tr style="border-top:2px solid rgba(255,255,255,.2);">
                            <td><b>Rekomendasi Stok</b></td>
                            <td>
                                <span style="font-size:18px;color:#38bdf8;font-weight:bold;">
                                    {{ $hasil['rekomendasi_stok'] }} {{ $hasil['satuan'] }}
                                </span>
                            </td>
                        </tr>
                        
                    </table>

                    <div style="margin-top:15px;padding:12px;border-radius:8px;background:rgba(56,189,248,.08);line-height:1.8;font-size:14px;">
                        <b>Keterangan:</b><br>
                        Random Forest tidak menghasilkan satu persamaan matematis seperti regresi linear.
                        Prediksi diperoleh dari hasil penggabungan (<b>Average</b>) prediksi yang dihasilkan oleh banyak
                        <b>Decision Tree</b> berdasarkan nilai fitur yang dimasukkan ke dalam model.
                    </div>

                </div>

                @endif

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
    TREND (DENGAN FILTER PRODUK)
======================= --}}
<div class="card" style="margin-top:20px;">

    <h3>📈 Grafik Prediksi Stok</h3>

    @if(!empty($trend_labels))

        {{-- COMBOBOX FILTER PRODUK --}}
        <div class="trend-filter">

            <label for="filterTrendProduk">
                Pilih Produk:
            </label>

            <select id="filterTrendProduk">

                <option value="__all__">Semua Produk</option>

                @foreach($produk as $p)
                    <option value="{{ $p }}">{{ ucfirst($p) }}</option>
                @endforeach

            </select>

        </div>

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
                <th>Tanggal Prediksi</th>
                <th>Produk</th>
                <th>Prediksi</th>
            </tr>

        </thead>

        <tbody>

            @foreach($history as $h)

            <tr>

                <td>{{ $h['tanggal'] }}</td>

                <td class="left">
                    {{ ucfirst($h['produk']) }}
                </td>

                <td>
                    {{ round($h['prediksi']) }} {{ $h['satuan'] }}
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

            'Prediksi Model',
            'Safety Stock',
            'Rekomendasi Stok'

        ],

        datasets: [{

            label: 'Qty',

            data: [

                {{ $hasil['prediksi_model'] }},

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

// ==============================
// DATA TREND: GABUNGAN & PER PRODUK
// ==============================
const trendByProduk  = {!! json_encode($trendByProduk ?? []) !!};
const trendLabelsAll = {!! json_encode($trend_labels) !!};
const trendDataAll   = {!! json_encode($trend_data) !!};

let trendChart;

function renderTrendChart(produkKey) {

    let labels, data, labelText;

    if (produkKey === '__all__') {

        labels = trendLabelsAll;
        data = trendDataAll;
        labelText = 'Trend Prediksi (Semua Produk)';

    } else {

        const d = trendByProduk[produkKey] || { labels: [], data: [] };

        labels = d.labels;
        data = d.data;
        labelText = 'Trend Prediksi - '
            + produkKey.charAt(0).toUpperCase()
            + produkKey.slice(1);

    }

    if (labels.length === 0) {
        labels = ['-'];
        data = [0];
    }

    if (trendChart) {
        trendChart.destroy();
    }

    trendChart = new Chart(document.getElementById('trendChart'), {

        type: 'line',

        data: {

            labels: labels,

            datasets: [{

                label: labelText,

                data: data,

                borderColor: '#38bdf8',

                pointBackgroundColor: '#38bdf8',

                pointRadius: 4,

                pointHoverRadius: 6,

                tension: 0.3,

                fill: false

            }]

        },

        options: {

            responsive: true,

            scales: {
                y: {
                    beginAtZero: true
                }
            }

        }

    });

}

// Render pertama kali dengan "Semua Produk"
renderTrendChart('__all__');

// Update grafik saat combobox diganti
document.getElementById('filterTrendProduk')
    .addEventListener('change', function () {
        renderTrendChart(this.value);
    });

</script>
@endif

@endsection