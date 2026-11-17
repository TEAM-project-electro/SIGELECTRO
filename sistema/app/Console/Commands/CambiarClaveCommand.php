<?php

namespace App\Console\Commands;

use App\Services\ClavesPublicasLegadas;
use App\Services\DatabaseTransactionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CambiarClaveCommand extends Command
{
    protected $signature = 'sig:cambiar-clave {usuario}';
    protected $description = 'Operador local: establece una contraseña privada sin cambiar perfil ni estado';

    public function handle(): int
    {
        if (!$this->input->isInteractive()) {
            $this->error('Se requiere consola interactiva.');
            return self::FAILURE;
        }
        $usuario = (string) $this->argument('usuario');
        $id = DB::connection('lector')->table('responsable')->where('usuario', $usuario)->value('id_responsable');
        if (!$id) {
            $this->error('Cuenta inexistente.');
            return self::FAILURE;
        }
        $clave = $this->secret('Nueva contraseña privada (12 a 72 bytes)', false);
        if (!is_string($clave) || strlen($clave) < 12 || strlen($clave) > 72 || trim($clave) === '' || $clave !== trim($clave) || str_contains($clave, "\0")
            || ClavesPublicasLegadas::contiene($clave)) {
            $this->error('Use una contraseña privada de 12 a 72 bytes, distinta de las demostrativas.');
            return self::FAILURE;
        }
        if ($clave !== $this->secret('Repita la contraseña', false)) {
            $this->error('Las contraseñas no coinciden.');
            return self::FAILURE;
        }
        // Mantenimiento CLI: autoridad del operador local, con motivo auditado.
        DatabaseTransactionService::run(1, function ($db) use ($id, $usuario, $clave) {
            $cuenta = $db->table('responsable')->select('id_responsable')->where('id_responsable', $id)
                ->where('usuario', $usuario)->lockForUpdate()->first();
            if (!$cuenta) {
                throw new \RuntimeException('La cuenta cambió. Repita la operación.');
            }
            $db->table('responsable')->where('id_responsable', $id)->update(['clave_hash' => Hash::make($clave)]);
        }, 'Cambio de clave por operador local mediante CLI');
        $this->info('Contraseña actualizada. Perfil y estado conservados.');
        return self::SUCCESS;
    }
}
