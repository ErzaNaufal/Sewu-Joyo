<?php

namespace App\Services;

use Yasumi\Yasumi;
use Carbon\Carbon;

class HolidayService
{
    /**
     * Bobot dasar setiap hari libur
     */
    private array $holidayWeights = [

        'Idul Fitri' => 30,

        'Idul Adha' => 20,

        'Natal' => 20,

        'Tahun Baru Masehi' => 20,

        'Tahun Baru Imlek' => 10,

        'Hari Kemerdekaan Republik Indonesia' => 10,

        'Isra Mi\'raj Nabi Muhammad SAW' => 5,

        'Hari Raya Waisak' => 5,

        'Kenaikan Yesus Kristus' => 5,

        'Hari Buruh Internasional' => 5,

        'Hari Lahir Pancasila' => 5,
    ];

    public function check(Carbon $tanggal)
    {
        $tahun = $tanggal->year;

        $holidays = Yasumi::create('Indonesia', $tahun);

        $result = [
            'holiday' => false,
            'holiday_name' => null,
            'days_before' => null,
            'boost' => 0
        ];

        foreach ($holidays as $holiday) {

            $holidayDate = Carbon::parse($holiday->format('Y-m-d'));

            $selisih = $tanggal->diffInDays($holidayDate, false);

            if ($selisih >= 0 && $selisih <= 7) {

                $nama = $holiday->getName();

                $boostDasar = $this->holidayWeights[$nama] ?? 5;

                if ($selisih == 0) {

                    $boost = round($boostDasar * 1.2);

                } elseif ($selisih <= 3) {

                    $boost = $boostDasar;

                } else {

                    $boost = round($boostDasar * 0.5);
                }

                $result = [

                    'holiday' => true,

                    'holiday_name' => $nama,

                    'days_before' => $selisih,

                    'boost' => $boost

                ];

                break;
            }
        }

        return $result;
    }
}