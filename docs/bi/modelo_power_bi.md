# Modelo Dimensional de Power BI - SIG Electrocentro

Fecha de consolidación: 4 de noviembre de 2026.  
Referencia canónica: [PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md](../../PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md) (Etapa 14).  
Base de datos origen: `dw_seguimiento_gd` (Data Mart de seguimiento).  
Cuenta de lectura técnica: `bi_lector`.

---

## 1. Arquitectura del Modelo Dimensional

El modelo analítico para Power BI corresponde a una **constelación de dos estrellas** (dos tablas de hechos que comparten cuatro dimensiones conformadas de Tipo 1):

```
              ┌─────────────────────┐
              │     dim_tiempo      │
              └──────────┬──────────┘
                         │
        ┌────────────────┼────────────────┐
        │ (1:N activa)   │ (1:N activa /  │ (1:N inactiva cierre)
        ▼                │                ▼
┌──────────────────────┐ │      ┌──────────────────────┐
│ fact_avance_proyecto │ │      │      fact_hito       │
└──────────┬───────────┘ │      └──────────┬───────────┘
           │             │                 │
           ├─────────────┼─────────────────┤
           │ (N:1)       │                 │ (N:1)
           ▼             ▼                 ▼
  ┌─────────────────┐ ┌───────────────┐ ┌─────────────────┐
  │  dim_proyecto   │ │ dim_objetivo  │ │ dim_responsable │
  └─────────────────┘ └───────────────┘ └─────────────────┘
```

---

## 2. Especificación de Tablas

### 2.1 Tablas Dimensionales (Lado 1)

1. **`dim_tiempo` (Tabla de Fechas):**
   - **Llave primaria:** `sk_tiempo` (Entero formato `AAAAMMDD`, ej. `20240630`).
   - **Atributos:** `fecha` (Date), `anio`, `semestre`, `trimestre`, `mes`, `nombre_mes`, `dia_semana`, `nombre_dia`, `es_fin_mes`.
   - **Configuración en Power BI:** Marcar como *Date Table* (*Tabla de fechas*) usando la columna `fecha`.
   - **Ordenación:** Ordenar `nombre_mes` por la columna numérica `mes`.

2. **`dim_proyecto` (Dimensión Proyectos PGD - Tipo 1):**
   - **Llave primaria:** `sk_proyecto` (Entero sustituto).
   - **Llave natural:** `id_proyecto` (Entero del sistema operacional).
   - **Atributos:** `codigo_pgd` (Texto), `nombre`, `monto` (Decimal PEN), `rango_monto`, `anio_inicio`, `anio_fin`, `estado_actual` (Vigente), `es_activo` (1: Pendiente/En curso, 0: Cerrado), `fuente`.

3. **`dim_objetivo` (Dimensión Objetivos de Gobierno Digital - Tipo 1):**
   - **Llave primaria:** `sk_objetivo`.
   - **Atributos:** `codigo` (ej. `OGD 01`), `descripcion`.

4. **`dim_responsable` (Dimensión Responsables - Tipo 1):**
   - **Llave primaria:** `sk_responsable`.
   - **Atributos:** `nombre`, `area`, `perfil` (Administrador, Responsable, Consulta), `es_activo`.
   - **Nota de privacidad:** Esta dimensión **no contiene** `usuario`, `clave_hash` ni contraseñas.

---

### 2.2 Tablas de Hechos (Lado N)

1. **`fact_avance_proyecto` (Hecho Periódico de Cortes Presupuestales):**
   - **Granularidad:** Un proyecto por cada fecha de corte (`id_proyecto` + `fecha_reporte`).
   - **Llaves foráneas:** `sk_proyecto`, `sk_objetivo`, `sk_responsable`, `sk_tiempo`.
   - **Medidas semiaditivas:**
     - `presupuesto_planificado` (Decimal PEN acumulado).
     - `gasto_acumulado` (Decimal PEN acumulado).
     - `diferencia_gasto` (`gasto_acumulado - presupuesto_planificado`).
     - `monto_proyecto` (Monto total asignado).
     - `hitos_linea_base` (Conteo total de hitos en línea base).
     - `hitos_concluidos` (Concluidos a la fecha de corte).
     - `hitos_vencidos` (Vencidos a la fecha de corte).
     - `con_retraso` (Flag 1/0 de retraso al corte).
     - `es_ultimo_corte` (Flag 1/0 del corte más reciente del proyecto).

