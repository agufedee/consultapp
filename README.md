# ConsultApp

ConsultApp es una aplicación que facilita la gestión de citas y turnos en consultorios médicos, proporcionando funcionalidades simplificadas para los profesionales y pacientes.

## Características principales
- Gestión de citas: Creación, edición y cancelación de turnos.
- Recordatorios automáticos a pacientes.
- Widgets personalizados como el calendario de citas.
- Gestión integral de información de pacientes.
- Administración de estados de turnos (Confirmado, Atendido, Cancelado, etc.).
- Diseño moderno y responsive.
- Integración con el sistema Filament y paquetes para visualizaciones como `Filament Full Calendar`.

## Tecnologías utilizadas
- **Laravel 12**: Framework backend.
- **Filament v4**: Herramienta para la creación de paneles de administración.
- **Livewire 3**: Componentes reactivos.
- **Blade**: Motor de Plantillas.
- **Tailwind CSS 4** y **Vite 7**: Para el frontend.
- **MySQL**: Base de datos para almacenar la información.

## Instalación
1. Clona el repositorio:
   ```bash
   git clone https://github.com/agufedee/consultapp.git
   ```
2. Instala las dependencias de Composer:
   ```bash
   composer install
   ```
3. Instala las dependencias de frontend:
   ```bash
   npm install
   ```
4. Copia el archivo `.env.example` a `.env` y ajusta los parámetros según tu entorno.
   ```bash
   cp .env.example .env
   ```
5. Genera la key de la aplicación:
   ```bash
   php artisan key:generate
   ```
6. Ejecuta las migraciones y seeders para la base de datos:
   ```bash
   php artisan migrate --seed
   ```
7. Compila los assets de frontend:
   ```bash
   npm run dev
   ```
8. Inicia el servidor de desarrollo:
   ```bash
   php artisan serve
   ```

## Uso general
Una vez configurado, accede al panel de administración en:
```
http://localhost/consultorio
```
Aquí podrás gestionar los turnos, pacientes y más.

---

## Changelog de Mejoras (MVP v2.0)

### Nuevas Funcionalidades Implementadas

#### 1. Historial Clínico Completo
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

#### 2. Flujo "Iniciar Consulta"
Se agregó una acción en el widget de **Turnos de Hoy** del Dashboard:

- **Botón "Iniciar Consulta"** (`app/Filament/Widgets/TodayAppointmentsWidget.php`)
  - Solo visible para turnos con estado "Agendado" o "Confirmado"
  - Abre modal con formulario unificado de:
    - Notas clínicas (diagnóstico, observaciones, indicaciones)
    - Mediciones antropométricas
  - Al guardar, cambia automáticamente el estado del turno a "Atendido"
  - Crea/actualiza registros en `clinical_notes` y `measurements`

#### 3. Gestión de Usuarios y Roles
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

### Estructura de Archivos Agregados

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

### Modelos Modificados

#### Patient.php
- Se agregó relación `clinicalNotes()` para acceder a las notas clínicas del paciente

#### ClinicalNote.php
- Se agregó relación `patient()` para navegación inversa

#### PatientResource.php
- Se registraron los nuevos RelationManagers:
  - `MeasurementsRelationManager`
  - `ClinicalNotesRelationManager`

### Navegación del Panel

Los nuevos recursos aparecen en el menú lateral:

| Sección | Ícono | Descripción |
|---------|-------|-------------|
| Pacientes | Users | Lista de pacientes con acceso a ficha completa |
| Agenda / Turnos | CalendarDays | Calendario y lista de turnos |
| **Configuración** | | |
| └─ Usuarios | UserCog | Gestión de usuarios del sistema |
| └─ Roles | Shield | Gestión de roles y permisos |

### Flujo de Trabajo Recomendado

1. **Inicio del día**: Revisar el widget "Turnos para Hoy" en el Dashboard
2. **Paciente llega**: Click en "Iniciar Consulta" (botón verde)
3. **Durante la consulta**: Completar diagnóstico, observaciones e indicaciones
4. **Mediciones**: Expandir sección y registrar peso/altura/cintura
5. **Finalizar**: Click en "Guardar y Finalizar Consulta"
6. **Resultado**: Turno marcado como "Atendido", datos guardados en historial

---

## Autores
- [agufedee](https://github.com/agufedee)
- [n0guera](https://github.com/n0guera)
- [EliasViotti](https://github.com/EliasViotti)

---

¡Gracias por utilizar ConsultApp! Si tienes dudas o sugerencias, no dudes en abrir un issue o contribuir al proyecto.
