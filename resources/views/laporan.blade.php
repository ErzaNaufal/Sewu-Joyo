@extends('layout')

@section('title', 'Laporan')

@section('content')

@php
    $data = $data ?? [];

    $total = count($data);

    $over = collect($data)->where('status', 'Overstock')->count();
    $under = collect($data)->where('status', 'Understock')->count();
    $aman = collect($data)->where('status', 'Aman')->count();

    $max = $total ? collect($data)->sortByDesc('prediksi')->first() : null;
    $min = $total ? collect($data)->sortBy('prediksi')->first() : null;

    $over_p = $total ? round(($over / $total) * 100, 1) : 0;
    $under_p = $total ? round(($under / $total) * 100, 1) : 0;
    $aman_p = $total ? round(($aman / $total) * 100, 1) : 0;
@endphp

<h1 style="margin-bottom:25px;">📄 Laporan Stok Barang</h1>

<div class="card mb">

    <h3>Toko Sewu Joyo</h3>

    <p class="muted">
        Laporan Analisis Stok Barang<br>
        Tanggal: {{ now()->format('d-m-Y') }}
    </p>

    <hr class="divider">

    <p class="desc">
        Laporan ini menyajikan hasil analisis kondisi stok berdasarkan
        prediksi penjualan sebagai dasar pengambilan keputusan dalam
        pengelolaan persediaan barang.
    </p>

</div>

<div class="card mb">

    <div class="filter-box">

        <a href="{{ url('/laporan/export/pdf') }}"
           target="_blank"
           class="btn-export">
            ⬇️ Export PDF
        </a>

        <a href="{{ url('/laporan/export/excel') }}"
           class="btn-export">
            ⬇️ Export Excel
        </a>

    </div>

</div>

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

<div class="card mb">

    <h3>📌 Insight</h3>

    <ul class="list">

        <li>
            🔥 Permintaan tertinggi :
            <strong>{{ $max['produk'] ?? '-' }}</strong>
            ({{ $max ? round($max['prediksi']) : 0 }})
        </li>

        <li>
            📉 Permintaan terendah :
            <strong>{{ $min['produk'] ?? '-' }}</strong>
            ({{ $min ? round($min['prediksi']) : 0 }})
        </li>

    </ul>

</div>

<div class="card table-box">

@if($total > 0)

<table class="table">

    <thead>
    <tr>
        <th>No</th>
        <th>Produk</th>
        <th>Stok</th>
        <th>Prediksi</th>
        <th>Status</th>
        <th>Keterangan</th>
    </tr>
    </thead>

    <tbody>

    @foreach($data as $i => $d)

    <tr>

        <td>{{ $i + 1 }}</td>

        <td class="left">{{ $d['produk'] }}</td>

        <td>{{ $d['stok'] }}</td>

        <td>{{ round($d['prediksi']) }}</td>

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

            @if($d['status'] == 'Overstock')

                Stok melebihi kebutuhan

            @elseif($d['status'] == 'Understock')

                Stok kurang, perlu penambahan

            @else

                Stok dalam kondisi ideal

            @endif

        </td>

    </tr>

    @endforeach

    </tbody>

</table>

@else

<div class="empty">

    <h3>📭 Data belum tersedia</h3>

    <p>Silakan lakukan analisis terlebih dahulu.</p>

    <a href="{{ url('/analisis') }}">
        ➜ Ke Halaman Analisis
    </a>

</div>

@endif

</div>

@if($total > 0)

<div class="card mt">

    <h3>📊 Distribusi Status</h3>

    <div class="chart-container">
        <canvas id="chartStatus"></canvas>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

<script>

new Chart(document.getElementById('chartStatus'), {

    type: 'pie',

    data: {
        labels: ['Overstock', 'Understock', 'Aman'],
        datasets: [{
            data: [{{ $over }}, {{ $under }}, {{ $aman }}],
            backgroundColor: [
                '#ef4444',
                '#facc15',
                '#22c55e'
            ],
            borderColor: '#ffffff',
            borderWidth: 2
        }]
    },

    options: {
        responsive: true,
        maintainAspectRatio: false,

        plugins: {

            legend: {
                position: 'top',
                labels: {
                    padding: 20,
                    boxWidth: 40,
                    font: {
                        size: 13
                    }
                }
            },

            datalabels: {
                color: '#ffffff',

                font: {
                    weight: 'bold',
                    size: 14
                },

                formatter: (value) => {

                    // Jangan tampilkan label jika nilainya 0
                    if (value === 0) {
                        return null;
                    }

                    return ((value / {{ $total }}) * 100).toFixed(1) + '%';
                }

            }

        }

    },

    plugins: [ChartDataLabels]

});

</script>

@endif

<style>

.mb{margin-bottom:20px;}
.mt{margin-top:20px;}

.muted{opacity:.7;}
.desc{line-height:1.8;}
.divider{margin:15px 0;opacity:.2;}

.summary{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
gap:20px;
}

.stat{text-align:center;}

.red{color:#ef4444;}
.yellow{color:#facc15;}
.green{color:#22c55e;}

.table{
width:100%;
border-collapse:collapse;
text-align:center;
}

.table th,
.table td{
padding:14px;
border-bottom:1px solid rgba(255,255,255,.08);
}

.table tbody tr:hover{
background:rgba(255,255,255,.04);
transition:.2s;
}

.list li{
margin-bottom:12px;
}

.left{text-align:left;}


.list{
list-style:none;
padding:0;
line-height:1.8;
}

.empty{
text-align:center;
padding:40px;
opacity:.8;
}

.filter-box{
display:flex;
gap:12px;
flex-wrap:wrap;
}

.btn-export{
background:linear-gradient(135deg,#38bdf8,#6366f1);
padding:10px 16px;
border-radius:8px;
color:#fff;
text-decoration:none;
font-size:15px;
font-weight:600;
transition:.2s;
}

.btn-export:hover{
opacity:.9;
transform:translateY(-2px);
}

.card{
border-radius:18px;
}


.badge{
display:inline-flex;
align-items:center;
justify-content:center;
padding:7px 14px;
border-radius:999px;
font-size:13px;
font-weight:600;
gap:6px;
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

.chart-container{
    position: relative;
    width: 100%;
    max-width: 420px;
    height: 320px;
    margin: 20px auto 0;
}

.chart-container canvas{
    width: 100% !important;
    height: 100% !important;
}

</style>

@endsection