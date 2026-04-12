# Spec: Integration Tests + Policies

## Overview
Agregar cobertura de tests para reglas de negocio y capa de autorización con Laravel Policies.

---

## Business Rules

### BR-001: Turnos no pueden ser en el pasado
- Un turno con `start_date` anterior a `now()` debe ser rechazado
- Validación en Model::creating y en Form

### BR-002: Turnos no pueden superponerse
- Un profesional (user) no puede tener 2 turnos con horarios que se superpongan
- Validación en Model::creating y en Form

### BR-003: Horario de atención válido
- Solo se permiten turnos entre 07:00 y 21:00
- Validación en Model::creating

### BR-004: Paciente inactivo no puede tener turnos nuevos
- Si `patient.active = false`, no se pueden crear turnos nuevos
- Validación en Model::creating

---

## Policies

### P-001: PatientPolicy
| Método | Nutricionista | Secretaria | Admin |
|--------|--------------|-----------|-------|
| view | Sus pacientes | Todos | Todos |
| create | Sí | Sí | Sí |
| update | Sus pacientes | Solo active | Todos |
| delete | No | No | Sí |

### P-002: AppointmentPolicy
| Método | Nutricionista | Secretaria | Admin |
|--------|--------------|-----------|-------|
| view | Sus turnos | Todos | Todos |
| create | Sí | Sí | Sí |
| update | Sus turnos | No | Todos |
| delete | No | No | Sí |

### P-003: UserPolicy
| Método | Nutricionista | Secretaria | Admin |
|--------|--------------|-----------|-------|
| view | No | No | Sí |
| create | No | No | Sí |
| update | No | No | Sí |
| delete | No | No | Sí |

---

## Tests

### T-001: Appointment Business Rules
- `test_appointment_cannot_be_in_the_past`
- `test_appointment_cannot_overlap_same_professional`
- `test_appointment_must_be_within_business_hours`
- `test_cannot_create_appointment_for_inactive_patient`

### T-002: Policies
- `test_nutritionist_can_only_view_own_patients`
- `test_secretary_cannot_delete_patients`
- `test_nutritionist_cannot_update_other_patients`
- `test_admin_can_manage_everything`

---

## Implementation Notes

### Files to Create
- `app/Rules/NoOverlappingAppointments.php`
- `app/Rules/ValidAppointmentDate.php`
- `app/Policies/PatientPolicy.php`
- `app/Policies/AppointmentPolicy.php`
- `app/Policies/UserPolicy.php`

### Files to Modify
- `app/Models/Appointment.php` (boot validations)
- `app/Providers/AuthServiceProvider.php` (register policies)
- `app/Filament/Resources/*/Resource.php` (authorize actions)
- `tests/Feature/`

### Acceptance Criteria
- [ ] Todos los tests pasan
- [ ] Policies integradas en Filament Resources
- [ ] Validaciones de negocio activas en Model
