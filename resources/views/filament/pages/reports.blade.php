<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filters Section -->
        <div class="rounded-lg bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold">Filtros</h3>
            
            <form class="mt-4">
                {{ $this->form }}
            </form>
        </div>

        <!-- Tabs for Different Reports -->
        <div x-data="{ activeTab: 'nuevos' }" class="space-y-6">
            <!-- Tab Navigation -->
            <div class="border-b border-gray-200">
                <div class="flex gap-4">
                    <button
                        @click="activeTab = 'nuevos'"
                        :class="activeTab === 'nuevos' ? 'border-b-2 border-primary-600 text-primary-600' : 'text-gray-600 hover:text-gray-900'"
                        class="px-4 py-3 text-sm font-semibold transition"
                    >
                        Pacientes Nuevos
                    </button>
                    <button
                        @click="activeTab = 'retencion'"
                        :class="activeTab === 'retencion' ? 'border-b-2 border-primary-600 text-primary-600' : 'text-gray-600 hover:text-gray-900'"
                        class="px-4 py-3 text-sm font-semibold transition"
                    >
                        Retención 30 Días
                    </button>
                    <button
                        @click="activeTab = 'ausentismo'"
                        :class="activeTab === 'ausentismo' ? 'border-b-2 border-primary-600 text-primary-600' : 'text-gray-600 hover:text-gray-900'"
                        class="px-4 py-3 text-sm font-semibold transition"
                    >
                        Ausentismo
                    </button>
                </div>
            </div>

            <!-- Pacientes Nuevos Tab -->
            <div x-show="activeTab === 'nuevos'" class="space-y-4">
                <!-- Data Table -->
                <div class="rounded-lg bg-white shadow-sm overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">ID Paciente</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Nombre</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Profesional</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Motivo</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Fecha Turno</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($this->getNewPatients() as $appointment)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">{{ $appointment->patient_id }}</td>
                                    <td class="px-6 py-4">{{ $appointment->patient?->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-4">{{ $appointment->user?->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-4">{{ $appointment->reason ?? 'N/A' }}</td>
                                    <td class="px-6 py-4">{{ $appointment->start_date->format('d/m/Y H:i') }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold"
                                            :class="{
                                                'bg-green-100 text-green-800': '{{ $appointment->status?->value ?? $appointment->status ?? "" }}' === 'Atendido',
                                                'bg-blue-100 text-blue-800': '{{ $appointment->status?->value ?? $appointment->status ?? "" }}' === 'Confirmado',
                                                'bg-gray-100 text-gray-800': '{{ $appointment->status?->value ?? $appointment->status ?? "" }}' === 'Agendado',
                                                'bg-red-100 text-red-800': '{{ $appointment->status?->value ?? $appointment->status ?? "" }}' === 'Cancelado',
                                                'bg-yellow-100 text-yellow-800': '{{ $appointment->status?->value ?? $appointment->status ?? "" }}' === 'Ausente',
                                            }"
                                        >
                                            {{ $appointment->status?->value ?? $appointment->status ?? 'N/A' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No hay datos disponibles</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Export Button -->
                @if($this->getNewPatients()->count() > 0)
                    <div class="flex justify-end">
                        <button wire:click="exportNewPatients" class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-green-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Exportar CSV
                        </button>
                    </div>
                @endif
            </div>

            <!-- Retención 30 Días Tab -->
            <div x-show="activeTab === 'retencion'" class="space-y-4">
                <!-- Data Table -->
                <div class="rounded-lg bg-white shadow-sm overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">ID Paciente</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Nombre</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Primer Turno</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Retenido</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Segundo Turno</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Días</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($this->getRetention() as $record)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">{{ $record->patient_id }}</td>
                                    <td class="px-6 py-4">{{ $record->patient_name }}</td>
                                    <td class="px-6 py-4">{{ $record->first_appointment_date->format('d/m/Y') }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold"
                                            :class="{ 'bg-green-100 text-green-800': {{ $record->retained ? 'true' : 'false' }}, 'bg-red-100 text-red-800': {{ !$record->retained ? 'true' : 'false' }} }"
                                        >
                                            {{ $record->retained ? 'Sí' : 'No' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">{{ $record->second_appointment_date?->format('d/m/Y') ?? 'N/A' }}</td>
                                    <td class="px-6 py-4">{{ $record->days_to_retention ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No hay datos disponibles</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Export Button -->
                @if($this->getRetention()->count() > 0)
                    <div class="flex justify-end">
                        <button wire:click="exportRetention" class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-green-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Exportar CSV
                        </button>
                    </div>
                @endif
            </div>

            <!-- Ausentismo Tab -->
            <div x-show="activeTab === 'ausentismo'" class="space-y-4">
                <!-- Data Table -->
                <div class="rounded-lg bg-white shadow-sm overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">ID Paciente</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Nombre</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Fecha</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Hora</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Día Semana</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Franja</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700">Motivo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($this->getAbsenteeism() as $record)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">{{ $record->patient_id }}</td>
                                    <td class="px-6 py-4">{{ $record->patient_name }}</td>
                                    <td class="px-6 py-4">{{ $record->date }}</td>
                                    <td class="px-6 py-4">{{ $record->time }}</td>
                                    <td class="px-6 py-4">{{ $record->day_of_week }}</td>
                                    <td class="px-6 py-4">{{ $record->time_slot }}</td>
                                    <td class="px-6 py-4">{{ $record->reason ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No hay datos disponibles</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Export Button -->
                @if($this->getAbsenteeism()->count() > 0)
                    <div class="flex justify-end">
                        <button wire:click="exportAbsenteeism" class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-green-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Exportar CSV
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
