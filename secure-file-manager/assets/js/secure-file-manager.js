jQuery(document).ready(function($) {
    if (typeof secure_file_manager_ajax === 'undefined' || typeof secure_file_manager_ajax.ajaxurl === 'undefined') {
        console.error('secure_file_manager_ajax o secure_file_manager_ajax.ajaxurl no están definidos.');
        return;
    }

    $('#secure-login-form').on('submit', function(e) {
        e.preventDefault();
        
        var username = $('#username').val();
        var password = $('#password').val();
        
        $('#secure-login-form').append('<div id="loading-indicator" style="text-align: center; margin-top: 10px;">Iniciando sesión...</div>');
        
        $.ajax({
            url: secure_file_manager_ajax.ajaxurl,
            method: 'POST',
            data: {
                action: 'secure_file_manager_login',
                username: username,
                password: password
            },
            success: function(response) {
                console.log('Response:', response);
                var result = JSON.parse(response);
                if (result.status === 'success') {
                    window.location.reload();
                } else {
                    alert(result.message);
                }
                $('#loading-indicator').remove();
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                alert('Ocurrió un error. Por favor, inténtalo de nuevo.');
                $('#loading-indicator').remove();
            }
        });
    });

    // Manejo de la descarga de archivos
    $('.file-download-link').on('click', function(e) {
        e.preventDefault();
        var fileUrl = $(this).attr('href');
        window.location.href = fileUrl;
    });
});
