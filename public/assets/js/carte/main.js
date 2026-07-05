(function (window, $) {
    const SanteCarte = window.SanteCarte;

    $(function () {
        SanteCarte.core.initLayoutEvents();
        SanteCarte.filters.initEvents();
        SanteCarte.routing.initEvents();
        SanteCarte.spatialAnalysis.initEvents();
        SanteCarte.decision.initEvents();
        $('#action-statistics').on('click', function () {
            const modalElement = document.getElementById('statistiquesModal');
            if (!modalElement || typeof bootstrap === 'undefined') {
                $('#info-panel').html('Le module statistiques n est pas disponible.').removeClass('hidden');
                return;
            }

            bootstrap.Modal.getOrCreateInstance(modalElement).show();
        });
        $('#action-reset-all').on('click', function () {
            SanteCarte.filters.reset();
            SanteCarte.routing.reset();
            SanteCarte.spatialAnalysis.reset();
            SanteCarte.decision.reset();
            SanteCarte.core.map.setView([-18.8792, 47.5079], 12.5);
            $('#info-panel').addClass('hidden').empty();
            $('#action-panel button').removeClass('active');
        });

        $.when(
            SanteCarte.core.loadArrondissements(),
            SanteCarte.core.loadTypes(),
            SanteCarte.filters.loadArrondissementsFilter()
        ).then(SanteCarte.core.loadEtablissements)
            .fail(function () { console.error('Erreur lors du chargement des donnees cartographiques'); })
            .always(function () { $('#loader').addClass('hide'); });
    });
})(window, jQuery);
