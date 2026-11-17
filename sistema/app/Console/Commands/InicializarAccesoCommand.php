<?php

namespace App\Console\Commands;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InicializarAccesoCommand extends Command
{
    protected $signature = 'sig:inicializar';
    protected $description = 'Carga la muestra sin sobrescribir cuentas y solicita una clave privada para el administrador nuevo';

    public function handle(): int
    {
        $anterior = config('sig.clave_administrador_inicial');
        try {
            if (!DB::connection('lector')->table('responsable')->where('usuario', 'msalas')->exists()) {
                if (!$this->input->isInteractive()) {
                    $this->error('El alta inicial requiere una consola interactiva para elegir una contraseña privada.');
                    return self::FAILURE;
                }
                $clave = $this->secret('Contraseña privada del administrador (12 a 72 bytes)', false);
                if (!is_string($clave) || strlen($clave) < 12 || strlen($clave) > 72 || trim($clave) === '' || $clave !== trim($clave) || str_contains($clave, "\0")
                    || \App\Services\ClavesPublicasLegadas::contiene($clave)) {
                    $this->error('La contraseña debe tener entre 12 y 72 bytes.');
                    return self::FAILURE;
                }
                if ($clave !== $this->secret('Repita la contraseña', false)) {
                    $this->error('Las contraseñas no coinciden.');
                    return self::FAILURE;
                }
                config(['sig.clave_administrador_inicial' => $clave]);
            }
            if ($this->call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]) !== self::SUCCESS) {
                return self::FAILURE;
            }
            $this->info('Muestra cargada. Las cuentas existentes conservan sus datos y credenciales.');
            $this->info('Asigne claves privadas a las otras cuentas desde Usuarios antes de utilizarlas.');
            return self::SUCCESS;
        } finally {
            config(['sig.clave_administrador_inicial' => $anterior]);
        }
    }
}
