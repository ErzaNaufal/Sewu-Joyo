@extends('layout')

@section('title','Analisis Stok')

@section('content')

<h1 style="margin-bottom:25px;">📈 Analisis Stok & Rekomendasi</h1>

@php
$total = count($data ?? []);

$over = collect($data ?? [])->where('status','Overstock')->count();
$under = collect($data ?? [])->where('status','Understock')->count();
$aman = collect($data ?? [])->where('status','Aman')->count();

$max = $total ? collect($data)->sortByDesc('prediksi')->first() : null;
$min = $total ? collect($data)->sortBy('prediksi')->first() : null;

$prioritas = collect($data ?? [])->where('status','Understock')->take(5);
@endphp

<!-- =======================
    UPDATE + ACTION
======================= -->
<div class="top-bar">

    <div class="update">
        🕒 Update: {{ now()->format('d-m-Y H:i') }}
    </div>

    <div class="actions">
        <a href="/laporan/export/pdf" class="btn-export">⬇️ PDF</a>
        <a href="/laporan/export/excel" class="btn-export">⬇️ Excel</a>
        <input type="text" id="searchTable" placeholder="🔍 Cari produk...">
    </div>

</div>

<!-- =======================
    SUMMARY
======================= -->
<div class="grid summary">

    <div class="card stat">
        <p>Total Produk</p>
        <h2>{{ $total }}</h2>
    </div>

    <div class="card stat red">
        <p>Overstock</p>
        <h2>{{ $over }}</h2>
    </div>

    <div class="card stat yellow">
        <p>Understock</p>
        <h2>{{ $under }}</h2>
    </div>

    <div class="card stat green">
        <p>Aman</p>
        <h2>{{ $aman }}</h2>
    </div>

</div>

<!-- =======================
    INSIGHT
======================= -->
<div class="grid insight-grid">

    <div class="card">
        <h3>⚡ Rekomendasi</h3>
        <ul class="list">
            <li>🔴 Kurangi pembelian: <b>{{ $over }}</b></li>
            <li>🟡 Tambah stok: <b>{{ $under }}</b></li>
            <li>🟢 Stabil: <b>{{ $aman }}</b></li>
        </ul>
    </div>

    <div class="card">
        <h3>🚨 Prioritas</h3>
        <ul class="list">
            @forelse($prioritas as $p)
            <li>⚠️ {{ $p['produk'] }} ({{ $p['stok'] }} → {{ round($p['prediksi']) }})</li>
            @empty
            <li>Tidak ada prioritas</li>
            @endforelse
        </ul>
    </div>

    <div class="card">
        <h3>📌 Insight</h3>
        <ul class="list">
            <li>🔥 {{ $max['produk'] ?? '-' }} ({{ $max ? round($max['prediksi']) : 0 }})</li>
            <li>📉 {{ $min['produk'] ?? '-' }} ({{ $min ? round($min['prediksi']) : 0 }})</li>
        </ul>
    </div>

</div>

<!-- =======================
    CHART
======================= -->
@if($total > 0)
<div class="grid chart">

    <div class="card">
        <h3>📊 Stok vs Prediksi</h3>
        <canvas id="barChart"></canvas>
    </div>

    <div class="card center">
        <h3>📊 Distribusi</h3>
        <canvas id="pieChart"></canvas>
    </div>

</div>
@endif

<!-- =======================
    TABLE
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
<th>Rekomendasi</th>
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
@if($d['status']=='Overstock') Stok berlebih
@elseif($d['status']=='Understock') Perlu tambah
@else Stabil
@endif
</td>

<td>{{ $d['rekomendasi'] }}</td>

</tr>
@endforeach

</table>

@else

<div style="text-align:center; padding:40px; opacity:0.6;">
    <h3>📭 Data belum tersedia</h3>
    <p>Silakan jalankan analisis terlebih dahulu</p>
    <a href="/analisis">➡️ Jalankan Analisis</a>
</div>

@endif

</div>

<!-- =======================
    SCRIPT
======================= -->
@if($total > 0)
<script>

// SEARCH (AMAN)
const searchInput = document.getElementById('searchTable');
if(searchInput){
searchInput.addEventListener('keyup', function() {
    let val = this.value.toLowerCase();
    document.querySelectorAll('.table tr').forEach((row,i)=>{
        if(i===0) return;
        row.style.display = row.innerText.toLowerCase().includes(val) ? '' : 'none';
    });
});
}

// BAR CHART
new Chart(document.getElementById('barChart'), {
type: 'bar',
data: {
labels: {!! json_encode(array_column($data, 'produk')) !!},
datasets: [
{
label: 'Stok',
data: {!! json_encode(array_column($data, 'stok')) !!},
backgroundColor: '#38bdf8'
},
{
label: 'Prediksi',
data: {!! json_encode(array_column($data, 'prediksi')) !!},
backgroundColor: '#6366f1'
}
]
},
options:{
responsive:true,
scales:{ y:{ beginAtZero:true } }
}
});

// PIE
new Chart(document.getElementById('pieChart'), {
type: 'doughnut',
data: {
labels: ['Overstock','Understock','Aman'],
datasets: [{
data: [{{ $over }}, {{ $under }}, {{ $aman }}],
backgroundColor: ['#ef4444','#facc15','#22c55e']
}]
},
options:{ cutout:'65%' }
});

</script>
@endif

<style>

/* TETAP SEMUA STYLE ANDA */
.top-bar {
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:20px;
flex-wrap:wrap;
gap:10px;
}

.actions {
display:flex;
gap:10px;
align-items:center;
}

#searchTable {
padding:10px;
border-radius:8px;
border:none;
background:#1e293b;
color:white;
}

.summary {
grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
margin-bottom:20px;
}

.insight-grid {
grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
gap:20px;
margin-bottom:20px;
}

.chart {
grid-template-columns:repeat(auto-fit,minmax(300px,1fr));
gap:20px;
margin-bottom:20px;
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

.btn-export {
background: linear-gradient(135deg,#38bdf8,#6366f1);
padding:10px;
border-radius:8px;
color:white;
text-decoration:none;
}

.list {
list-style:none;
padding:0;
line-height:1.8;
}

</style>

@endsection