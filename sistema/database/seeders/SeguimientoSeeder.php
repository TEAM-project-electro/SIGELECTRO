<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeguimientoSeeder extends Seeder
{
    /**
     * Carga de hitos y cortes presupuestales demostrativos.
     * Idempotente.
     */
    public function run(): void
    {
        $idProy = DB::connection('escritor')->table('proyecto')->pluck('id_proyecto', 'codigo_pgd')->toArray();

        // 1. HITOS DEMOSTRATIVOS (24 hitos de línea base + 1 hito fuera de línea base)
        $hitos = [
            // 005 - Equipos de ciberseguridad (En curso)
            ['cod' => '005', 'desc' => 'Adquisicion de equipamiento de seguridad', 'prev' => '2023-03-15', 'cie' => '2023-03-15', 'evi' => 'Acta de conformidad N° 012-TIC', 'lb' => 1],
            ['cod' => '005', 'desc' => 'Instalacion y configuracion en sede central', 'prev' => '2024-09-15', 'cie' => null, 'evi' => null, 'lb' => 1], // Vencido al 30/09/2024
            ['cod' => '005', 'desc' => 'Despliegue y pruebas de seguridad perimetral', 'prev' => '2023-08-15', 'cie' => '2024-10-01', 'evi' => 'Conformidad total para pase a cierre', 'lb' => 1],

            // 010 - Software Factory (En curso)
            ['cod' => '010', 'desc' => 'Terminos de referencia y contratacion', 'prev' => '2023-04-30', 'cie' => '2023-04-28', 'evi' => 'Contrato N° 045-2023-ELCTO', 'lb' => 1],
            ['cod' => '010', 'desc' => 'Pase a produccion Sprint 1 a 6', 'prev' => '2024-05-31', 'cie' => '2024-05-30', 'evi' => 'Informe de entrega Fabrica v1', 'lb' => 1],
            ['cod' => '010', 'desc' => 'Entrega de paquetes de automatizacion FONAFE', 'prev' => '2025-11-30', 'cie' => null, 'evi' => null, 'lb' => 1], // En plazo

            // 011 - Fibra optica en subestaciones (En curso)
            ['cod' => '011', 'desc' => 'Estudio de ingenieria y tendido en subestaciones', 'prev' => '2023-08-30', 'cie' => '2023-08-25', 'evi' => 'Informe tecnico de enlace SET Huancayo', 'lb' => 1],
            // Hito fuera de linea base vencido (comprobacion Decision 2: no genera retraso)
            ['cod' => '011', 'desc' => 'Prueba piloto de enlace redundante microondas', 'prev' => '2024-02-15', 'cie' => null, 'evi' => null, 'lb' => 0],

            // 013 - SAP S/4 HANA (En curso)
            ['cod' => '013', 'desc' => 'Definicion de alcance y Business Blueprint', 'prev' => '2023-06-30', 'cie' => '2023-06-25', 'evi' => 'Documento BBP firmado', 'lb' => 1],
            ['cod' => '013', 'desc' => 'Configuracion base del modulo financiero FICO', 'prev' => '2023-12-15', 'cie' => '2023-12-10', 'evi' => 'Acta de pase a pruebas FICO', 'lb' => 1],
            ['cod' => '013', 'desc' => 'Migracion de datos historicos maestros', 'prev' => '2024-08-30', 'cie' => null, 'evi' => null, 'lb' => 1], // Vencido al 30/09/2024
            ['cod' => '013', 'desc' => 'Capacitacion a usuarios clave de operaciones', 'prev' => '2025-06-30', 'cie' => null, 'evi' => null, 'lb' => 1], // En plazo

            // 014 - Comite de Gobierno Digital (Cerrado)
            ['cod' => '014', 'desc' => 'Actualizacion de resolucion de conformacion de comite', 'prev' => '2023-05-15', 'cie' => '2023-05-10', 'evi' => 'Resolucion de Gerencia N° 088-2023', 'lb' => 1],
            ['cod' => '014', 'desc' => 'Aprobacion del reglamento interno de gobernanza digital', 'prev' => '2023-11-30', 'cie' => '2023-11-28', 'evi' => 'Acta de sesion de comite N° 04', 'lb' => 1],

            // 017 - Competencias digitales (En curso)
            ['cod' => '017', 'desc' => 'Diagnostico de brechas digitales del personal', 'prev' => '2023-07-31', 'cie' => '2023-07-20', 'evi' => 'Informe de evaluacion de competencias', 'lb' => 1],
            ['cod' => '017', 'desc' => 'Ejecucion de talleres de herramientas colaborativas', 'prev' => '2024-04-30', 'cie' => '2024-04-25', 'evi' => 'Registro de asistencia y aprobacion', 'lb' => 1],
            ['cod' => '017', 'desc' => 'Programa de certificacion en gestion agil y datos', 'prev' => '2025-10-31', 'cie' => null, 'evi' => null, 'lb' => 1], // En plazo

            // 020 - IPv6 (En curso)
            ['cod' => '020', 'desc' => 'Plan de direccionamiento y evaluacion de routers de borde', 'prev' => '2023-09-30', 'cie' => '2023-09-15', 'evi' => 'Plan de asignacion IPv6', 'lb' => 1],
            ['cod' => '020', 'desc' => 'Habilitacion de dual stack en servidores de servicios web', 'prev' => '2024-03-31', 'cie' => '2024-03-28', 'evi' => 'Informe de prueba de conectividad IPv6', 'lb' => 1],
            ['cod' => '020', 'desc' => 'Migracion total de subredes zonales Huancayo y Tarma', 'prev' => '2025-05-31', 'cie' => null, 'evi' => null, 'lb' => 1], // En plazo

            // 022 - Datos Abiertos (Cerrado)
            ['cod' => '022', 'desc' => 'Identificacion y clasificacion de datasets institucionales', 'prev' => '2023-06-30', 'cie' => '2023-06-15', 'evi' => 'Catalogo de datos priorizados', 'lb' => 1],
            ['cod' => '022', 'desc' => 'Publicacion en plataforma nacional de datos abiertos', 'prev' => '2023-10-31', 'cie' => '2023-10-25', 'evi' => 'Enlace a datosabiertos.gob.pe publicado', 'lb' => 1],

            // 023 - SGSI ISO 27001 (Cerrado)
            ['cod' => '023', 'desc' => 'Analisis de riesgos de seguridad de informacion (SoA)', 'prev' => '2023-05-31', 'cie' => '2023-05-20', 'evi' => 'Matriz de riesgos aprobada', 'lb' => 1],
            ['cod' => '023', 'desc' => 'Auditoria externa y obtencion de certificacion ISO 27001', 'prev' => '2023-12-15', 'cie' => '2023-12-12', 'evi' => 'Certificado de conformidad ISO/IEC 27001', 'lb' => 1],

            // 024 - Mesa de partes digital (En curso)
            ['cod' => '024', 'desc' => 'Desarrollo e integracion con sistema de tramite documentario', 'prev' => '2023-08-31', 'cie' => '2023-08-20', 'evi' => 'Acta de puesta en marcha MPV', 'lb' => 1],
            ['cod' => '024', 'desc' => 'Implementacion de casilla electronica y notificaciones', 'prev' => '2024-05-31', 'cie' => null, 'evi' => null, 'lb' => 1], // Vencido
        ];

        foreach ($hitos as $h) {
            DB::connection('escritor')->table('hito')->updateOrInsert(
                [
                    'id_proyecto' => $idProy[$h['cod']],
                    'descripcion' => $h['desc'],
                ],
                [
                    'fecha_prevista' => $h['prev'],
                    'fecha_cierre' => $h['cie'],
                    'evidencia' => $h['evi'],
                    'en_linea_base' => $h['lb'],
                ]
            );
        }

        // 2. AVANCES PRESUPUESTALES DEMOSTRATIVOS (24 cortes con gasto monotono)
        $avances = [
            // 005 (Monto: 500,000)
            ['cod' => '005', 'fecha' => '2023-03-31', 'plan' => 200000.00, 'gasto' => 150000.00],
            ['cod' => '005', 'fecha' => '2023-06-30', 'plan' => 400000.00, 'gasto' => 380000.00],
            ['cod' => '005', 'fecha' => '2024-09-30', 'plan' => 480000.00, 'gasto' => 450000.00],

            // 010 (Monto: 2,383,550)
            ['cod' => '010', 'fecha' => '2023-03-31', 'plan' => 400000.00, 'gasto' => 350000.00],
            ['cod' => '010', 'fecha' => '2023-06-30', 'plan' => 900000.00, 'gasto' => 820000.00],
            ['cod' => '010', 'fecha' => '2024-09-30', 'plan' => 1500000.00, 'gasto' => 1375000.00],

            // 011 (Monto: 2,500,000)
            ['cod' => '011', 'fecha' => '2023-03-31', 'plan' => 500000.00, 'gasto' => 450000.00],
            ['cod' => '011', 'fecha' => '2023-06-30', 'plan' => 1200000.00, 'gasto' => 1100000.00],
            ['cod' => '011', 'fecha' => '2024-09-30', 'plan' => 1900000.00, 'gasto' => 1750000.00],

            // 013 (Monto: 2,000,000)
            ['cod' => '013', 'fecha' => '2023-03-31', 'plan' => 300000.00, 'gasto' => 280000.00],
            ['cod' => '013', 'fecha' => '2023-06-30', 'plan' => 700000.00, 'gasto' => 690000.00],
            ['cod' => '013', 'fecha' => '2024-09-30', 'plan' => 1200000.00, 'gasto' => 1180000.00],

            // 014 (Monto: 120,000 - Cerrado al cierre 2023)
            ['cod' => '014', 'fecha' => '2023-12-31', 'plan' => 120000.00, 'gasto' => 112000.00],

            // 017 (Monto: 300,000)
            ['cod' => '017', 'fecha' => '2023-03-31', 'plan' => 60000.00, 'gasto' => 50000.00],
            ['cod' => '017', 'fecha' => '2023-06-30', 'plan' => 120000.00, 'gasto' => 110000.00],
            ['cod' => '017', 'fecha' => '2024-09-30', 'plan' => 210000.00, 'gasto' => 196000.00],

            // 020 (Monto: 400,000)
            ['cod' => '020', 'fecha' => '2023-03-31', 'plan' => 80000.00, 'gasto' => 70000.00],
            ['cod' => '020', 'fecha' => '2023-06-30', 'plan' => 180000.00, 'gasto' => 160000.00],
            ['cod' => '020', 'fecha' => '2024-09-30', 'plan' => 280000.00, 'gasto' => 254500.00],

            // 022 (Monto: 108,000 - Cerrado al cierre 2023)
            ['cod' => '022', 'fecha' => '2023-12-31', 'plan' => 108000.00, 'gasto' => 102000.00],

            // 023 (Monto: 212,000 - Cerrado al cierre 2023)
            ['cod' => '023', 'fecha' => '2023-12-31', 'plan' => 212000.00, 'gasto' => 205000.00],

            // 024 (Monto: 100,000 - En curso, ultimo corte 30/06/2024 para probar reporte antiguo)
            ['cod' => '024', 'fecha' => '2023-03-31', 'plan' => 25000.00, 'gasto' => 20000.00],
            ['cod' => '024', 'fecha' => '2023-06-30', 'plan' => 50000.00, 'gasto' => 45000.00],
            ['cod' => '024', 'fecha' => '2024-06-30', 'plan' => 80000.00, 'gasto' => 76000.00],
        ];

        foreach ($avances as $a) {
            DB::connection('escritor')->table('avance_presupuestal')->updateOrInsert(
                [
                    'id_proyecto' => $idProy[$a['cod']],
                    'fecha_reporte' => $a['fecha'],
                ],
                [
                    'presupuesto_planificado' => $a['plan'],
                    'gasto_acumulado' => $a['gasto'],
                ]
            );
        }

        // Cierre formal de proyectos concluidos con sus hitos ya registrados
        DB::connection('escritor')->table('proyecto')
            ->whereIn('codigo_pgd', ['014', '022', '023'])
            ->update(['estado' => 'Cerrado']);
    }
}
