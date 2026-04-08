# Changelog

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

## [Unreleased]

### Fixed
- **`app/Models/Patient.php`**: Agregado import faltante `use Illuminate\Database\Eloquent\Relations\HasManyThrough;` requerido por la relación `clinicalNotes()` (línea 10)
- **`app/Models/ClinicalNote.php`**: Eliminada relación incorrecta `patient(): BelongsTo` que causaba error ya que la tabla `clinical_notes` no tiene columna `patient_id`. El acceso al paciente debe hacerse a través de `appointment->patient`
- **`app/Filament/Resources/Patients/PatientResource.php`**: Corregido error de concatenación cuando `birth_date` es null en el infolist (línea 243-245). Cambiado operador null coalescing por operador ternario para prevenir cadenas malformadas como `" ( años)"`

---

## Notas de Versión

### Contexto de Correcciones (2026-04-07)

Se realizó una revisión exhaustiva de los componentes principales del proyecto (modelos, recursos Filament, widgets) identificando y corrigiendo errores críticos que impedían el correcto funcionamiento de la aplicación:

1. **Errores de tipo Fatal**: Imports faltantes que causaban crashes al llamar relaciones
2. **Errores de lógica**: Relaciones que apuntaban a columnas inexistentes en la base de datos
3. **Errores de presentación**: Manejo incorrecto de valores null que generaban texto malformado en la UI

Estos cambios no modifican la funcionalidad existente, solo corrigen bugs que impedían el uso normal de las funciones implementadas.
