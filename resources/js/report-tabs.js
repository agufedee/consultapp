/**
 * Alpine.js component for the Reports page tabs.
 * Usage: <div x-data="reportTabs()">
 */
export function reportTabs() {
    return {
        activeTab: 'nuevos',

        /**
         * Get the class string for an active/inactive tab button.
         */
        tabClass(tabName) {
            return this.activeTab === tabName
                ? 'border-b-2 border-primary-600 text-primary-600'
                : 'text-gray-600 hover:text-gray-900';
        },

        /**
         * Get badge color classes for appointment status.
         */
        statusBadgeClass(status) {
            const statusMap = {
                Atendido: 'bg-green-100 text-green-800',
                Confirmado: 'bg-blue-100 text-blue-800',
                Agendado: 'bg-gray-100 text-gray-800',
                Cancelado: 'bg-red-100 text-red-800',
                Ausente: 'bg-yellow-100 text-yellow-800',
            };
            return statusMap[status] ?? 'bg-gray-100 text-gray-800';
        },

        /**
         * Get retention badge classes.
         */
        retentionBadgeClass(isRetained) {
            return isRetained
                ? 'bg-green-100 text-green-800'
                : 'bg-red-100 text-red-800';
        },
    };
}
