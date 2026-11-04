<?php

namespace Tests\Feature;

use App\Models\ObjetivoGd;
use App\Models\Proyecto;
use App\Models\Responsable;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Suite de Pruebas de la Etapa 06: Cartera de Proyectos y Catálogos.
 * Valida listados, filtros, creación, edición con concurrencia optimista, protección FK y catálogo OGD.
 */
class CarteraProyectoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Limpiar posibles proyectos de prueba previos y asegurar la muestra canónica
        DB::connection('mysql')->table('proyecto')->where('codigo_pgd', '099')->delete();
        $this->seed();
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->table('proyecto')->where('codigo_pgd', '099')->delete();
        parent::tearDown();
    }

    /**
     * 1. Listado general de cartera accesible y con datos.
     */
    public function test_listado_cartera_accesible_para_usuarios_autenticados(): void
    {
        $user = Responsable::where('usuario', 'cgarcia')->first(); // Perfil Consulta
        $this->actingAs($user);

        $response = $this->get('/proyectos');

        $response->assertStatus(200);
        $response->assertSee('Cartera de Proyectos de Gobierno Digital');
        $response->assertSee('S/ 8,623,550.00'); // Monto total en métricas
        $response->assertSee('005');
        $response->assertSee('010');
        $response->assertSee('014');
    }

    /**
     * 2. Filtros de búsqueda por estado.
     */
    public function test_filtro_por_estado_cerrado(): void
    {
        $user = Responsable::where('usuario', 'msalas')->first();
        $this->actingAs($user);

        $response = $this->get('/proyectos?estado=Cerrado');

        $response->assertStatus(200);
        $response->assertSee('014');
        $response->assertSee('022');
        $response->assertSee('023');
        $response->assertDontSee('005'); // En curso
    }

    /**
     * 3. Solo Administrador puede ver formulario de creación.
     */
    public function test_permisos_formulario_creacion(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $resp = Responsable::where('usuario', 'aquispe')->first();

        // Admin -> 200
        $this->actingAs($admin);
        $this->get('/proyectos/crear')->assertStatus(200);

        // Responsable -> 403
        $this->actingAs($resp);
        $this->get('/proyectos/crear')->assertStatus(403);
    }

    /**
     * 4. Administrador registra exitosamente un nuevo proyecto PGD.
     */
    public function test_administrador_crea_nuevo_proyecto(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $this->actingAs($admin);

        $objetivo = ObjetivoGd::first();
        $responsable = Responsable::where('perfil', 'Responsable')->first();

        $response = $this->post('/proyectos', [
            'codigo_pgd' => '099',
            'nombre' => 'Proyecto Piloto de Automatización y Gobernanza',
            'monto' => 150000.00,
            'anio_inicio' => 2024,
            'anio_fin' => 2025,
            'estado' => 'En curso',
            'id_objetivo' => $objetivo->id_objetivo,
            'id_responsable' => $responsable->id_responsable,
            'fuente' => 'Cartera PGD 2023 a 2026',
        ]);

        $response->assertRedirect('/proyectos');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('proyecto', [
            'codigo_pgd' => '099',
            'monto' => 150000.00,
            'estado' => 'En curso',
        ]);
    }

    /**
     * 5. Rechazo de duplicidad en código PGD.
     */
    public function test_rechazo_codigo_pgd_duplicado(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $this->actingAs($admin);

        $objetivo = ObjetivoGd::first();
        $responsable = Responsable::where('perfil', 'Responsable')->first();

        // 005 ya existe en la cartera canónica
        $response = $this->post('/proyectos', [
            'codigo_pgd' => '005',
            'nombre' => 'Intento Duplicado de Proyecto',
            'monto' => 200000.00,
            'anio_inicio' => 2023,
            'anio_fin' => 2024,
            'estado' => 'En curso',
            'id_objetivo' => $objetivo->id_objetivo,
            'id_responsable' => $responsable->id_responsable,
        ]);

        $response->assertSessionHasErrors('codigo_pgd');
    }

    /**
     * 6. Vista de detalle muestra métricas, hitos y ejecuciones.
     */
    public function test_vista_detalle_proyecto(): void
    {
        $user = Responsable::where('usuario', 'aquispe')->first();
        $this->actingAs($user);

        $proyecto005 = Proyecto::where('codigo_pgd', '005')->first();

        $response = $this->get("/proyectos/{$proyecto005->id_proyecto}");

        $response->assertStatus(200);
        $response->assertSee('PGD 005');
        $response->assertSee($proyecto005->nombre);
        $response->assertSee('Hitos del Proyecto');
        $response->assertSee('Historial de Cortes Presupuestales');
        $response->assertSee('Avance de Hitos (LB)');
    }

    /**
     * 7. Responsable titular edita su proyecto satisfactoriamente.
     */
    public function test_responsable_titular_edita_su_proyecto(): void
    {
        $ana = Responsable::where('usuario', 'aquispe')->first();
        $proyecto005 = Proyecto::where('codigo_pgd', '005')->first();

        $this->actingAs($ana);

        $response = $this->put("/proyectos/{$proyecto005->id_proyecto}", [
            'nombre' => 'Renovacion de Servidores - Actualizado',
            'monto' => 650000.00,
            'anio_inicio' => 2023,
            'anio_fin' => 2025,
            'id_objetivo' => $proyecto005->id_objetivo,
            'id_responsable' => $proyecto005->id_responsable,
            'fecha_actualizacion_esperada' => (string) $proyecto005->fecha_actualizacion,
            'motivo_cambio' => 'Ampliación de vigencia aprobada por comité',
        ]);

        $response->assertRedirect("/proyectos/{$proyecto005->id_proyecto}");
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('proyecto', [
            'id_proyecto' => $proyecto005->id_proyecto,
            'nombre' => 'Renovacion de Servidores - Actualizado',
            'anio_fin' => 2025,
        ]);
    }

    /**
     * 8. Control de concurrencia optimista detecta modificaciones en paralelo.
     */
    public function test_control_concurrencia_optimista_detecta_conflicto(): void
    {
        $ana = Responsable::where('usuario', 'aquispe')->first();
        $proyecto005 = Proyecto::where('codigo_pgd', '005')->first();

        $this->actingAs($ana);

        // Simulamos enviar una fecha desfasada (otro usuario ya guardó antes)
        $fechaDesfasada = '2020-01-01 00:00:00';

        $response = $this->put("/proyectos/{$proyecto005->id_proyecto}", [
            'nombre' => 'Intento con Concurrencia Desfasada',
            'monto' => 650000.00,
            'anio_inicio' => 2023,
            'anio_fin' => 2024,
            'id_objetivo' => $proyecto005->id_objetivo,
            'id_responsable' => $proyecto005->id_responsable,
            'fecha_actualizacion_esperada' => $fechaDesfasada,
        ]);

        $response->assertSessionHasErrors('concurrencia');

        // Verificar que en base de datos NO se sobreescribió
        $this->assertDatabaseMissing('proyecto', [
            'id_proyecto' => $proyecto005->id_proyecto,
            'nombre' => 'Intento con Concurrencia Desfasada',
        ]);
    }

    /**
     * 9. Protección FK: Intento de eliminar proyecto con hitos asociados es bloqueado con mensaje controlado.
     */
    public function test_intento_eliminar_proyecto_con_hitos_es_bloqueado_por_fk(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $this->actingAs($admin);

        $proyectoConHitos = Proyecto::where('codigo_pgd', '005')->first();
        $this->assertGreaterThan(0, $proyectoConHitos->hitos()->count());

        $response = $this->delete("/proyectos/{$proyectoConHitos->id_proyecto}");

        $response->assertRedirect('/proyectos');
        $response->assertSessionHas('error');

        // El proyecto NO debe haber sido eliminado
        $this->assertDatabaseHas('proyecto', [
            'id_proyecto' => $proyectoConHitos->id_proyecto,
        ]);
    }

    /**
     * 10. Catálogo de Objetivos PGD accesible con conteo y proyectos asociados.
     */
    public function test_catalogo_objetivos_pgd(): void
    {
        $user = Responsable::where('usuario', 'cgarcia')->first();
        $this->actingAs($user);

        $response = $this->get('/objetivos');

        $response->assertStatus(200);
        $response->assertSee('Catálogo de Objetivos de Gobierno Digital');
        $response->assertSee('OGD 01');
        $response->assertSee('OGD 02');
        $response->assertSee('OGD 06');
    }
}
