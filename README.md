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

## Autores
- [agufedee](https://github.com/agufedee)
- [n0guera](https://github.com/n0guera)
- [EliasViotti](https://github.com/EliasViotti)

---

¡Gracias por utilizar ConsultApp! Si tienes dudas o sugerencias, no dudes en abrir un issue o contribuir al proyecto.
