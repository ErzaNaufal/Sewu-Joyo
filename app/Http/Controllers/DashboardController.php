<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Exports\LaporanExport;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    // private const API_URL = 'https://sewu-joyo.onrender.com';
    private const API_URL = 'https://sewu-joyo.onrender.com';

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

            $satuan = $this->getSatuanProduk($produk);

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

            // ===================================
            // PENYESUAIAN MENJELANG HARI LIBUR
            // ===================================

            $holidayBoost = 0;
            $holidayName = '-';
            $daysBeforeHoliday = null;

            // tanggal yang dipilih user
            $tanggalPrediksi = Carbon::today();

            // daftar hari libur nasional
            $holidays = [
                '2027-03-28' => 'Idul Fitri',
                '2027-12-25' => 'Natal',
                '2027-01-01' => 'Tahun Baru',
            ];

            // cek H-7 s/d Hari H
            foreach ($holidays as $tgl => $nama) {

                $selisih = $tanggalPrediksi->diffInDays(
                    Carbon::parse($tgl),
                    false
                );

                if ($selisih >= 0 && $selisih <= 7) {

                    $holidayName = $nama;
                    $daysBeforeHoliday = $selisih;

                    if ($selisih == 0) {
                        $holidayBoost = 30;
                    } elseif ($selisih <= 3) {
                        $holidayBoost = 20;
                    } else {
                        $holidayBoost = 10;
                    }

                    break;
                }
            }

            $prediksiAwal = round($prediksi);

            $prediksiAkhir = round(
                $prediksiAwal +
                    ($prediksiAwal * $holidayBoost / 100)
            );

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
                'rekomendasi' => $rekomendasi,
                'satuan' => $satuan,

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
                        'prediksi' => (float)$data[2],
                        'satuan'   => $data[3] ?? '-'

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

                if (count($data) >= 4) {

                    $penjualan[] = [

                        'tanggal' => date(
                            'Y-m-d',
                            strtotime($data[0])
                        ),

                        'produk' => strtolower(
                            trim($data[1])
                        ),

                        'jumlah' => (float) $data[2],

                        'satuan' => trim($data[3])

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
        // RIWAYAT PREDIKSI (SEMUA DATA, KRONOLOGIS)
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
                        'produk'   => strtolower(trim($data[1])),
                        'prediksi' => (float) $data[2],
                        'satuan' => $data[3] ?? '-'
                    ];
                }
            }
        }

        // ==============================
        // GROUP TREND PER PRODUK (untuk combobox filter grafik)
        // Dibuat dari data yang MASIH KRONOLOGIS (belum di-reverse)
        // supaya urutan tanggal di grafik tetap benar (kiri lama -> kanan baru)
        // ==============================
        $trendByProduk = [];

        foreach ($history as $h) {

            $key = $h['produk'];

            if (!isset($trendByProduk[$key])) {
                $trendByProduk[$key] = [
                    'labels' => [],
                    'data'   => []
                ];
            }

            $trendByProduk[$key]['labels'][] = $h['tanggal'];
            $trendByProduk[$key]['data'][]   = round($h['prediksi']);
        }

        // Data gabungan "Semua Produk" (default tampilan awal grafik)
        $trend_labels = array_column($history, 'tanggal');

        $trend_data = array_map(
            fn($h) => round($h['prediksi']),
            $history
        );

        if (empty($trend_labels)) {
            $trend_labels = ['-'];
            $trend_data = [0];
        }

        // ==============================
        // Untuk TABEL riwayat: urutkan terbaru dulu, lalu batasi jumlahnya
        // ==============================
        $history = array_reverse($history);
        $history = array_slice($history, 0, self::HISTORY_LIMIT);

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
            'trendByProduk',
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

            $satuan = $this->getSatuanProduk($produk);

            // Cek apakah produk memiliki histori penjualan
            if (empty($history_penjualan)) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with(
                        'error',
                        'Produk belum memiliki data histori penjualan. Silakan input data penjualan terlebih dahulu sebelum melakukan prediksi.'
                    );
            }

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
            $hasil['satuan'] = $this->getSatuanProduk($produk);

            if (!isset($hasil['prediksi'])) {
                return back()->with('error', 'Response model tidak valid');
            }

            // =====================================================
            // BUSINESS RULE HARI LIBUR NASIONAL
            // =====================================================

            $tanggalPrediksi = Carbon::parse($request->tanggal);

            $holidayName = '-';
            $holidayBoost = 0;
            $daysBeforeHoliday = null;

            $holidayList = [

                // ==========================
                // Hari Libur Tetap
                // ==========================
                '2026-01-01' => [
                    'name' => 'Tahun Baru',
                    'boost' => 20
                ],

                '2026-05-01' => [
                    'name' => 'Hari Buruh',
                    'boost' => 5
                ],

                '2026-06-01' => [
                    'name' => 'Hari Lahir Pancasila',
                    'boost' => 5
                ],

                '2026-08-17' => [
                    'name' => 'Hari Kemerdekaan RI',
                    'boost' => 10
                ],

                '2026-12-25' => [
                    'name' => 'Natal',
                    'boost' => 20
                ],

                // ==========================
                // Hari Libur Tidak Tetap (2026)
                // ==========================
                '2026-01-16' => [
                    'name' => 'Isra Mi\'raj',
                    'boost' => 10
                ],

                '2026-02-17' => [
                    'name' => 'Idul Fitri',
                    'boost' => 30
                ],

                '2026-02-18' => [
                    'name' => 'Idul Fitri',
                    'boost' => 30
                ],

                '2026-03-19' => [
                    'name' => 'Nyepi',
                    'boost' => 10
                ],

                '2026-03-20' => [
                    'name' => 'Imlek',
                    'boost' => 10
                ],

                '2026-05-14' => [
                    'name' => 'Kenaikan Isa Almasih',
                    'boost' => 10
                ],

                '2026-05-24' => [
                    'name' => 'Waisak',
                    'boost' => 10
                ],

                '2026-05-27' => [
                    'name' => 'Idul Adha',
                    'boost' => 20
                ],

                '2026-06-16' => [
                    'name' => 'Tahun Baru Islam',
                    'boost' => 10
                ],

                '2026-08-26' => [
                    'name' => 'Maulid Nabi Muhammad SAW',
                    'boost' => 10
                ],

            ];

            foreach ($holidayList as $tanggal => $item) {

                $libur = Carbon::parse($tanggal);

                $selisih = $tanggalPrediksi->diffInDays($libur, false);

                if ($selisih >= 0 && $selisih <= 7) {

                    $holidayName = $item['name'];

                    $daysBeforeHoliday = $selisih;

                    if ($selisih == 0) {

                        $holidayBoost = round($item['boost'] * 1.2);
                    } elseif ($selisih <= 3) {

                        $holidayBoost = $item['boost'];
                    } else {

                        $holidayBoost = round($item['boost'] * 0.5);
                    }

                    break;
                }
            }

            $prediksiModel = round($hasil['prediksi']);

            $penyesuaian = round(
                $prediksiModel * $holidayBoost / 100
            );

            $rekomendasiStok = $prediksiModel + $penyesuaian;

            // simpan ke hasil
            $hasil['prediksi_model'] = $prediksiModel;
            $hasil['holiday_name'] = $holidayName;
            $hasil['days_before_holiday'] = $daysBeforeHoliday;
            $hasil['holiday_boost'] = $holidayBoost;
            $hasil['penyesuaian'] = $penyesuaian;
            $hasil['rekomendasi_stok'] = $rekomendasiStok;
            $hasil['satuan'] = $satuan;

            // ==============================
            // SIMPAN HISTORY KE CSV
            // ==============================

            if (!Storage::exists('prediksi_history.csv')) {

                Storage::put(
                    'prediksi_history.csv',
                    "tanggal,produk,prediksi,satuan\n"
                );
            }

            Storage::append(
                'prediksi_history.csv',
                implode(',', [
                    $request->tanggal,
                    $produk,
                    $hasil['rekomendasi_stok'],
                    $satuan
                ])
            );

            // ==============================
            // DATA VIEW (baca ulang CSV, urutan KRONOLOGIS dulu)
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
                    'produk'   => strtolower(trim($data[1])),
                    'prediksi' => (float) $data[2],
                    'satuan'   => $data[3] ?? '-'
                ];
            }

            // ==============================
            // GROUP TREND PER PRODUK (untuk combobox filter grafik)
            // Dibuat dari $history yang MASIH KRONOLOGIS
            // ==============================
            $trendByProduk = [];

            foreach ($history as $h) {

                $key = $h['produk'];

                if (!isset($trendByProduk[$key])) {
                    $trendByProduk[$key] = [
                        'labels' => [],
                        'data'   => []
                    ];
                }

                $trendByProduk[$key]['labels'][] = $h['tanggal'];
                $trendByProduk[$key]['data'][]   = round($h['prediksi']);
            }

            // Data gabungan "Semua Produk" (default tampilan grafik)
            $trend_labels = array_column($history, 'tanggal');

            $trend_data = array_map(
                fn($h) => round($h['prediksi']),
                $history
            );

            if (empty($trend_labels)) {
                $trend_labels = ['-'];
                $trend_data = [0];
            }

            // Untuk TABEL riwayat: urutkan terbaru dulu & batasi jumlah
            $history = array_reverse($history);

            if (count($history) > self::HISTORY_LIMIT) {

                $history = array_slice(
                    $history,
                    0,
                    self::HISTORY_LIMIT
                );
            }

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

                'hasil'         => $hasil,
                'produk'        => $produkList,
                'history'       => $history,
                'trend_labels'  => $trend_labels,
                'trend_data'    => $trend_data,
                'trendByProduk' => $trendByProduk,
                'metrics'       => $metrics

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

                    'total'   => 0,

                    'satuan'  => $data[3] ?? '-'

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

                if (count($data) >= 4) {

                    $penjualan[] = [
                        'tanggal' => date('Y-m-d', strtotime($data[0])),
                        'produk'  => strtolower(trim($data[1])),
                        'jumlah'  => (float) $data[2],
                        'satuan'  => trim($data[3])
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
            'produk'  => 'required',
            'jumlah'  => 'required|numeric|min:1',
        ]);

        $path = 'histori_penjualan.csv';

        // Jika file belum ada, buat header
        if (!Storage::exists($path)) {

            Storage::put(
                $path,
                "tanggal,produk,jumlah,satuan\n"
            );
        }

        // Data transaksi
        $baris = implode(',', [

            $request->tanggal . ' ' . now()->format('H:i:s'),

            trim($request->produk),

            (int) $request->jumlah,

            $this->getSatuanProduk($request->produk)

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

    private function getSatuanProduk($produk)
    {
        $mapping = [
            'bakso' => 'Pack',
            'cakwe' => 'Pack',
            'tahu putih' => 'Pack',
            'tempe' => 'Pack',
            'jamur' => 'Pack',

            'kentang' => 'Kg',
            'wortel' => 'Kg',
            'bawang merah' => 'Kg',
            'bawang merah giling' => 'Kg',
            'bawang putih' => 'Kg',
            'bawang putih giling' => 'Kg',
            'cabai merah besar' => 'Kg',
            'rawit merah' => 'Kg',
            'rawit hijau' => 'Kg',
            'kol' => 'Kg',
            'kembang kol' => 'Kg',
            'brokoli' => 'Kg',
            'buncis' => 'Kg',
            'tomat merah' => 'Kg',
            'tomat hijau' => 'Kg',
            'bayam' => 'Kg',
            'pakcoy' => 'Kg',
            'sawi hijau' => 'Kg',
            'sawi putih' => 'Kg',
            'selada' => 'Kg',
            'seledri' => 'Kg',
            'serai' => 'Kg',
            'tauge' => 'Kg',
            'kacang panjang' => 'Kg',
            'kacang hijau' => 'Kg',
            'jagung muda' => 'Kg',
            'jeruk nipis' => 'Kg',
            'singkong' => 'Kg',
            'labu' => 'Kg',

            'daun pisang' => 'Lembar',
        ];

        $produk = strtolower(trim($produk));

        return $mapping[$produk] ?? 'Pack';
    }
}
