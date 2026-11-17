# Inventario y Trazabilidad de la Muestra Canónica de Demostración

**Proyecto:** Sistema de Información Gerencial (SIG) - Electrocentro S. A.  
**Versión de los Datos:** Demostrativo Canónico 2023–2026 (10 Proyectos PGD)  
**Fecha de Validación:** 2026-11-03  
**Motor de Verificación:** MariaDB 10.11.11 (`seguimiento_gd`)  

---

## 1. Resumen Ejecutivo de Datos Sembrados

| Entidad | Registros Sembrados | Verificación Canónica | Estado / Observación |
| :--- | :---: | :---: | :--- |
| **Objetivos GD (`objetivo_gd`)** | 6 | 6 objetivos PGD | Códigos `OGD 01` al `OGD 06` |
| **Responsables (`responsable`)** | 4 | 4 perfiles clave | 1 Administrador, 2 Responsables TIC, 1 Consulta |
| **Proyectos (`proyecto`)** | 10 | 10 proyectos PGD | Monto total: **S/ 8,623,550.00** exacto |
| **Hitos (`hito`)** | 26 | 25 LB + 1 Fuera LB | Permite validar oráculo de cálculo con hitos imprevistos |
| **Avances Presupuestales (`avance_presupuestal`)** | 24 | 24 cortes trimestrales | Verificación R1 y R2 cumplidas en cada corte |

---

## 2. Objetivos de Gobierno Digital (`objetivo_gd`)

| ID | Código | Descripción |
| :---: | :---: | :--- |
| 1 | `OGD 01` | Modernizar la gestión institucional y la infraestructura tecnológica |
| 2 | `OGD 02` | Fortalecer la seguridad digital y la gobernanza de datos |
| 3 | `OGD 03` | Digitalizar los servicios y trámites orientados al cliente externo |
| 4 | `OGD 04` | Optimizar y automatizar los procesos de operación comercial y distribución |
| 5 | `OGD 05` | Implementar analítica avanzada e inteligencia de negocios gerencial |
| 6 | `OGD 06` | Promover la cultura digital y el desarrollo de competencias del personal |

---

## 3. Cuentas de Responsables y Credenciales de Demostración

> [!NOTE]
> Las contraseñas se almacenan mediante hashing seguro **bcrypt** (`Hash::make()` en Laravel). Prohibido el uso de SHA2 o contraseñas en texto plano.

| ID | Nombre Completo | Área | Perfil | Usuario | Acceso | Activo |
| :---: | :--- | :--- | :--- | :--- | :--- | :---: |
| 1 | Marta Salas | Planeamiento | `Administrador` | `msalas` | `Clave privada asignada localmente` | 1 |
| 2 | Ana Quispe | TIC | `Responsable` | `aquispe` | `Clave privada asignada localmente` | 1 |
| 3 | Luis Rojas | TIC | `Responsable` | `lrojas` | `Clave privada asignada localmente` | 1 |
| 4 | Carlos García | Comité de GD | `Consulta` | `cgarcia` | `Clave privada asignada localmente` | 1 |

---

## 4. Cartera de Proyectos PGD (`proyecto`)

| ID | PGD | Nombre del Proyecto | Monto (S/) | Años | Estado | Objetivo | Responsable |
| :---: | :---: | :--- | :---: | :---: | :---: | :---: | :---: |
| 1 | `005` | Renovación de Plataforma Tecnológica de Servidores | S/ 650,000.00 | 2023–2024 | En curso | OGD 01 | Ana Quispe |
| 2 | `010` | Sistema de Gestión Comercial y Atención al Cliente | S/ 2,100,000.00 | 2023–2025 | En curso | OGD 03 | Ana Quispe |
| 3 | `011` | Implementación de Infraestructura de Medición Avanzada (AMI) | S/ 2,850,000.00 | 2023–2025 | En curso | OGD 04 | Luis Rojas |
| 4 | `013` | Fortalecimiento del Centro de Control SCADA y Redes OT | S/ 1,450,000.00 | 2023–2024 | En curso | OGD 04 | Luis Rojas |
| 5 | `014` | Actualización de Seguridad Informática Perimetral y SOC | S/ 120,000.00 | 2023–2023 | **Cerrado** | OGD 02 | Ana Quispe |
| 6 | `017` | Sistema Integrado de Gestión Documental y Firma Digital | S/ 315,000.00 | 2023–2024 | En curso | OGD 01 | Ana Quispe |
| 7 | `020` | Plataforma de Inteligencia de Negocios y Analítica | S/ 418,550.00 | 2023–2024 | En curso | OGD 05 | Ana Quispe |
| 8 | `022` | Portal Web Accesible y Trámites Digitales para Clientes | S/ 108,000.00 | 2023–2023 | **Cerrado** | OGD 03 | Luis Rojas |
| 9 | `023` | Plataforma de Capacitación y Gestión del Talento Digital | S/ 212,000.00 | 2023–2023 | **Cerrado** | OGD 06 | Ana Quispe |
| 10 | `024` | Solución Móvil para Inspección Técnica y Mantenimiento | S/ 400,000.00 | 2023–2024 | En curso | OGD 04 | Luis Rojas |
| **TOTAL** | — | **10 Proyectos de Cartera PGD** | **S/ 8,623,550.00** | — | **3 Cerrados / 7 En curso** | — | — |

