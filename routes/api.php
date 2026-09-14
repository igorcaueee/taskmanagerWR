<?php

use App\Http\Controllers\Api\FiscalController;
use App\Http\Middleware\EnsureNexusFiscalApiKey;
use Illuminate\Support\Facades\Route;

Route::middleware(EnsureNexusFiscalApiKey::class)->prefix('fiscal')->name('api.fiscal.')->group(function () {
    Route::get('notas', [FiscalController::class, 'index'])->name('notas');
    Route::get('notas/{chaveAcesso}/xml', [FiscalController::class, 'xml'])->name('notas.xml');
    Route::get('notas/{chaveAcesso}/pdf', [FiscalController::class, 'pdf'])->name('notas.pdf');
});
