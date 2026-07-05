(function (window, L) {
    const labels = {
        PHARMACY: 'Pharmacie',
        HOSPITAL: 'Hopital',
        CLINIC: 'Clinique',
        DOCTOR: 'Docteur / centre medical',
        DENTIST: 'Dentiste',
    };

    function escapeHtml(value) {
        if (value === null || value === undefined) return '';
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function labelFr(value) {
        return value ? (labels[String(value).toUpperCase()] || value) : '';
    }

    function pinIcon(color) {
        const safeColor = escapeHtml(color || window.SANTE_CARTE_CONFIG.defaultColor);
        const svg = `<svg class="pin" width="17" height="24" viewBox="0 0 24 32">
            <path d="M12 0C5.4 0 0 5.4 0 12c0 8.4 12 20 12 20s12-11.6 12-20C24 5.4 18.6 0 12 0z" fill="${safeColor}"/>
            <circle cx="12" cy="12" r="4.5" fill="#fff"/></svg>`;

        return L.divIcon({
            html: svg,
            className: '',
            iconSize: [17, 24],
            iconAnchor: [8.5, 24],
            popupAnchor: [0, -23],
        });
    }

    window.SanteCarte = window.SanteCarte || {};
    window.SanteCarte.utils = { escapeHtml, labelFr, pinIcon };
})(window, L);
