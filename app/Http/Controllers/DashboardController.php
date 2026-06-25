<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;

class DashboardController extends Controller
{
    private $api = 'http://127.0.0.1:5000';

    // ==============================
    // ANALISIS REALTIME
    // ==============================
    private function generateAnalisisData()
    {
        try {

            $res = Http::timeout(5)
                ->get($this->api . '/produk');

            if (!$res->successful()) {
                return [];
            }

            $produkList = $res->json()['produk'] ?? [];

        } catch (\Exception $e) {

            return [];

        }

        $history_penjualan = session('penjualan', []);

        $data = [];

        foreach ($produkList as $produk) {

            // ==============================
            // FILTER PRODUK
            // ==============================
            $filtered = array_values(
                array_filter(
                    $history_penjualan,
                    fn($h) =>
                    $h['produk'] == $produk
                )
            );

            $count = count($filtered);

            // ==============================
            // AUTO LAG
            // ==============================
            $lag1 = $count >= 1
                ? $filtered[$count - 1]['jumlah']
                : 20;

            $lag2 = $count >= 2
                ? $filtered[$count - 2]['jumlah']
                : $lag1;

            $lag3 = $count >= 3
                ? $filtered[$count - 3]['jumlah']
                : $lag2;

            try {

                // ==============================
                // REQUEST API
                // ==============================
                $response = Http::timeout(5)
                    ->post($this->api . '/predict', [

                    'produk' => $produk,

                    'tanggal' => now()->toDateString(),

                    'lag1' => $lag1,

                    'lag2' => $lag2,

                    'lag3' => $lag3

                ]);

                if (!$response->successful()) {
                    continue;
                }

                $json = $response->json();

                if (!isset($json['prediksi'])) {
                    continue;
                }

                $prediksi = (float)
                    $json['prediksi'];

            } catch (\Exception $e) {

                continue;

            }

            // ==============================
            // HITUNG STOK
            // ==============================
            $stok = round(
                ($lag1 + $lag2 + $lag3) / 3
            );

            // ==============================
            // TOLERANSI
            // ==============================
            $toleransi = max(
                10,
                $prediksi * 0.2
            );

            // ==============================
            // STATUS
            // ==============================
            if (
                $stok >
                $prediksi + $toleransi
            ) {

                $status = 'Overstock';

                $rekomendasi =
                    'Kurangi pembelian';

            }

            elseif (
                $stok <
                $prediksi - $toleransi
            ) {

                $status = 'Understock';

                $rekomendasi =
                    'Tambah stok';

            }

            else {

                $status = 'Aman';

                $rekomendasi =
                    'Stok sesuai';

            }

            // ==============================
            // SIMPAN DATA
            // ==============================
            $data[] = [

                'produk' => $produk,

                'stok' => $stok,

                'prediksi' => round($prediksi),

                'status' => $status,

                'rekomendasi' => $rekomendasi

            ];

        }

        return $data;
    }

    // ==============================
    // DASHBOARD
    // ==============================
    public function index()
    {
        $produk = [];

        try {

            $res = Http::timeout(5)
                ->get($this->api . '/produk');

            if ($res->successful()) {

                $produk =
                    $res->json()['produk'] ?? [];

            }

        } catch (\Exception $e) {}

        $history = session('history', []);

        $penjualan = session(
            'penjualan',
            []
        );

        // ==============================
        // TREND CHART
        // ==============================
        $trend_labels = array_column(
            $history,
            'tanggal'
        );

        $trend_data = array_map(
            fn($h) =>
            round($h['prediksi']),
            $history
        );

        if (empty($trend_labels)) {

            $trend_labels = ['-'];

            $trend_data = [0];

        }

        return view(
            'dashboard',
            compact(
                'produk',
                'trend_labels',
                'trend_data',
                'history',
                'penjualan'
            )
        );
    }

    // ==============================
    // VIEW PREDIKSI
    // ==============================
    public function prediksiView()
    {
        $produk = [];

        try {

            $res = Http::timeout(5)
                ->get($this->api . '/produk');

            if ($res->successful()) {

                $produk =
                    $res->json()['produk'] ?? [];

            }

        } catch (\Exception $e) {}

        // ==============================
        // HISTORY
        // ==============================
        $history = session(
            'history',
            []
        );

        // ==============================
        // TREND
        // ==============================
        $trend_labels = array_column(
            $history,
            'tanggal'
        );

        $trend_data = array_map(
            fn($h) =>
            round($h['prediksi']),
            $history
        );

        return view(
            'prediksi',
            compact(
                'produk',
                'history',
                'trend_labels',
                'trend_data'
            )
        );
    }

