<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">

<style>
@page { margin: 80px 40px; }

body{
    font-family: Arial, sans-serif;
    font-size:12px;
    color:#000;
}

.header{
    position:fixed;
    top:-60px;
    left:0;
    right:0;
}

.footer{
    position:fixed;
    bottom:-50px;
    left:0;
    right:0;
    text-align:center;
    font-size:10px;
}

.pagenum:before{
    content:counter(page);
}

.header table{
    width:100%;
}

.logo{
    width:60px;
}

.title{
    text-align:center;
}

.title h2{
    margin:0;
    font-size:16px;
}

.title h3{
    margin:3px 0;
    font-size:13px;
}

.address{
    font-size:10px;
}

.line1{
    border-top:2px solid #000;
    margin-top:5px;
}

.line2{
    border-top:1px solid #000;
    margin-bottom:10px;
}

.section{
    margin-top:15px;
}

table.data{
    width:100%;
    border-collapse:collapse;
    margin-top:10px;
}

table.data th,
table.data td{
    border:1px solid #000;
    padding:6px;
    text-align:center;
}

table.data th{
    background:#f2f2f2;
}

.left{
    text-align:left;
}

.ttd{
    width:220px;
    float:right;
    text-align:center;
    margin-top:40px;
}

.ttd .line{
    margin-top:60px;
    border-top:1px solid #000;
}
</style>

</head>

<body>

@php

$total = count($data ?? []);

$over = collect($data ?? [])
            ->where('status','Overstock')
            ->count();

$under = collect($data ?? [])
            ->where('status','Understock')
            ->count();

$aman = collect($data ?? [])
            ->where('status','Aman')
            ->count();

$no = 'LPR-'.date('Ymd').'-'.rand(100,999);

$now = now()->format('d-m-Y H:i:s');

@endphp

<!-- ================= HEADER ================= -->

<div class="header">

<table>

<tr>

<td width="70">
<img src="{{ public_path('logo.png') }}" class="logo">
</td>

<td class="title">

<h2>LAPORAN ANALISIS STOK BARANG</h2>

<h3>TOKO SEWU JOYO</h3>

<div class="address">
Perum Griya Prima Galaxy 3 Blok B3 No.16<br>
Bekasi, Jawa Barat
</div>

</td>

<td width="120" style="text-align:right">

<b>No :</b> {{ $no }}<br>

<b>Tanggal :</b><br>

{{ date('d-m-Y') }}

</td>

</tr>

</table>

<div class="line1"></div>
<div class="line2"></div>

</div>

<!-- ================= FOOTER ================= -->

<div class="footer">

Halaman <span class="pagenum"></span> |
Dicetak : {{ $now }}

</div>

<!-- ================= CONTENT ================= -->

<div class="section">

<b>Waktu Generate :</b>

{{ $now }}

</div>

<div class="section">

<b>Ringkasan Analisis</b>

<ul>

<li>Total Produk : {{ $total }}</li>

<li>Overstock : {{ $over }}</li>

<li>Understock : {{ $under }}</li>

<li>Aman : {{ $aman }}</li>

</ul>

<p>

Laporan ini dihasilkan secara otomatis menggunakan
metode <b>Random Forest</b> berdasarkan data
penjualan terbaru yang tersedia pada sistem.

</p>

</div>

<!-- ================= TABLE ================= -->

<div class="section">

@if($total > 0)

<table class="data">

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

@foreach($data as $i => $d)

<tr>

<td>{{ $i+1 }}</td>

<td class="left">{{ $d['produk'] }}</td>

<td>{{ $d['stok'] }}{{ $d['satuan'] != '-' ? ' '.$d['satuan'] : '' }}</td>

<td>{{ round($d['prediksi']) }}{{ $d['satuan'] != '-' ? ' '.$d['satuan'] : '' }}</td>

@php
    $selisih = $d['stok'] - round($d['prediksi']);
@endphp

<td>
@if($selisih > 0)
    +{{ $selisih }}{{ $d['satuan'] != '-' ? ' '.$d['satuan'] : '' }}
@elseif($selisih < 0)
    {{ $selisih }}{{ $d['satuan'] != '-' ? ' '.$d['satuan'] : '' }}
@else
    0{{ $d['satuan'] != '-' ? ' '.$d['satuan'] : '' }}
@endif
</td>

<td>{{ $d['status'] }}</td>

<td>

@if($d['status']=='Overstock')

Stok berlebih

@elseif($d['status']=='Understock')

Perlu penambahan stok

@else

Stok dalam kondisi aman

@endif

</td>

<td>

{{ $d['rekomendasi'] }}

</td>

</tr>

@endforeach

</table>

@else

<p style="text-align:center">

Data analisis belum tersedia.

</p>

@endif

</div>

<!-- ================= TTD ================= -->

<div class="ttd">

Bekasi,
{{ date('d-m-Y') }}

<br><br>

Administrator

<div class="line"></div>

<div style="margin-top:20px;font-size:10px">

Laporan ini dihasilkan secara otomatis oleh sistem
prediksi stok berbasis Machine Learning
(Random Forest).

</div>

</div>

</body>
</html>