2. **`fact_hito` (Hecho Atómico de Cumplimiento de Hitos):**
   - **Granularidad:** Un hito individual de proyecto (`id_hito`).
   - **Llaves foráneas:** `sk_proyecto`, `sk_objetivo`, `sk_responsable`, `sk_tiempo_previsto`, `sk_tiempo_cierre` (Nullable).
   - **Atributos y Medidas:**
     - `descripcion` (Texto).
     - `en_linea_base` (Flag 1: Línea base, 0: Fuera de línea base).
     - `concluido` (Flag 1/0).
     - `vencido` (Flag 1/0 evaluado al corte vigente).
     - `dias_desfase` (Días entre cierre y previsto).
     - `concluido_a_tiempo` (Flag 1/0).
     - `tiene_evidencia` (Flag 1/0).
     - `estado_hito` (`Concluido`, `Vencido`, `En plazo`, `Sin corte`).

---

## 3. Relaciones del Modelo en Power BI

| Tabla Origen (1) | Columna Origen | Tabla Destino (N) | Columna Destino | Cardinalidad | Dirección de Filtro | Estado Relación |
| :--- | :--- | :--- | :--- | :---: | :---: | :---: |
| `dim_proyecto` | `sk_proyecto` | `fact_avance_proyecto` | `sk_proyecto` | 1 a Varios (1:*) | Única (dim -> fact) | **Activa** |
| `dim_objetivo` | `sk_objetivo` | `fact_avance_proyecto` | `sk_objetivo` | 1 a Varios (1:*) | Única (dim -> fact) | **Activa** |
| `dim_responsable` | `sk_responsable` | `fact_avance_proyecto` | `sk_responsable` | 1 a Varios (1:*) | Única (dim -> fact) | **Activa** |
| `dim_tiempo` | `sk_tiempo` | `fact_avance_proyecto` | `sk_tiempo` | 1 a Varios (1:*) | Única (dim -> fact) | **Activa** |
| `dim_proyecto` | `sk_proyecto` | `fact_hito` | `sk_proyecto` | 1 a Varios (1:*) | Única (dim -> fact) | **Activa** |
| `dim_objetivo` | `sk_objetivo` | `fact_hito` | `sk_objetivo` | 1 a Varios (1:*) | Única (dim -> fact) | **Activa** |
| `dim_responsable` | `sk_responsable` | `fact_hito` | `sk_responsable` | 1 a Varios (1:*) | Única (dim -> fact) | **Activa** |
| `dim_tiempo` | `sk_tiempo` | `fact_hito` | `sk_tiempo_previsto` | 1 a Varios (1:*) | Única (dim -> fact) | **Activa** |
| `dim_tiempo` | `sk_tiempo` | `fact_hito` | `sk_tiempo_cierre` | 1 a Varios (1:*) | Única (dim -> fact) | *Inactiva (Rol cierre)* |

### Regla Crucial sobre Relaciones:
- **Prohibido crear relaciones bidireccionales:** Mantener siempre filtro unidireccional desde las dimensiones hacia los hechos para evitar ambigüedades y duplicación de medidas.
- **Prohibido crear relaciones directas entre hechos:** `fact_avance_proyecto` y `fact_hito` se comunican exclusivamente a través de las dimensiones compartidas.

---

## 4. Columnas Técnicas Ocultas en Vista de Informes

Para asegurar una experiencia limpia al usuario final, se deben **ocultar** en la vista de informe las llaves técnicas:
- En `dim_tiempo`: ocultar `sk_tiempo`.
- En `dim_proyecto`: ocultar `sk_proyecto`, `id_proyecto`.
- En `dim_objetivo`: ocultar `sk_objetivo`, `id_objetivo`.
- En `dim_responsable`: ocultar `sk_responsable`, `id_responsable`.
- En `fact_avance_proyecto`: ocultar `id_hecho_avance`, `sk_proyecto`, `sk_objetivo`, `sk_responsable`, `sk_tiempo`, `id_proyecto`.
- En `fact_hito`: ocultar `id_hecho_hito`, `sk_proyecto`, `sk_objetivo`, `sk_responsable`, `sk_tiempo_previsto`, `sk_tiempo_cierre`, `id_hito`, `id_proyecto`.