    // ==============================
    // PROSES PREDIKSI
    // ==============================
    public function prediksi(Request $request)
    {
        $request->validate([

            'produk' => 'required',

            'tanggal' => 'required|date',

            'penjualan' =>
                'required|numeric|min:0'

        ]);

        try {

            // ==============================
            // INPUT USER
            // ==============================
            $penjualan_input = (float)
                $request->penjualan;

            // ==============================
            // HISTORY PENJUALAN
            // ==============================
            $history_penjualan = session(
                'penjualan',
                []
            );

            // ==============================
            // FILTER PRODUK
            // ==============================
            $filtered = array_values(
                array_filter(
                    $history_penjualan,
                    fn($h) =>
                    $h['produk'] ==
                    $request->produk
                )
            );

            $count = count($filtered);

            // ==============================
            // AUTO LAG
            // ==============================
            $lag1 = $penjualan_input;

            $lag2 = $count >= 1
                ? $filtered[$count - 1]['jumlah']
                : $lag1;

            $lag3 = $count >= 2
                ? $filtered[$count - 2]['jumlah']
                : $lag2;

            // ==============================
            // REQUEST API
            // ==============================
            $response = Http::timeout(5)
                ->post($this->api . '/predict', [

                'produk' => trim(
                    $request->produk
                ),

                'tanggal' =>
                    $request->tanggal,

                'lag1' => $lag1,

                'lag2' => $lag2,

                'lag3' => $lag3

            ]);

            // ==============================
            // VALIDASI API
            // ==============================
            if (!$response->successful()) {

                return back()->with(
                    'error',
                    'Prediksi gagal (API)'
                );

            }

            $hasil = $response->json();

            if (
                !isset($hasil['prediksi'])
            ) {

                return back()->with(
                    'error',
                    'Response model tidak valid'
                );

            }

            // ==============================
            // HISTORY PREDIKSI
            // ==============================
            $history = session(
                'history',
                []
            );

            $history[] = [

                'tanggal' =>
                    $request->tanggal,

                'produk' =>
                    $hasil['produk'],

                'prediksi' =>
                    $hasil['prediksi']

            ];

            // ==============================
            // LIMIT HISTORY
            // ==============================
            if (count($history) > 10) {

                $history = array_slice(
                    $history,
                    -10
                );

            }

            session([
                'history' => $history
            ]);

            // ==============================
            // PRODUK
            // ==============================
            $produk = Http::timeout(5)
                ->get($this->api . '/produk')
                ->json()['produk'] ?? [];

            // ==============================
            // TREND
            // ==============================
            $trend_labels = array_column(
                $history,
                'tanggal'
            );

            $trend_data = array_map(
                fn($h) =>
                round($h['prediksi']),
                $history
            );

            return view(
                'prediksi',
                compact(
                    'hasil',
                    'produk',
                    'history',
                    'trend_labels',
                    'trend_data'
                )
            );

        } catch (\Exception $e) {

            return back()->with(
                'error',
                'Terjadi kesalahan sistem'
            );

        }
    }

    // ==============================
    // VIEW PENJUALAN
    // ==============================
    public function penjualanView()
    {
        $produk = [];

        try {

            $res = Http::timeout(5)
                ->get($this->api . '/produk');

            if ($res->successful()) {

                $produk =
                    $res->json()['produk'] ?? [];

            }

        } catch (\Exception $e) {}

        $penjualan = collect(
            session('penjualan', [])
        )
        ->sortByDesc('tanggal')
        ->values()
        ->toArray();

        return view(
            'penjualan',
            compact(
                'produk',
                'penjualan'
            )
        );
    }

    // ==============================
    // SIMPAN TRANSAKSI
    // ==============================
    public function transaksi(Request $request)
    {
        $request->validate([

            'produk' => 'required',

            'jumlah' =>
                'required|numeric|min:1'

        ]);

        $data = session(
            'penjualan',
            []
        );

        $data[] = [

            'tanggal' =>
                now()->toDateTimeString(),

            'produk' =>
                $request->produk,

            'jumlah' =>
                (int)$request->jumlah

        ];

        // ==============================
        // LIMIT DATA
        // ==============================
        if (count($data) > 100) {

            $data = array_slice(
                $data,
                -100
            );

        }

        session([
            'penjualan' => $data
        ]);

        return redirect('/penjualan')
            ->with(
                'success',
                'Transaksi berhasil disimpan'
            );
    }

    // ==============================
    // ANALISIS
    // ==============================
    public function analisis()
    {
        $data =
            $this->generateAnalisisData();

        return view(
            'analisis',
            compact('data')
        );
    }

    // ==============================
    // LAPORAN
    // ==============================
    public function laporan()
    {
        $data =
            $this->generateAnalisisData();

        return view(
            'laporan',
            compact('data')
        );
    }

    // ==============================
    // EXPORT PDF
    // ==============================
    public function exportPdf()
    {
        $data =
            $this->generateAnalisisData();

        return Pdf::loadView(
            'laporan_pdf',
            compact('data')
        )
        ->download(
            'laporan_stok.pdf'
        );
    }
}