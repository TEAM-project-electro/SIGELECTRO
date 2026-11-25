<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catálogos iniciales de la gestión gerencial: criterios de priorización y umbrales de alerta.
 * Solo inserta lo que falta: nunca sobrescribe pesos ni umbrales que la organización ya ajustó.
 *
 * Los criterios salen de la auditoría del 25/11/2026 y arrancan con el mismo peso (1,00).
 * Los pesos definitivos los aprueba el Comité de Gobierno Digital; el sistema no los inventa.
 */
class GestionGerencialSeeder extends Seeder
{
    public function run(): void
    {
        $db = DB::connection('escritor');
        if (!$db->getSchemaBuilder()->hasTable('criterio_priorizacion')) {
            return;
        }

        $criterios = [
            ['ESTR-ALIN', 'Alineamiento estratégico', 'Contribución a los objetivos del PEI (OEI 12) y del PGD (OGD).', 'Estratégico'],
            ['ESTR-NORM', 'Cumplimiento normativo', 'Obligación legal o regulatoria (Gobierno Digital, seguridad de la información, OSINERGMIN).', 'Estratégico'],
            ['SERV-CONT', 'Continuidad del servicio eléctrico', 'Efecto sobre la continuidad y calidad del suministro.', 'Servicio eléctrico'],
            ['SERV-USUA', 'Impacto en los usuarios', 'Mejora para clientes o usuarios internos.', 'Servicio eléctrico'],
            ['ECON-BENE', 'Beneficio económico', 'Ahorro o ingreso estimado frente a la inversión.', 'Económico'],
            ['ECON-OPER', 'Beneficio operativo', 'Eficiencia de procesos y reducción de tiempos o errores.', 'Económico'],
            ['ECON-PRES', 'Disponibilidad presupuestal', 'Fondos asignados o asegurados para el periodo.', 'Económico'],
            ['EJEC-URGE', 'Urgencia', 'Costo o riesgo de postergar el proyecto.', 'Ejecución'],
            ['EJEC-RIES', 'Riesgo controlado', 'Menor nivel de riesgo de ejecución (5 = riesgo bajo).', 'Ejecución'],
            ['EJEC-VIAB', 'Viabilidad técnica', 'Madurez de la solución y capacidad técnica para implementarla.', 'Ejecución'],
            ['EJEC-PREP', 'Preparación del proyecto', 'Expediente, términos de referencia y aprobaciones previas listos.', 'Ejecución'],
            ['EJEC-RRHH', 'Disponibilidad de recursos humanos', 'Personal propio o contratado disponible para ejecutarlo.', 'Ejecución'],
        ];
        foreach ($criterios as $i => [$codigo, $nombre, $descripcion, $grupo]) {
            if (!$db->table('criterio_priorizacion')->where('codigo', $codigo)->exists()) {
                $db->table('criterio_priorizacion')->insert([
                    'codigo' => $codigo, 'nombre' => $nombre, 'descripcion' => $descripcion,
                    'grupo' => $grupo, 'peso' => 1.00, 'activo' => 1, 'orden' => $i + 1,
                ]);
            }
        }

        $parametros = [
            ['dias_aviso_hito', 15, 'días', 'Anticipación con que se avisa un hito de línea base próximo a vencer.'],
            ['dias_sin_actualizar', 100, 'días', 'Días sin corte presupuestal ni cambios tras los cuales un proyecto activo se considera sin actualizar.'],
            ['umbral_desviacion', -5, '%', 'Desviación presupuestal a partir de la cual se alerta un desfase de devengado (valor negativo).'],
            ['umbral_sobregasto', 0, '%', 'Desviación positiva a partir de la cual se alerta un gasto superior al plan.'],
            ['exposicion_critica', 15, 'puntos', 'Exposición (probabilidad × impacto, 1 a 25) desde la cual un riesgo abierto es crítico.'],
            ['exposicion_media', 8, 'puntos', 'Exposición desde la cual un riesgo es de nivel medio.'],
            ['exigir_acta_cierre', 0, '0/1', 'Si vale 1, un proyecto no se cierra sin un acta adjunta y al menos un beneficio planificado.'],
        ];
        foreach ($parametros as [$clave, $valor, $unidad, $descripcion]) {
            if (!$db->table('parametro_alerta')->where('clave', $clave)->exists()) {
                $db->table('parametro_alerta')->insert([
                    'clave' => $clave, 'valor' => $valor, 'unidad' => $unidad, 'descripcion' => $descripcion,
                ]);
            }
        }
    }
}
