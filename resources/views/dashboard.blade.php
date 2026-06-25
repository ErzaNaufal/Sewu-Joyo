@extends('layout')

@section('title','Dashboard')

@section('content')

<h1 style="margin-bottom:25px;">📊 Dashboard Sistem</h1>

{{-- ALERT ERROR --}}
@if(session('error'))
<div id="error-alert"
     style="
        background:#ef4444;
        color:white;
        padding:10px;
        border-radius:8px;
        margin-bottom:15px;
     ">
    {{ session('error') }}
</div>
@endif

@php
$total_produk = count($produk ?? []);
$total_transaksi = count($penjualan ?? []);
$total_prediksi = count($history ?? []);
$last_transaksi = collect($penjualan ?? [])->last();
@endphp

<!-- =======================
    SUMMARY
======================= -->
<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px; margin-bottom:25px;">

    <div class="card">
        <p>Total Produk</p>
        <h2>{{ $total_produk }}</h2>
    </div>

    <div class="card">
        <p>Total Transaksi</p>
        <h2>{{ $total_transaksi }}</h2>
    </div>

    <div class="card">
        <p>Total Prediksi</p>
        <h2>{{ $total_prediksi }}</h2>
    </div>

    <div class="card">
        <p>Status Sistem</p>
        <h2 style="color:lime;">Aktif</h2>
    </div>

</div>

<!-- =======================
    INFO CEPAT
======================= -->
<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:20px; margin-bottom:25px;">

    <div class="card">
        <h3>📦 Produk Aktif</h3>
        <p>Total {{ $total_produk }} produk tersedia di sistem</p>
    </div>

    <div class="card">
        <h3>🛒 Aktivitas Terakhir</h3>
        <p>
            {{ $last_transaksi['produk'] ?? 'Belum ada transaksi' }}
        </p>
    </div>

    <div class="card">
        <h3>🤖 Model Prediksi</h3>
        <p>Random Forest aktif dan siap digunakan</p>
    </div>

</div>

<!-- =======================
    TREND CHART
======================= -->
<div class="card" style="margin-bottom:20px;">
    <h3>📈 Tren Prediksi</h3>

    @if(!empty($trend_labels))
        <canvas id="trendChart"></canvas>
    @else
        <p style="opacity:0.6;">Belum ada data prediksi</p>
    @endif
</div>

<!-- =======================
    RIWAYAT
======================= -->
<div class="grid" style="grid-template-columns:1fr 1fr; gap:20px;">

    <!-- TRANSAKSI -->
    <div class="card">
        <h3>📜 Transaksi Terakhir</h3>

        @if(!empty($penjualan))
        <table class="table">
            <tr>
                <th>Tanggal</th>
                <th>Produk</th>
                <th>Jumlah</th>
            </tr>

            @foreach(array_slice($penjualan, -5) as $p)
            <tr>
                <td>{{ $p['tanggal'] }}</td>
                <td class="left">{{ $p['produk'] }}</td>
                <td>{{ $p['jumlah'] }}</td>
            </tr>
            @endforeach
        </table>
        @else
            <p style="opacity:0.6;">Belum ada transaksi</p>
        @endif
    </div>

    <!-- PREDIKSI -->
    <div class="card">
        <h3>📜 Prediksi Terakhir</h3>

        @if(!empty($history))
        <table class="table">
            <tr>
                <th>Tanggal</th>
                <th>Produk</th>
                <th>Prediksi</th>
            </tr>

            @foreach(array_slice($history, -5) as $h)
            <tr>
                <td>{{ $h['tanggal'] }}</td>
                <td class="left">{{ $h['produk'] }}</td>
                <td>{{ round($h['prediksi']) }}</td>
            </tr>
            @endforeach
        </table>
        @else
            <p style="opacity:0.6;">Belum ada prediksi</p>
        @endif
    </div>

</div>

<!-- =======================
    SCRIPT
======================= -->
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
</script>
@endif

<style>

.table {
    width:100%;
    border-collapse: collapse;
    text-align:center;
}

.table th {
    border-bottom: 2px solid rgba(255,255,255,0.2);
    padding:10px;
}

.table td {
    padding:8px;
    border-bottom:1px solid rgba(255,255,255,0.05);
}

.left {
    text-align:left;
}

canvas {
    max-height:300px;
}

</style>

<script>

setTimeout(function(){

    let error = document.getElementById('error-alert');

    if(error){

        error.style.transition = "0.5s";
        error.style.opacity = "0";

        setTimeout(function(){
            error.remove();
        },500);

    }

},3000);

</script>


@endsection