---

## 5. Distribución de Hitos (`hito`)

- **Total:** 26 hitos.
- **En Línea Base (`en_linea_base = 1`):** 25 hitos procedentes de los planes operativos.
- **Hito Fuera de Línea Base (`en_linea_base = 0`):** 1 hito no previsto asignado a `005` (*"Atención extraordinaria por contingencia en datacenter"*), con fecha cierre 2024-05-15, evidenciando que el oráculo de avance calcula exclusivamente sobre hitos de línea base.
- **Hitos de Proyectos Cerrados (`014`, `022`, `023`):** Todos sus hitos de línea base están debidamente cerrados con fecha y evidencia, respetando la regla R3.

---

## 6. Cortes de Avance Presupuestal (`avance_presupuestal`)

- **Total de Cortes:** 24 registros trimestrales.
- **Consistencia Temporal:**
  - Los proyectos concluidos en 2023 (`014`, `022`, `023`) registran cortes a 2023-06-30 y 2023-12-31.
  - El proyecto `024` tiene su último corte registrado al 2024-06-30 para someter a prueba la comparación de modos (`CORTE_COMUN` vs `ULTIMO_REPORTE_POR_PROYECTO`).
  - Los restantes proyectos en curso (`005`, `010`, `011`, `013`, `017`, `020`) cuentan con corte consolidado al **2024-09-30**.
- **Reglas Validadas en Carga:**
  - `gasto_acumulado <= monto` (Regla R1).
  - `presupuesto_planificado <= monto` (Regla R1).
  - `gasto_acumulado(t) >= gasto_acumulado(t-1)` (Regla R2 de no decrecimiento cronológico).

---

## 7. Verificación de la Vista de Indicadores (`v_indicadores_proyecto`)

Ejecución directa sobre MariaDB 10.11:

```text
PGD  | Estado     | Últ. Reporte | Hitos LB | Vencidos | Presup. Plan   | Gasto Acum     | Desv. % | Retraso
005  | En curso   | 2024-09-30   | 1/3      | 2        | S/ 480,000.00  | S/ 450,000.00  |  -6.3%  | 1 (Sí)
010  | En curso   | 2024-09-30   | 2/3      | 0        | S/ 1,500,000.00| S/ 1,375,000.00|  -8.3%  | 0 (No)
011  | En curso   | 2024-09-30   | 1/1      | 0        | S/ 1,900,000.00| S/ 1,750,000.00|  -7.9%  | 0 (No)
013  | En curso   | 2024-09-30   | 2/4      | 1        | S/ 1,200,000.00| S/ 1,180,000.00|  -1.7%  | 1 (Sí)
014  | Cerrado    | 2023-12-31   | 2/2      | 0        | S/ 120,000.00  | S/ 112,000.00  |  -6.7%  | 0 (No)
017  | En curso   | 2024-09-30   | 2/3      | 0        | S/ 210,000.00  | S/ 196,000.00  |  -6.7%  | 0 (No)
020  | En curso   | 2024-09-30   | 2/3      | 0        | S/ 280,000.00  | S/ 254,500.00  |  -9.1%  | 0 (No)
022  | Cerrado    | 2023-12-31   | 2/2      | 0        | S/ 108,000.00  | S/ 102,000.00  |  -5.6%  | 0 (No)
023  | Cerrado    | 2023-12-31   | 2/2      | 0        | S/ 212,000.00  | S/ 205,000.00  |  -3.3%  | 0 (No)
024  | En curso   | 2024-06-30   | 1/2      | 1        | S/ 80,000.00   | S/ 76,000.00   |  -5.0%  | 1 (Sí)
```


### Acceso privado desde la corrección de seguridad (06/11/2026)
En una instalación nueva, `scripts/instalar.ps1` ejecuta `php artisan sig:inicializar` después de migrar. El comando solicita dos veces una contraseña privada para `msalas`, sin mostrarla ni guardarla en archivos o argumentos. Debe tener entre 12 y 72 bytes. No hay contraseñas predeterminadas públicas. El administrador asigna contraseñas privadas a las demás cuentas desde **Usuarios** antes de entregarlas.

Repetir la inicialización o la siembra conserva las cuentas existentes, incluidas claves, perfil, estado y datos editados. Las claves demostrativas anteriores quedan rechazadas en el login y no pueden volver a asignarse desde Usuarios. En una instalación anterior, el operador debe ejecutar desde `sistema/`: `php artisan sig:cambiar-clave msalas` (y repetir con los demás usuarios que necesiten renovación). El comando pide la contraseña sin mostrarla; conserva perfil/estado y audita el cambio. No escriba contraseñas en argumentos o en el repositorio. Ejecutar `db:seed` directamente en una base nueva genera claves aleatorias no entregadas; utilice el flujo de inicialización interactiva para disponer del acceso administrador.

La sonda pública `/salud` devuelve únicamente `estado` y HTTP 200/503. Los detalles de errores quedan en el registro del servidor.
