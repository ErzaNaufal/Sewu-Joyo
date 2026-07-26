<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Exports\LaporanExport;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
private const API_URL = 'http://127.0.0.1:5000';

    // Konstanta aplikasi
    private const DEFAULT_LAG = 20;
    private const ROLLING_WINDOW = 7;
    private const HTTP_TIMEOUT = 10;
    private const HISTORY_LIMIT = 10;

    private function getProdukList(): array
    {
        try {

            $response = Http::timeout(self::HTTP_TIMEOUT)
                ->get(self::API_URL . '/produk');

            if (!$response->successful()) {
                return [];
            }

            $produk = $response->json()['produk'] ?? [];

            sort($produk);

            return $produk;

        } catch (\Exception $e) {

            return [];

        }
    }
    // ==============================
    // ANALISIS REALTIME
    // ==============================
    private function generateAnalisisData()
    {
        $produkList = $this->getProdukList();

        $data = [];

        foreach ($produkList as $produk) {

            // ==============================
            // AMBIL REKAP PENJUALAN HARIAN
            // ==============================
            $filtered = $this->getRekapPenjualan($produk);

            $count = count($filtered);

            // Ambil total penjualan harian
            $nilai = array_column($filtered, 'total');

            // ==============================
            // FEATURE ENGINEERING
            // ==============================
            if (empty($nilai)) {

                $lag1 = self::DEFAULT_LAG;
                $lag2 = $lag1;
                $lag3 = $lag2;

                $histori7 = [
                    $lag1,
                    $lag2,
                    $lag3
                ];

            } else {

                $lag1 = $count >= 1
                    ? $nilai[$count - 1]
                    : self::DEFAULT_LAG;

                $lag2 = $count >= 2
                    ? $nilai[$count - 2]
                    : $lag1;

                $lag3 = $count >= 3
                    ? $nilai[$count - 3]
                    : $lag2;

                $histori7 = array_slice(
                    $nilai,
                    -self::ROLLING_WINDOW
                );

                if (empty($histori7)) {

                    $histori7 = [
                        $lag1,
                        $lag2,
                        $lag3
                    ];

                }

            }

            $rolling_mean_7 =
                array_sum($histori7) / count($histori7);

            $rolling_max_7 =
                max($histori7);

            $rolling_min_7 =
                min($histori7);

            $variance = 0;

            foreach ($histori7 as $item) {

                $variance += pow(
                    $item - $rolling_mean_7,
                    2
                );

            }

            $rolling_std_7 =
                sqrt(
                    $variance / count($histori7)
                );

            // ==============================
            // REQUEST KE FLASK
            // ==============================
            $response = Http::timeout(self::HTTP_TIMEOUT)
                ->post(self::API_URL . '/predict', [

                    'produk' => $produk,
                    'tanggal' => now()->toDateString(),

                    'lag1' => $lag1,
                    'lag2' => $lag2,
                    'lag3' => $lag3,

                    'rolling_mean_7' => $rolling_mean_7,
                    'rolling_std_7' => $rolling_std_7,
                    'rolling_max_7' => $rolling_max_7,
                    'rolling_min_7' => $rolling_min_7

                ]);

            if (!$response->successful()) {
                continue;
            }

            $json = $response->json();

            if (!isset($json['prediksi'])) {
                continue;
            }

            $prediksi = (float) $json['prediksi'];

            // ==============================
            // STOK SAAT INI
            // ==============================

            // Nilai acuan berdasarkan rata-rata penjualan historis (Rolling Mean)
            // Digunakan sebagai representasi kondisi stok untuk analisis
            $stok = round($rolling_mean_7);

            $toleransi = max(
                10,
                $prediksi * 0.2
            );

            if ($stok > ($prediksi + $toleransi)) {

                $status = 'Overstock';
                $rekomendasi = 'Kurangi pembelian';

            } elseif ($stok < ($prediksi - $toleransi)) {

                $status = 'Understock';
                $rekomendasi = 'Tambah stok';

            } else {

                $status = 'Aman';
                $rekomendasi = 'Stok sesuai';

            }

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
        $produk = $this->getProdukList();

        // ==============================
        // HISTORI PREDIKSI DARI CSV
        // ==============================
        $history = [];

        if (Storage::exists('prediksi_history.csv')) {

            $rows = explode(
                "\n",
                trim(Storage::get('prediksi_history.csv'))
            );

            // Hapus header
            array_shift($rows);

            foreach ($rows as $row) {

                if (trim($row) == '') {
                    continue;
                }

                $data = str_getcsv($row);

                if (count($data) >= 3) {

                    $history[] = [

                        'tanggal'  => $data[0],
                        'produk'   => strtolower(trim($data[1])),
                        'prediksi' => (float)$data[2]

                    ];

                }

            }

        }

        // ==============================
        // HISTORI PENJUALAN DARI CSV
        // ==============================
        $penjualan = [];

        if (Storage::exists('histori_penjualan.csv')) {

            $rows = explode(
                "\n",
                trim(Storage::get('histori_penjualan.csv'))
            );

            // Hapus header
            array_shift($rows);

            foreach ($rows as $row) {

                if (trim($row) == '') {
                    continue;
                }

                $data = str_getcsv($row);

                if (count($data) >= 3) {

                    $penjualan[] = [

                        'tanggal' => date(
                            'Y-m-d',
                            strtotime($data[0])
                        ),

                        'produk' => strtolower(
                            trim($data[1])
                        ),

                        'jumlah' => (float)$data[2]

                    ];

                }

            }

        }

        // ==============================
        // TRANSAKSI TERBARU
        // ==============================
        $penjualan = array_reverse($penjualan);

        // ==============================
        // STATISTIK
        // ==============================
        $totalProduk = count($produk);

        $totalTransaksi = count($penjualan);

        $totalPenjualan = array_sum(
            array_column($penjualan, 'jumlah')
        );

        // ==============================
        // PRODUK TERLARIS
        // ==============================
        $produkCount = [];

        foreach ($penjualan as $item) {

            if (!isset($produkCount[$item['produk']])) {

                $produkCount[$item['produk']] = 0;

            }

            $produkCount[$item['produk']] += $item['jumlah'];

        }

        arsort($produkCount);

        $produkTerlaris = '-';

        if (!empty($produkCount)) {

            $produkTerlaris = array_key_first($produkCount);

        }

        // ==============================
        // TREND CHART
        // ==============================
        $trend_labels = array_column(
            $history,
            'tanggal'
        );

        $trend_data = array_map(
            fn($h) => round($h['prediksi']),
            $history
        );

        if (empty($trend_labels)) {

            $trend_labels = ['-'];
            $trend_data = [0];

        }

        return view('dashboard', [

            'produk'          => $produk,

            'history'         => $history,

            'penjualan'       => $penjualan,

            'trend_labels'    => $trend_labels,

            'trend_data'      => $trend_data,

            'totalProduk'     => $totalProduk,

            'totalTransaksi'  => $totalTransaksi,

            'totalPenjualan'  => $totalPenjualan,

            'produkTerlaris'  => $produkTerlaris

        ]);
    }   
    // ==============================
    // VIEW PREDIKSI
    // ==============================
    public function prediksiView()
    {
        // Daftar produk
        $produk = $this->getProdukList();

        // ==============================
        // RIWAYAT PREDIKSI
        // ==============================
        $history = [];

        if (Storage::exists('prediksi_history.csv')) {

            $rows = explode(
                "\n",
                trim(Storage::get('prediksi_history.csv'))
            );

            // Hapus header CSV
            array_shift($rows);

            foreach ($rows as $row) {

                if (trim($row) === '') {
                    continue;
                }

                $data = str_getcsv($row);

                if (count($data) >= 3) {

                    $history[] = [
                        'tanggal'  => $data[0],
                        'produk'   => $data[1],
                        'prediksi' => (float) $data[2]
                    ];

                }
            }
        }

        // Batasi jumlah histori
        if (count($history) > self::HISTORY_LIMIT) {

            $history = array_slice(
                $history,
                -self::HISTORY_LIMIT
            );

        }

        // Data grafik
        $trend_labels = array_column(
            $history,
            'tanggal'
        );

        $trend_data = array_map(
            fn($h) => round($h['prediksi']),
            $history
        );

        // ==============================
        // AMBIL METRIK MODEL DARI FLASK
        // ==============================
        $metrics = null;

        try {

            $response = Http::timeout(self::HTTP_TIMEOUT)
                ->get(self::API_URL . '/metrics');

            if ($response->successful()) {

                $metrics = $response->json();

            }

        } catch (\Exception $e) {

            // Jika API gagal diakses,
            // halaman tetap ditampilkan
            $metrics = null;

            \Log::error(
                'Gagal mengambil metrics: ' .
                $e->getMessage()
            );

        }

        return view('prediksi', compact(
            'produk',
            'history',
            'trend_labels',
            'trend_data',
            'metrics'
        ));
    }
    // ==============================
    // PROSES PREDIKSI
    // ==============================
    public function prediksi(Request $request)
    {
        $request->validate([
            'produk' => 'required',
            'tanggal' => 'required|date'
        ]);

        try {

            // ==============================
            // INPUT USER
            // ==============================
            $produk = strtolower(trim($request->produk));

            // ==============================
            // AMBIL REKAP PENJUALAN HARIAN
            // ==============================

            $history_penjualan = $this->getRekapPenjualan($produk);

            // ==============================
            // FEATURE ENGINEERING
            // ==============================

            $count = count($history_penjualan);

            // Ambil hanya total penjualan harian
            $nilai = array_column($history_penjualan, 'total');

            // Jika histori kosong
            if (empty($nilai)) {

                $lag1 = self::DEFAULT_LAG;
                $lag2 = $lag1;
                $lag3 = $lag2;

                $histori7 = [
                    $lag1,
                    $lag2,
                    $lag3
                ];

            } else {

                $lag1 = $count >= 1
                    ? $nilai[$count - 1]
                    : self::DEFAULT_LAG;

                $lag2 = $count >= 2
                    ? $nilai[$count - 2]
                    : $lag1;

                $lag3 = $count >= 3
                    ? $nilai[$count - 3]
                    : $lag2;

                $histori7 = array_slice(
                    $nilai,
                    -self::ROLLING_WINDOW
                );

                if (empty($histori7)) {

                    $histori7 = [
                        $lag1,
                        $lag2,
                        $lag3
                    ];

                }

            }

            $rolling_mean_7 =
                array_sum($histori7) / count($histori7);

            $rolling_max_7 =
                max($histori7);

            $rolling_min_7 =
                min($histori7);

            $variance = 0;

            foreach ($histori7 as $nilaiItem) {

                $variance += pow(
                    $nilaiItem - $rolling_mean_7,
                    2
                );

            }

            $rolling_std_7 = sqrt(
                $variance / count($histori7)
            );

            // ==============================
            // REQUEST API
            // ==============================
            $response = Http::timeout(self::HTTP_TIMEOUT)->post(
                self::API_URL . '/predict',
                [
                    'produk' => $produk,
                    'tanggal' => $request->tanggal,
                    'lag1' => $lag1,
                    'lag2' => $lag2,
                    'lag3' => $lag3,
                    'rolling_mean_7' => $rolling_mean_7,
                    'rolling_std_7' => $rolling_std_7,
                    'rolling_max_7' => $rolling_max_7,
                    'rolling_min_7' => $rolling_min_7,
                ]
            );

            if (!$response->successful()) {

                dd([
                    'status' => $response->status(),
                    'body'   => $response->body(),
                    'json'   => $response->json(),
                ]);

            }

            $hasil = $response->json();
           
            if (!isset($hasil['prediksi'])) {
                return back()->with('error', 'Response model tidak valid');
            }

            // ==============================
            // SIMPAN HISTORY KE CSV
            // ==============================

            if (!Storage::exists('prediksi_history.csv')) {

                Storage::put(
                    'prediksi_history.csv',
                    "tanggal,produk,prediksi\n"
                );
            }

            Storage::append(
                'prediksi_history.csv',
                implode(',', [
                    $request->tanggal,
                    $hasil['produk'],
                    round($hasil['prediksi'])
                ])
            );

            // ==============================
            // DATA VIEW
            // ==============================
            $produkList = $this->getProdukList();

            $history = [];

            $rows = explode("\n", trim(Storage::get('prediksi_history.csv')));

            array_shift($rows);

            foreach ($rows as $row) {

                if (trim($row) == '') {
                    continue;
                }

                $data = str_getcsv($row);

                $history[] = [
                    'tanggal'  => $data[0],
                    'produk'   => $data[1],
                    'prediksi' => (float) $data[2]
                ];
            }

            if (count($history) > self::HISTORY_LIMIT) {

                $history = array_slice(
                    $history,
                    -self::HISTORY_LIMIT
                );
            }

            $trend_labels = array_column($history, 'tanggal');

            $trend_data = array_map(
                fn($h) => round($h['prediksi']),
                $history
            );

            // ==============================
            // AMBIL METRIK MODEL
            // ==============================
            $metrics = null;

            try {

                $metricResponse = Http::timeout(self::HTTP_TIMEOUT)
                    ->get(self::API_URL . '/metrics');

                if ($metricResponse->successful()) {
                    $metrics = $metricResponse->json();
                }

            } catch (\Exception $e) {

                $metrics = null;

            }

            return view('prediksi', [

                'hasil'        => $hasil,
                'produk'       => $produkList,
                'history'      => $history,
                'trend_labels' => $trend_labels,
                'trend_data'   => $trend_data,
                'metrics'      => $metrics

            ]);

            } catch (\Throwable $e) {

                return response()->json([
                    'message' => $e->getMessage(),
                    'line'    => $e->getLine(),
                    'file'    => $e->getFile(),
                ]);

            }

        }
    
        // ==============================
        // REKAP PENJUALAN HARIAN PER PRODUK
        // ==============================
        private function getRekapPenjualan($produk = null)
        {
            $rekap = [];

            if (!Storage::exists('histori_penjualan.csv')) {
                return [];
            }

            $rows = explode("\n", trim(Storage::get('histori_penjualan.csv')));

            // Lewati header
            array_shift($rows);

            foreach ($rows as $row) {

                if (trim($row) == '') {
                    continue;
                }

                $data = str_getcsv($row);

                if (count($data) < 3) {
                    continue;
                }

                $tanggal = date(
                    'Y-m-d',
                    strtotime($data[0])
                );

                $namaProduk = strtolower(trim($data[1]));

                // Filter jika produk dipilih
                if ($produk !== null && $namaProduk !== strtolower($produk)) {
                    continue;
                }

                $key = $tanggal . '_' . $namaProduk;

                if (!isset($rekap[$key])) {

                    $rekap[$key] = [

                        'tanggal' => $tanggal,

                        'produk' => $namaProduk,

                        'total' => 0

                    ];

                }

                $rekap[$key]['total'] += (float)$data[2];

            }

            $rekap = array_values($rekap);

            usort($rekap, function ($a, $b) {

                return strtotime($a['tanggal'])
                    <=>
                    strtotime($b['tanggal']);

            });

            return $rekap;
        }


        // ==============================
        // VIEW PENJUALAN
        // ==============================
        public function penjualanView()
        {
           
            // ==============================
            // AMBIL LIST PRODUK
            // ==============================
            $produk = $this->getProdukList();

            // ==============================
            // BACA HISTORI PENJUALAN
            // ==============================
            $penjualan = [];

            if (Storage::exists('histori_penjualan.csv')) {

                $rows = explode("\n", trim(Storage::get('histori_penjualan.csv')));

                // Hapus header
                array_shift($rows);

                foreach ($rows as $row) {

                    if (trim($row) == '') {
                        continue;
                    }

                    $data = str_getcsv($row);

                    if (count($data) >= 3) {

                        $penjualan[] = [
                            'tanggal' => date('Y-m-d', strtotime($data[0])),
                            'produk'  => strtolower(trim($data[1])),
                            'jumlah'  => (float) $data[2]
                        ];
                    }
                }
            }

            // ==============================
            // TRANSAKSI TERBARU DI ATAS
            // ==============================
            $penjualan = array_reverse($penjualan);

          
            // ==============================
            // REKAP PENJUALAN HARIAN
            // ==============================
            $rekapPenjualan = $this->getRekapPenjualan();

            // ==============================
            // HITUNG STATISTIK
            // ==============================
            $totalTransaksi = count($penjualan);

            $totalPenjualan = array_sum(
                array_column($penjualan, 'jumlah')
            );

            $produkCount = [];

            foreach ($penjualan as $item) {

                if (!isset($produkCount[$item['produk']])) {
                    $produkCount[$item['produk']] = 0;
                }

                $produkCount[$item['produk']] += $item['jumlah'];
            }

            arsort($produkCount);

            $produkTerlaris = '-';

            if (!empty($produkCount)) {
                $produkTerlaris = array_key_first($produkCount);
            }

            // ==============================
            // TAMPILKAN VIEW
            // ==============================
            return view('penjualan', [

                'produk'          => $produk,

                'penjualan'       => $penjualan,

                'rekapPenjualan'  => $rekapPenjualan,

                'totalTransaksi'  => $totalTransaksi,

                'totalPenjualan'  => $totalPenjualan,

                'produkTerlaris'  => $produkTerlaris

            ]);
        }

    // ==============================
    // SIMPAN TRANSAKSI
    // ==============================
    public function transaksi(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'produk' => 'required',
            'jumlah' => 'required|numeric|min:1'
        ]);

        $path = 'histori_penjualan.csv';

        // Jika file belum ada, buat header
        if (!Storage::exists($path)) {

            Storage::put(
                $path,
                "tanggal,produk,jumlah\n"
            );

        }

        // Data transaksi
        $baris = implode(',', [

            $request->tanggal . ' ' . now()->format('H:i:s'),

            trim($request->produk),

            (int)$request->jumlah

        ]);

        // Simpan transaksi
        Storage::append($path, $baris);

    

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

    // ==============================
    // EXPORT EXCEL
    // ==============================
    public function exportExcel()
    {
        $data = $this->generateAnalisisData();

        return Excel::download(

            new LaporanExport($data),

            'laporan_stok.xlsx'

        );
    }
}