@extends('layout')

@section('title','Penjualan')

@section('content')

<h1 style="margin-bottom:25px;">🛒 Sistem Penjualan</h1>

{{-- ALERT --}}
@if(session('success'))
<div style="background:#22c55e;padding:10px;border-radius:8px;margin-bottom:15px;">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div style="background:#ef4444;padding:10px;border-radius:8px;margin-bottom:15px;">
    {{ session('error') }}
</div>
@endif

@php
$total = count($penjualan ?? []);
$total_qty = collect($penjualan ?? [])->sum('jumlah');
$last = collect($penjualan ?? [])->last();

// 🔥 DATA UNTUK CHART
$grouped = collect($penjualan ?? [])
    ->groupBy('produk')
    ->map(fn($items) => $items->sum('jumlah'));

// 🔥 INSIGHT
$top_produk = $grouped->sortDesc()->keys()->first();
@endphp

<!-- =======================
    PENJELASAN SISTEM (INI KUNCI TA)
======================= -->
<div class="card" style="margin-bottom:20px;">
    <h3>📌 Alur Sistem</h3>
    <p style="line-height:1.7;">
        Data transaksi penjualan yang diinput akan disimpan sebagai data historis.
        Data ini digunakan sebagai <b>input model prediksi</b> menggunakan metode 
        Random Forest dengan pendekatan time-series (lag 1, lag 2, lag 3).
    </p>
</div>

<!-- =======================
    SUMMARY
======================= -->
<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); margin-bottom:20px;">

    <div class="card">
        <p>Total Transaksi</p>
        <h2>{{ $total }}</h2>
    </div>

    <div class="card">
        <p>Total Penjualan</p>
        <h2>{{ $total_qty }}</h2>
    </div>

    <div class="card">
        <p>Produk Terlaris</p>
        <h2>{{ $top_produk ?? '-' }}</h2>
    </div>

</div>

<!-- FORM INPUT -->
<div class="card" style="margin-bottom:20px;">
    <h3>📥 Input Transaksi</h3>

    {{-- 🔥 FIX DI SINI --}}
    <form method="POST" action="/penjualan/simpan">
        @csrf

        <div class="form-grid">

            <div class="form-group">
                <label>Produk</label>
                <select name="produk" required>
                    <option value="">-- Pilih Produk --</option>
                    @foreach($produk as $p)
                        <option value="{{ $p }}">{{ $p }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Jumlah Terjual</label>
                <input type="number" name="jumlah" min="1" required>
            </div>

        </div>

        <br>
        <button type="submit">💾 Simpan Transaksi</button>
    </form>
</div>
<!-- =======================
    CHART
======================= -->
<div class="card" style="margin-bottom:20px;">
    <h3>📊 Grafik Penjualan per Produk</h3>

    @if($grouped->count() > 0)
        <canvas id="chartPenjualan"></canvas>
    @else
        <p style="opacity:0.6;">Belum ada data penjualan</p>
    @endif
</div>

<!-- =======================
    DATASET (INI PENTING BANGET UNTUK TA)
======================= -->
<div class="card">
    <h3>📜 Dataset Penjualan (Data Historis)</h3>

    @if($total > 0)

    <table class="table">
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Produk</th>
            <th>Jumlah</th>
        </tr>

        @foreach($penjualan as $i => $p)
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $p['tanggal'] }}</td>
            <td class="left">{{ $p['produk'] }}</td>
            <td>{{ $p['jumlah'] }}</td>
        </tr>
        @endforeach

    </table>

    <p style="margin-top:10px; font-size:13px; opacity:0.7;">
        Data di atas digunakan sebagai input untuk proses prediksi kebutuhan stok.
    </p>

    @else
        <p style="opacity:0.6;">Belum ada data penjualan</p>
    @endif

</div>

<!-- =======================
    SCRIPT
======================= -->
@if($grouped->count() > 0)
<script>
new Chart(document.getElementById('chartPenjualan'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($grouped->keys()) !!},
        datasets: [{
            label: 'Total Penjualan',
            data: {!! json_encode($grouped->values()) !!},
            backgroundColor: '#38bdf8'
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
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
</style>

@endsection