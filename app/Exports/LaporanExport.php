<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected Collection $data;

    public function __construct(array $data)
    {
        $this->data = collect($data);
    }

    public function collection(): Collection
    {
        return $this->data->map(function ($item) {

            $satuan = ($item['satuan'] ?? '-') != '-'
                ? ' ' . $item['satuan']
                : '';

            $selisih = $item['stok'] - round($item['prediksi']);

            $selisihText = ($selisih > 0 ? '+' : '') . $selisih . $satuan;

            return [
                'Produk' => $item['produk'],

                'Stok Saat Ini' =>
                    $item['stok'] . $satuan,

                'Prediksi' =>
                    round($item['prediksi']) . $satuan,

                'Selisih' =>
                    $selisihText,

                'Status' =>
                    $item['status'],

                'Rekomendasi' =>
                    $item['rekomendasi'],
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Produk',
            'Stok Saat Ini',
            'Prediksi',
            'Selisih',
            'Status',
            'Rekomendasi'
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                ],
            ],
        ];
    }
}