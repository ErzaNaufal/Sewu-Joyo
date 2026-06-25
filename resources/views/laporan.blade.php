@extends('layout')

@section('title','Laporan')

@section('content')

<h1 style="margin-bottom:25px;">📄 Laporan Stok Barang</h1>

@php
$data = $data ?? [];

$total = count($data);

$over = collect($data)->where('status','Overstock')->count();
$under = collect($data)->where('status','Understock')->count();
$aman = collect($data)->where('status','Aman')->count();

$max = $total ? collect($data)->sortByDesc('prediksi')->first() : null;
$min = $total ? collect($data)->sortBy('prediksi')->first() : null;

$over_p = $total ? round(($over/$total)*100,1) : 0;
$under_p = $total ? round(($under/$total)*100,1) : 0;
$aman_p = $total ? round(($aman/$total)*100,1) : 0;
@endphp

<!-- =======================
    HEADER
======================= -->
<div class="card mb">
    <h3>Toko Sewu Joyo</h3>
    <p class="muted">
        Laporan Analisis Stok Barang<br>
        Tanggal: {{ date('d-m-Y') }}
    </p>

    <hr class="divider">

    <p class="desc">
        Laporan ini menyajikan hasil analisis kondisi stok berdasarkan prediksi penjualan 
        sebagai dasar pengambilan keputusan dalam pengelolaan persediaan barang.
    </p>
</div>

<!-- =======================
    FILTER & EXPORT
======================= -->
<div class="card mb">
<form method="GET" class="filter-box">

    <input type="text" name="search"
        placeholder="🔍 Cari produk..."
        value="{{ request('search') }}">

    <select name="status">
        <option value="">Semua Status</option>
        <option value="Overstock" {{ request('status')=='Overstock'?'selected':'' }}>Overstock</option>
        <option value="Understock" {{ request('status')=='Understock'?'selected':'' }}>Understock</option>
        <option value="Aman" {{ request('status')=='Aman'?'selected':'' }}>Aman</option>
    </select>

    <button type="submit">Filter</button>

    <a href="/laporan/export/pdf" target="_blank" class="btn-export">⬇️ PDF</a>
    <a href="/laporan/export/excel" class="btn-export">⬇️ Excel</a>

</form>
</div>

<!-- =======================
    RINGKASAN
======================= -->
<div class="grid summary mb">

    <div class="card stat">
        <p>Total Produk</p>
        <h2>{{ $total }}</h2>
    </div>

    <div class="card stat red">
        <p>Overstock</p>
        <h2>{{ $over }} ({{ $over_p }}%)</h2>
    </div>

    <div class="card stat yellow">
        <p>Understock</p>
        <h2>{{ $under }} ({{ $under_p }}%)</h2>
    </div>

    <div class="card stat green">
        <p>Aman</p>
        <h2>{{ $aman }} ({{ $aman_p }}%)</h2>
    </div>

</div>

<!-- =======================
    INSIGHT
======================= -->
<div class="card mb">
    <h3>📌 Insight</h3>

    <ul class="list">
        <li>
            🔥 Permintaan tertinggi:
            <b>{{ $max['produk'] ?? '-' }}</b>
            ({{ $max ? round($max['prediksi']) : 0 }})
        </li>

        <li>
            📉 Permintaan terendah:
            <b>{{ $min['produk'] ?? '-' }}</b>
            ({{ $min ? round($min['prediksi']) : 0 }})
        </li>
    </ul>
</div>

<!-- =======================
    TABEL
======================= -->
<div class="card table-box">

@if($total > 0)

<table class="table">

<tr>
<th>No</th>
<th>Produk</th>
<th>Stok</th>
<th>Prediksi</th>
<th>Status</th>
<th>Keterangan</th>
</tr>

@foreach($data as $i => $d)
<tr>
<td>{{ $i+1 }}</td>

<td class="left">{{ $d['produk'] }}</td>

<td>{{ $d['stok'] }}</td>

<td>{{ round($d['prediksi']) }}</td>

<td class="{{ strtolower($d['status']) }}">
@if($d['status']=='Overstock')
🔴 {{ $d['status'] }}
@elseif($d['status']=='Understock')
🟡 {{ $d['status'] }}
@else
🟢 {{ $d['status'] }}
@endif
</td>

<td>
@if($d['status']=='Overstock') Stok melebihi kebutuhan
@elseif($d['status']=='Understock') Stok kurang, perlu penambahan
@else Stok dalam kondisi ideal
@endif
</td>

</tr>
@endforeach

</table>

@else

<div class="empty">
    <h3>📭 Data belum tersedia</h3>
    <p>Silakan lakukan analisis terlebih dahulu</p>
    <a href="/analisis">➡️ Ke halaman Analisis</a>
</div>

@endif

</div>

<!-- =======================
    CHART
======================= -->
@if($total > 0)
<div class="card mt">
    <h3>📊 Distribusi Status</h3>
    <canvas id="chartStatus"></canvas>
</div>
@endif

<!-- =======================
    SCRIPT
======================= -->
@if($total > 0)
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

<script>
new Chart(document.getElementById('chartStatus'), {
type: 'pie',
data: {
labels: ['Overstock','Understock','Aman'],
datasets: [{
data: [{{ $over }}, {{ $under }}, {{ $aman }}],
backgroundColor: ['#ef4444','#facc15','#22c55e']
}]
},
options: {
plugins: {
datalabels: {
color: '#fff',
formatter: (value) => ((value / {{ $total }}) * 100).toFixed(1) + '%'
}
}
},
plugins: [ChartDataLabels]
});
</script>
@endif

<style>

.mb { margin-bottom:20px; }
.mt { margin-top:20px; }

.muted { opacity:0.7; }
.desc { line-height:1.8; }
.divider { margin:15px 0; opacity:0.2; }

.filter-box {
display:flex;
gap:10px;
flex-wrap:wrap;
align-items:center;
}

.filter-box input,
.filter-box select {
padding:10px;
border-radius:8px;
border:none;
}

.summary {
grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
gap:20px;
}

.stat { text-align:center; }

.red { color:#ef4444; }
.yellow { color:#facc15; }
.green { color:#22c55e; }

.table {
width:100%;
border-collapse:collapse;
text-align:center;
}

.table td, .table th {
padding:10px;
border-bottom:1px solid rgba(255,255,255,0.05);
}

.left { text-align:left; }

.empty {
text-align:center;
padding:40px;
opacity:0.7;
}

.list {
list-style:none;
padding:0;
line-height:1.8;
}

.btn-export {
background: linear-gradient(135deg,#38bdf8,#6366f1);
padding:10px;
border-radius:8px;
color:white;
text-decoration:none;
}

</style>

@endsection