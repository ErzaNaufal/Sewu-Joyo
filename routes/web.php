<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| ROOT
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect('/login');
});

/*
/*
|--------------------------------------------------------------------------
| LOGIN PAGE
|--------------------------------------------------------------------------
*/

Route::get('/login', function () {

    if (session('login')) {
        return redirect('/dashboard');
    }

    return view('login');

});

/*
|--------------------------------------------------------------------------
| LOGIN PROCESS
|--------------------------------------------------------------------------
*/

Route::post('/login', function (Request $request) {

    if (
        $request->username === 'admin' &&
        $request->password === 'admin123'
    ) {

        session([
            'login'    => true,
            'username' => 'admin'
        ]);

        return redirect('/dashboard');
    }

    return back()->with(
        'error',
        'Username atau Password salah'
    );

});

/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/

Route::post('/logout', function () {

    session()->flush();

    return redirect('/login')
        ->with(
            'success',
            'Berhasil logout'
        );

})->name('logout');

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['checklogin'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/dashboard',
        [DashboardController::class, 'index']
    );

    /*
    |--------------------------------------------------------------------------
    | PENJUALAN
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/penjualan',
        [DashboardController::class, 'penjualanView']
    );

    Route::post(
        '/penjualan/simpan',
        [DashboardController::class, 'transaksi']
    );

    /*
    |--------------------------------------------------------------------------
    | PREDIKSI
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/prediksi',
        [DashboardController::class, 'prediksiView']
    );

    Route::post(
        '/prediksi',
        [DashboardController::class, 'prediksi']
    );

    /*
    |--------------------------------------------------------------------------
    | ANALISIS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/analisis',
        [DashboardController::class, 'analisis']
    );

    /*
    |--------------------------------------------------------------------------
    | LAPORAN
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/laporan',
        [DashboardController::class, 'laporan']
    );

    Route::get(
        '/laporan/export/pdf',
        [DashboardController::class, 'exportPdf']
    );

    Route::get(
        '/laporan/export/excel',
        [DashboardController::class, 'exportExcel']
    );

});