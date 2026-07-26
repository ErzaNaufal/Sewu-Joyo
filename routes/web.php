<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| ROOT
|--------------------------------------------------------------------------
*/

Route::redirect('/', '/login');

/*
|--------------------------------------------------------------------------
| LOGIN PAGE
|--------------------------------------------------------------------------
*/

Route::get('/login', function () {

    if (session()->has('login')) {
        return redirect()->route('dashboard');
    }

    return view('login');

})->name('login');

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

        return redirect()->route('dashboard');
    }

    return back()->with(
        'error',
        'Username atau Password salah'
    );

})->name('login.process');

/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/

Route::post('/logout', function () {

    session()->flush();

    return redirect()
        ->route('login')
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
    )->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | PENJUALAN
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/penjualan',
        [DashboardController::class, 'penjualanView']
    )->name('penjualan');

    Route::post(
        '/penjualan/simpan',
        [DashboardController::class, 'transaksi']
    )->name('penjualan.simpan');

    /*
    |--------------------------------------------------------------------------
    | PREDIKSI
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/prediksi',
        [DashboardController::class, 'prediksiView']
    )->name('prediksi');

    Route::post(
        '/prediksi',
        [DashboardController::class, 'prediksi']
    )->name('prediksi.proses');

    /*
    |--------------------------------------------------------------------------
    | ANALISIS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/analisis',
        [DashboardController::class, 'analisis']
    )->name('analisis');

    /*
    |--------------------------------------------------------------------------
    | LAPORAN
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/laporan',
        [DashboardController::class, 'laporan']
    )->name('laporan');

    Route::get(
        '/laporan/export/pdf',
        [DashboardController::class, 'exportPdf']
    )->name('laporan.pdf');

    Route::get(
        '/laporan/export/excel',
        [DashboardController::class, 'exportExcel']
    )->name('laporan.excel');

});