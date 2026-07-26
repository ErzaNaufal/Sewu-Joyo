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

            return [
                'Produk'      => $item['produk'],
                'Stok'        => $item['stok'],
                'Prediksi'    => round($item['prediksi']),
                'Status'      => $item['status'],
                'Rekomendasi' => $item['rekomendasi'],
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Produk',
            'Stok Saat Ini',
            'Prediksi',
            'Status',
            'Rekomendasi'
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true
                ]
            ]
        ];
    }
}