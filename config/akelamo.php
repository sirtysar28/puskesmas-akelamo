<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi aplikasi SIM Puskesmas Akelamo
|--------------------------------------------------------------------------
*/

return [

    /*
    |------------------------------------------------------------------
    | Terminal web Super Admin (/terminal)
    |------------------------------------------------------------------
    | Terminal hanya menjalankan perintah artisan whitelist (migrasi,
    | cache, optimasi) — bukan shell sungguhan. Set false untuk
    | menonaktifkan terminal sepenuhnya (mis. di lingkungan produksi
    | yang sangat ketat).
    */
    'terminal_enabled' => env('TERMINAL_ENABLED', true),

];
