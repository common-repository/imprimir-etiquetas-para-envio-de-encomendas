jQuery(document).ready(function($) {
    // Abre a modal ao clicar no ícone de ajuda
    $('#imprimir_etiquetas_help_link').on('click', function(event) {
        event.preventDefault();
        $('#imprimir_etiquetas_help_modal').fadeIn(); // Mostra a modal
    });

    // Fecha a modal ao clicar no botão de fechar (X)
    $('.imprimir-etiquetas-close').on('click', function() {
        $('#imprimir_etiquetas_help_modal').fadeOut(); // Fecha a modal
    });

    // Fecha a modal ao clicar fora do conteúdo
    $(window).on('click', function(event) {
        if ($(event.target).is('#imprimir_etiquetas_help_modal')) {
            $('#imprimir_etiquetas_help_modal').fadeOut();
        }
    });
});
