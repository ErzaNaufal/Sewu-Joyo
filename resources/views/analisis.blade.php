@extends('layout')

@section('title','Analisis Stok')

@section('content')

<h1 style="margin-bottom:18px;font-size:34px;">📈 Analisis Stok & Rekomendasi</h1>

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
        🕒 Update Terakhir: {{ now()->format('d-m-Y H:i') }}
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

        <h3>📖 Kriteria Analisis</h3>

        <ul class="list">
            <li>🔴 <b>Overstock</b><br>Stok lebih tinggi dibanding hasil prediksi.</li>
            <li>🟢 <b>Aman</b><br>Stok sesuai dengan hasil prediksi.</li>
            <li>🟡 <b>Understock</b><br>Stok lebih rendah dibanding hasil prediksi.</li>
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

        <li>
        🔥 Prediksi Tertinggi
        <br>
        <b>{{ $max['produk'] ?? '-' }}</b>
        ({{ $max ? round($max['prediksi']) : 0 }} pcs)
        </li>

        <li>
        📉 Prediksi Terendah
        <br>
        <b>{{ $min['produk'] ?? '-' }}</b>
        ({{ $min ? round($min['prediksi']) : 0 }} pcs)
        </li>

        </ul>
    </div>

</div>

<!-- =======================
    CHART
======================= -->
@if($total > 0)
<div class="chart-wrapper">

<div class="card chart-card bar-card">
    <h3>📊 Stok vs Prediksi</h3>

    <canvas id="barChart"></canvas>
</div>

    <div class="card center chart-card pie-card">

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

<thead>
<tr>
    <th>No</th>
    <th>Produk</th>
    <th>Stok</th>
    <th>Prediksi</th>
    <th>Selisih</th>
    <th>Status</th>
    <th>Keterangan</th>
    <th>Rekomendasi</th>
</tr>
</thead>

<tbody>

@foreach($data as $i => $d)

<tr>
<td>{{ $i+1 }}</td>
<td class="left">{{ $d['produk'] }}</td>
<td>{{ $d['stok'] }}</td>
<td>{{ round($d['prediksi']) }}</td>
<td>
    @php
        $selisih = $d['stok'] - round($d['prediksi']);
    @endphp

    @if($selisih > 0)
        +{{ $selisih }}
    @elseif($selisih < 0)
        {{ $selisih }}
    @else
        0
    @endif
</td>


<td>
    @if($d['status']=='Overstock')
        <span class="badge badge-red">🔴 Overstock</span>
    @elseif($d['status']=='Understock')
        <span class="badge badge-yellow">🟡 Understock</span>
    @else
        <span class="badge badge-green">🟢 Aman</span>
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

</tbody>

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
    maintainAspectRatio:false,
    plugins:{
        legend:{
            position:'top'
        }
    },
    scales:{
        y:{
            beginAtZero:true
        },
        x:{
            ticks:{
                maxRotation:45,
                minRotation:45
            }
        }
    }
}
});

// PIE CHART
new Chart(document.getElementById('pieChart'), {
    type: 'doughnut',
    data: {
        labels: ['Overstock','Understock','Aman'],
        datasets: [{
            data: [{{ $over }}, {{ $under }}, {{ $aman }}],
            backgroundColor: ['#ef4444','#facc15','#22c55e']
        }]
    },
    options:{
        responsive:true,
        maintainAspectRatio:false,
        cutout:'70%',
        plugins:{
            legend:{
                position:'top'
            }
        }
    }
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

.actions{
display:flex;
align-items:center;
gap:10px;
flex-wrap:wrap;
justify-content:flex-end;
}

#searchTable{
width:250px;
padding:11px 15px;
border-radius:10px;
border:none;
outline:none;
background:#1e293b;
color:#fff;
}

.summary{
display:grid;
grid-template-columns:repeat(4,1fr);
gap:20px;
margin-bottom:25px;
}

.insight-grid{
display:grid;
grid-template-columns:repeat(3,1fr);
gap:20px;
margin-bottom:25px;
align-items:stretch;
}

.insight-grid .card{
display:flex;
flex-direction:column;
min-height:250px;
}

.chart-wrapper{
display:flex;
flex-direction:column;
gap:20px;
margin-bottom:25px;
}

@media (max-width:992px){

.summary{
grid-template-columns:repeat(2,1fr);
}

.insight-grid{
grid-template-columns:1fr;
}

.chart{
grid-template-columns:1fr;
}

}

@media (max-width:768px){

.summary{
grid-template-columns:1fr;
}

.actions{
width:100%;
}

#searchTable{
width:100%;
}

}


.stat{
padding:22px;
display:flex;
flex-direction:column;
justify-content:center;
align-items:center;
min-height:110px;
}

.stat h2{

font-size:32px;
margin-top:10px;

}
.red { color:#ef4444; }
.yellow { color:#facc15; }
.green { color:#22c55e; }

.table-box{
overflow-x:auto;
}

.card h3{
    margin-bottom:18px;
    font-size:22px;
    font-weight:600;
}


.table{
width:100%;
border-collapse:collapse;
text-align:center;
min-width:950px;
}

.table td,
.table th{

padding:14px 16px;
border-bottom:1px solid rgba(255,255,255,.08);

}

.table tbody tr:hover{
background:rgba(255,255,255,.04);
transition:.2s;
}

.left { text-align:left; }

.btn-export{

display:inline-flex;
align-items:center;
gap:6px;

background:linear-gradient(135deg,#38bdf8,#6366f1);
padding:10px 14px;
border-radius:8px;
color:white;
text-decoration:none;

}

.list {
list-style:none;
padding:0;
line-height:1.8;
}

.badge{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:5px;
    padding:8px 14px;
    border-radius:999px;
    font-size:12px;
    font-weight:700;
    white-space:nowrap;
}

.badge-red{
background:#ef4444;
color:#fff;
}

.badge-yellow{
background:#facc15;
color:#000;
}

.badge-green{
background:#22c55e;
color:#fff;
}

.chart-card{
width:100%;
padding:20px;
}

.bar-card canvas{
height:420px !important;
}

.pie-card canvas{
width:300px !important;
height:300px !important;
margin:auto;
display:block;
}

</style>

@endsection