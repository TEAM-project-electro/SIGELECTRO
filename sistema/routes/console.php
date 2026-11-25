<?php

use Illuminate\Support\Facades\Schedule;

// Única programación semanal automática del proceso ETL hacia el Data Warehouse (Cap. 6 BI / Especificación DW)
Schedule::command('etl:cargar')
    ->weeklyOn(0, '02:00')
    ->withoutOverlapping()
    ->description('Carga semanal del Data Warehouse dw_seguimiento_gd');
