# Changelog

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

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
