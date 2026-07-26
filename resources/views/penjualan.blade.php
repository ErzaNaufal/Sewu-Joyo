@extends('layout')

@section('title', 'Penjualan')

@section('content')

<h1 style="margin-bottom:25px;">🛒 Sistem Penjualan</h1>

@if(session('success'))
<div style="background:#22c55e;color:white;padding:12px;border-radius:8px;margin-bottom:15px;">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div style="background:#ef4444;color:white;padding:12px;border-radius:8px;margin-bottom:15px;">
    {{ session('error') }}
</div>
@endif

@php
    $penjualan = $penjualan ?? [];
    $rekapPenjualan = $rekapPenjualan ?? [];

    $total = count($penjualan);
    $total_qty = collect($penjualan)->sum('jumlah');
    $last = collect($penjualan)->last();

    $grouped = collect($penjualan)
        ->groupBy('produk')
        ->map(fn($items) => $items->sum('jumlah'));

    $top_produk = $grouped->sortDesc()->keys()->first();
@endphp

<!-- ==========================
    PENJELASAN SISTEM
========================== -->

<div class="card" style="margin-bottom:20px;">

    <h3>📌 Alur Sistem</h3>

    <p style="line-height:1.8;">
        Data transaksi penjualan yang diinput akan disimpan sebagai data historis.
        Data historis tersebut digunakan sebagai input model prediksi kebutuhan stok
        menggunakan algoritma Random Forest dengan pendekatan time series
        melalui fitur <strong>Lag-1</strong>, <strong>Lag-2</strong>, dan
        <strong>Lag-3</strong>.
    </p>

</div>

<!-- ==========================
    SUMMARY
========================== -->

<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));margin-bottom:20px;">

    <div class="card">
        <p>Total Transaksi</p>
        <h2>{{ $totalTransaksi }}</h2>
    </div>

    <div class="card">
        <p>Total Penjualan</p>
        <h2>{{ $totalPenjualan }}</h2>
    </div>

    <div class="card">
        <p>Produk Terlaris</p>
        <h2>{{ $produkTerlaris }}</h2>
    </div>

</div>

<!-- ==========================
    FORM INPUT
========================== -->

<div class="card" style="margin-bottom:20px;">

    <h3>📥 Input Transaksi Penjualan</h3>

    <form method="POST" action="{{ url('/penjualan/simpan') }}">

        @csrf

        <div class="form-grid">

            {{-- TANGGAL --}}
            <div class="form-group">

                <label>Tanggal Penjualan</label>

                <input
                    type="date"
                    name="tanggal"
                    value="{{ old('tanggal', date('Y-m-d')) }}"
                    required>

            </div>

            {{-- PRODUK --}}
            <div class="form-group">

                <label>Produk</label>

                <select name="produk" required>

                    <option value="">-- Pilih Produk --</option>

                    @foreach($produk as $p)

                        <option value="{{ $p }}"
                            {{ old('produk') == $p ? 'selected' : '' }}>

                            {{ $p }}

                        </option>

                    @endforeach

                </select>

            </div>

            {{-- JUMLAH --}}
            <div class="form-group">

                <label>Jumlah Terjual</label>

                <input
                    type="number"
                    name="jumlah"
                    min="1"
                    value="{{ old('jumlah') }}"
                    required>

            </div>

        </div>
        <br>

        <button type="submit">
            💾 Simpan Transaksi
        </button>

    </form>

</div>

<!-- ==========================
    GRAFIK
========================== -->

<div class="card" style="margin-bottom:20px;">

    <h3>📊 Grafik Penjualan per Produk</h3>

    @if($grouped->count())

        <canvas id="chartPenjualan"></canvas>

    @else

        <p style="opacity:.6;">Belum ada data penjualan.</p>

    @endif

</div>

<!-- ==========================
    DATASET HISTORI
========================== -->

<div class="card">

    <h3>📜 Dataset Penjualan (Data Historis)</h3>

    @if($total > 0)

    <div style="overflow-x:auto;">

        <table class="table">

            <thead>

                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Produk</th>
                    <th>Jumlah</th>
                </tr>

            </thead>

            <tbody>

            @foreach($penjualan as $i => $p)

                <tr>

                    <td>{{ $i + 1 }}</td>

                    <td>
                        {{ \Carbon\Carbon::parse($p['tanggal'])->format('Y-m-d') }}
                    </td>

                    <td class="left">{{ $p['produk'] }}</td>

                    <td>{{ $p['jumlah'] }}</td>

                </tr>

            @endforeach

            </tbody>

        </table>

    </div>

    <p style="margin-top:15px;font-size:13px;opacity:.7;">
        Data transaksi di atas digunakan sebagai data historis untuk membentuk
        fitur time series yang selanjutnya diproses oleh model Random Forest
        dalam memprediksi kebutuhan stok barang.
    </p>

    @else

        <p style="opacity:.6;">Belum ada data penjualan.</p>

    @endif

</div>

<!-- ==========================
    REKAP PENJUALAN HARIAN
========================== -->

<div class="card" style="margin-top:20px;">

    <h3>📊 Rekap Penjualan Harian per Produk</h3>

    @if(count($rekapPenjualan) > 0)

    <div style="overflow-x:auto;">

        <table class="table">

            <thead>

                <tr>

                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Produk</th>
                    <th>Total Penjualan</th>

                </tr>

            </thead>

            <tbody>

            @foreach($rekapPenjualan as $i => $item)

                <tr>

                    <td>{{ $i + 1 }}</td>

                    <td>{{ $item['tanggal'] }}</td>

                    <td class="left">{{ ucfirst($item['produk']) }}</td>

                    <td>{{ $item['total'] }}</td>

                </tr>

            @endforeach

            </tbody>

        </table>

    </div>

    <p style="margin-top:15px;font-size:13px;opacity:.7;">

        Rekap ini merupakan hasil akumulasi seluruh transaksi penjualan berdasarkan
        tanggal dan produk. Data ini akan digunakan sebagai dasar pembentukan
        fitur <strong>Lag-1</strong>, <strong>Lag-2</strong>,
        <strong>Lag-3</strong>, <strong>Rolling Mean</strong>,
        <strong>Rolling Standard Deviation</strong>,
        <strong>Rolling Maximum</strong>, dan
        <strong>Rolling Minimum</strong> pada proses prediksi kebutuhan stok.

    </p>

    @else

        <p style="opacity:.6;">
            Belum ada data rekap penjualan.
        </p>

    @endif

</div>

@if($grouped->count())

<script>

new Chart(document.getElementById('chartPenjualan'),{

    type:'bar',

    data:{

        labels:{!! json_encode($grouped->keys()) !!},

        datasets:[{

            label:'Total Penjualan',

            data:{!! json_encode($grouped->values()) !!},

            backgroundColor:'#38bdf8',

            borderRadius:6

        }]

    },

    options:{

        responsive:true,

        plugins:{
            legend:{
                display:false
            }
        },

        scales:{
            y:{
                beginAtZero:true
            }
        }

    }

});

</script>

@endif

<style>

.table{

    width:100%;
    border-collapse:collapse;
    text-align:center;

}

.table th{

    padding:10px;
    border-bottom:2px solid rgba(255,255,255,.2);

}

.table td{

    padding:10px;
    border-bottom:1px solid rgba(255,255,255,.05);

}

.left{

    text-align:left;

}

</style>

@endsection