# Changelog

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

## [2.0.0] - 2026-04-07

### Added

#### Historial Clínico Completo
Se agregaron RelationManagers en la vista del paciente para gestionar su historial médico:

- **MeasurementsRelationManager** (`app/Filament/Resources/Patients/RelationManagers/MeasurementsRelationManager.php`)
  - Registro de mediciones antropométricas (peso, altura, cintura)
  - Cálculo automático del IMC con categorización visual
  - Historial completo con filtros por fecha
  - Asociación opcional con turnos

- **ClinicalNotesRelationManager** (`app/Filament/Resources/Patients/RelationManagers/ClinicalNotesRelationManager.php`)
  - Registro de notas clínicas por consulta
  - Campos: Diagnóstico, Observaciones/Evolución, Plan/Indicaciones
  - Vinculación automática con el turno correspondiente

#### Flujo "Iniciar Consulta"
Se agregó una acción en el widget de **Turnos de Hoy** del Dashboard:

- **Botón "Iniciar Consulta"** (`app/Filament/Widgets/TodayAppointmentsWidget.php`)
  - Solo visible para turnos con estado "Agendado" o "Confirmado"
  - Abre modal con formulario unificado de:
    - Notas clínicas (diagnóstico, observaciones, indicaciones)
    - Mediciones antropométricas
  - Al guardar, cambia automáticamente el estado del turno a "Atendido"
  - Crea/actualiza registros en `clinical_notes` y `measurements`

#### Gestión de Usuarios y Roles
Nuevos recursos para administrar el acceso al sistema:

- **UserResource** (`app/Filament/Resources/Users/`)
  - CRUD completo de usuarios del sistema
  - Asignación de roles
  - Gestión de contraseñas con confirmación
  - Toggle de estado activo/inactivo
  - Páginas: ListUsers, CreateUser, EditUser

- **RoleResource** (`app/Filament/Resources/Roles/`)
  - CRUD de roles del sistema
  - Contador de usuarios por rol
  - Descripción de permisos
  - Páginas: ListRoles, CreateRole, EditRole

#### Estructura de Archivos Agregados

```
app/
├── Filament/
│   ├── Resources/
│   │   ├── Patients/
│   │   │   └── RelationManagers/
│   │   │       ├── MeasurementsRelationManager.php    [NUEVO]
│   │   │       └── ClinicalNotesRelationManager.php   [NUEVO]
│   │   ├── Users/                                      [NUEVO]
│   │   │   ├── UserResource.php
│   │   │   └── Pages/
│   │   │       ├── ListUsers.php
│   │   │       ├── CreateUser.php
│   │   │       └── EditUser.php
│   │   └── Roles/                                      [NUEVO]
│   │       ├── RoleResource.php
│   │       └── Pages/
│   │           ├── ListRoles.php
│   │           ├── CreateRole.php
│   │           └── EditRole.php
│   └── Widgets/
│       └── TodayAppointmentsWidget.php                [MODIFICADO]
├── Models/
│   ├── Patient.php                                    [MODIFICADO]
│   └── ClinicalNote.php                               [MODIFICADO]
```

#### Modelos Modificados

**Patient.php**
- Se agregó relación `clinicalNotes()` para acceder a las notas clínicas del paciente

**PatientResource.php**
- Se registraron los nuevos RelationManagers:
  - `MeasurementsRelationManager`
  - `ClinicalNotesRelationManager`

#### Navegación del Panel

Los nuevos recursos aparecen en el menú lateral:

| Sección | Ícono | Descripción |
|---------|-------|-------------|
| Pacientes | Users | Lista de pacientes con acceso a ficha completa |
| Agenda / Turnos | CalendarDays | Calendario y lista de turnos |
| **Configuración** | | |
| └─ Usuarios | UserCog | Gestión de usuarios del sistema |
| └─ Roles | Shield | Gestión de roles y permisos |

#### Flujo de Trabajo Recomendado

1. **Inicio del día**: Revisar el widget "Turnos para Hoy" en el Dashboard
2. **Paciente llega**: Click en "Iniciar Consulta" (botón verde)
3. **Durante la consulta**: Completar diagnóstico, observaciones e indicaciones
4. **Mediciones**: Expandir sección y registrar peso/altura/cintura
5. **Finalizar**: Click en "Guardar y Finalizar Consulta"
6. **Resultado**: Turno marcado como "Atendido", datos guardados en historial

---

## [Unreleased]

### Performance
- **`app/Filament/Widgets/TodayAppointmentsWidget.php`**: Agregado eager loading de relaciones `patient.personalData` y `status` para prevenir consultas N+1. Antes se ejecutaba 1 query por cada turno del día, ahora se resuelve en una sola consulta
- **`app/Filament/Resources/Patients/PatientResource.php`**: Optimizada columna `last_appointment_date` usando subquery en lugar de `getStateUsing()`. Eliminado el problema de N+1 que ejecutaba una consulta por cada paciente en la tabla (líneas 114-122, 148-157)
- **`app/Filament/Resources/Appointments/AppointmentResource.php`**: Agregado eager loading de `patient.personalData` para evitar consultas adicionales al renderizar nombres completos en la tabla de turnos (línea 135)

### Fixed
- **`app/Models/Patient.php`**: Agregado import faltante `use Illuminate\Database\Eloquent\Relations\HasManyThrough;` requerido por la relación `clinicalNotes()` (línea 10)
- **`app/Models/ClinicalNote.php`**: Eliminada relación incorrecta `patient(): BelongsTo` que causaba error ya que la tabla `clinical_notes` no tiene columna `patient_id`. El acceso al paciente debe hacerse a través de `appointment->patient`
- **`app/Filament/Resources/Patients/PatientResource.php`**: Corregido error de concatenación cuando `birth_date` es null en el infolist (línea 243-245). Cambiado operador null coalescing por operador ternario para prevenir cadenas malformadas como `" ( años)"`

---

## Notas de Versión

### Contexto de Mejoras de Rendimiento (2026-04-07)

Se identificaron y corrigieron múltiples problemas de rendimiento (consultas N+1) que causaban lentitud en la aplicación cuando se trabajaba con muchos registros:

**Problema N+1**: Ocurre cuando se ejecuta 1 consulta principal + N consultas adicionales (una por cada registro). Ejemplo: Si hay 50 pacientes, se ejecutaban 51 consultas en lugar de 2-3.

**Soluciones aplicadas**:
1. **Eager Loading**: Pre-cargar relaciones con `->with()` para evitar consultas lazy
2. **Subqueries**: Usar `addSelect()` con subqueries para calcular valores agregados en una sola consulta

**Impacto**: La carga de tablas con muchos registros ahora es significativamente más rápida (reducción de 90%+ en consultas a la base de datos).

### Contexto de Correcciones de Bugs (2026-04-07)

Se realizó una revisión exhaustiva de los componentes principales del proyecto (modelos, recursos Filament, widgets) identificando y corrigiendo errores críticos que impedían el correcto funcionamiento de la aplicación:

1. **Errores de tipo Fatal**: Imports faltantes que causaban crashes al llamar relaciones
2. **Errores de lógica**: Relaciones que apuntaban a columnas inexistentes en la base de datos
3. **Errores de presentación**: Manejo incorrecto de valores null que generaban texto malformado en la UI

Estos cambios no modifican la funcionalidad existente, solo corrigen bugs que impedían el uso normal de las funciones implementadas.
