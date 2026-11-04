# SIG Electrocentro S. A. — Sistema de Información Gerencial

Prototipo académico local para la supervisión y seguimiento gerencial de proyectos de gobierno digital de **Electrocentro S. A.** (Cartera PGD 2023–2026, 10 proyectos por S/ 8,623,550.00).

Desarrollado para el curso de **Sistema de Información Gerencial**, Escuela Profesional de Ingeniería de Sistemas, **Universidad Nacional de Huancavelica**.

---

## 1. Requisitos del Entorno

- **Sistema Operativo:** Windows 10/11 (PowerShell 7 recomendado).
- **PHP:** 8.2 o superior (PHP 8.3.28 comprobado) con extensiones `pdo_mysql`, `mbstring`, `openssl`, `intl`, `bcmath`, `fileinfo`.
- **Composer:** 2.8+ (Composer 2.9.2 comprobado).
- **Node.js:** v18+ (v24.19.0 comprobado) con `npm`.
- **Motor de Base de Datos:** MariaDB 10.11 (ejecutándose en `127.0.0.1:3307`).

---

## 2. Puesta en Marcha Rápida

### Paso 1: Iniciar el servidor MariaDB 10.11
Desde la raíz del proyecto:
```powershell
pwsh -File .\scripts\iniciar_mariadb.ps1
```
*(Escucha en `127.0.0.1:3307`, sin interferir con XAMPP ni MySQL locales).*

### Paso 2: Configurar la Aplicación
```powershell
Set-Location -LiteralPath 'sistema'
copy .env.example .env
# Generar clave solo si no está establecida:
php artisan key:generate
```

### Paso 3: Compilar Activos Frontend (Tailwind CSS, Alpine.js, Chart.js)
```powershell
Set-Location -LiteralPath 'sistema'
npm.cmd install
npm.cmd run build
```

### Paso 4: Ejecutar Pruebas de Verificación
```powershell
php artisan test
```

### Paso 5: Iniciar el Servidor Web
```powershell
php artisan serve --port=8000
```
Abrir en el navegador:
- Página de Bienvenida: [http://localhost:8000](http://localhost:8000)
- Chequeo de Salud y Diagnóstico: [http://localhost:8000/salud](http://localhost:8000/salud)

---

## 3. Estructura del Proyecto

```text
proyecto team/
  PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md  # Contrato canónico maestro
  AGENTS.md                               # Reglas breves del agente
  README.md                               # Guía de inicio rápido
  scripts/                                # Utilidades de encendido/apagado de MariaDB
  docs/                                   # Documentación técnica, decisiones y pruebas
    fuentes/                              # Documentos canónicos (Plan e Informe DOCX/TXT)
    decisiones/                           # Registro de decisiones arquitectónicas
    especificacion.md                     # Especificación funcional y reglas R1-R4
    contrato_indicadores.md               # Definición matemática de los 4 KPIs
    matriz_trazabilidad.md                # RF01 a RF08 y backlog
    entorno.md                            # Comandos comprobados
    estado_desarrollo.md                  # Matriz viva de avance
    pruebas_ejecutadas.md                 # Evidencia acumulada
  sistema/                                # Aplicación Laravel 11 (monolito modular)
```

---

## 4. Gobernanza y Puertas de Control (Gates)

- **G1 (Acceso y Seguridad):** Autenticación de tres perfiles (`Administrador`, `Responsable`, `Consulta`) sin `root`.
- **G2 (Operación y Auditoría):** Reglas R1-R4, `control_revision`, `bitacora_motivo` y atomicidad.
- **G3 (Analítica y Reportes):** Motor de 4 KPIs, paridad Operativo vs DW, exportación CSV e imprimible.
- **G4 (Entrega Final):** Restauración comprobada, guion de demostración y cierre académico.
