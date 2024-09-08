<?php

// Función para mostrar el formulario de login y los archivos
function render_secure_login() {
    if (isset($_SESSION['secure_file_user'])) {
        return '
        <div id="loading-animation" style="text-align: center; opacity: 0; transition: opacity 1s ease-in-out;">
            <p id="developed-by-text" style="font-size: 18px; margin-bottom: 10px; opacity: 0; transition: opacity 1s ease-in-out;">Desarrollado por:</p>
            <img src="' . plugins_url('assets/images/mdevignlogo.png', dirname(__FILE__)) . '" alt="Mdevign Logo" style="max-width: 250px; opacity: 0; transition: opacity 1s ease-in-out;">
            <div id="decrypting-text" style="margin-top: 20px; font-size: 18px; opacity: 0; transition: opacity 1s ease-in-out;">Desencriptando archivos...</div>
            <div class="progress-bar-container" style="width: 80%; margin: 20px auto;">
                <div class="progress-bar" style="width: 0%; height: 20px; background-color: #f05822;"></div>
            </div>
        </div>
        <div id="file-list" style="display: none; text-align: center; margin-top: 50px; opacity: 0; transition: opacity 1s ease-in-out;">
            <ul style="list-style: none; padding: 0;">
                ' . secure_file_manager_generate_file_list() . '
            </ul>
            <a href="' . esc_url(admin_url('admin-ajax.php?action=secure_file_manager_logout')) . '" class="logout-button" style="display: inline-block; padding: 10px 20px; background-color: #f05822; color: #fff; text-decoration: none; border-radius: 50px; margin-top: 20px;">Cerrar sesión</a>
        </div>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var progressBar = document.querySelector(".progress-bar");
                var loadingAnimation = document.getElementById("loading-animation");
                var fileList = document.getElementById("file-list");
                var developedByText = document.getElementById("developed-by-text");
                var decryptingText = document.getElementById("decrypting-text");
                var logo = document.querySelector("img[alt=\'Mdevign Logo\']");
                
                // Mostrar la animación de carga con una transición suave
                loadingAnimation.style.opacity = 1;
                
                // Mostrar textos y logo con transiciones suaves
                setTimeout(function() {
                    developedByText.style.opacity = 1;
                    logo.style.opacity = 1;
                    decryptingText.style.opacity = 1;
                }, 500); // Retraso para una mejor sincronización visual

                var width = 0;
                var interval = setInterval(function() {
                    if (width >= 100) {
                        clearInterval(interval);
                        loadingAnimation.style.opacity = 0;
                        setTimeout(function() {
                            fileList.style.display = "block";
                            fileList.style.opacity = 1;
                        }, 1000); // Espera 1 segundo antes de mostrar la lista de archivos
                    } else {
                        width++;
                        progressBar.style.width = width + "%";
                    }
                }, 30); // Ajusta la velocidad de la animación según prefieras
            });
        </script>
        ';
    } else {
        return '
        <form id="secure-login-form" style="max-width: 400px; margin: 0 auto; background-color: #0c356a; padding: 30px; border-radius: 50px;">
            <label for="username" style="color: #fff; font-size: 18px; display: block; margin-bottom: 10px;">   Usuario</label>
            <input type="text" id="username" name="username" required style="width: 100%; padding: 10px; margin-bottom: 20px; border: none; border-radius: 50px; background-color: #fff; color: #333;">
            <label for="password" style="color: #fff; font-size: 18px; display: block; margin-bottom: 10px;">   Contraseña</label>
            <input type="password" id="password" name="password" required style="width: 100%; padding: 10px; margin-bottom: 20px; border: none; border-radius: 50px; background-color: #fff; color: #333;">
            <button type="submit" style="width: 100%; padding: 10px; background-color: #f05822; color: #fff; border: none; border-radius: 50px; font-size: 18px;">Ingresar</button>
        </form>
        ';
    }
}

// Función para manejar el login personalizado
function secure_file_manager_login() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'secure_file_users';

    $username = sanitize_text_field($_POST['username']);
    $password = sanitize_text_field($_POST['password']);

    $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE username = %s", $username));

    if ($user && password_verify($password, $user->password)) {
        // Iniciar sesión
        $_SESSION['secure_file_user'] = $username;
        echo json_encode(array('status' => 'success', 'message' => 'Login successful'));
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Usuario o contraseña incorrectos'));
    }
    wp_die();
}
add_action('wp_ajax_secure_file_manager_login', 'secure_file_manager_login');
add_action('wp_ajax_nopriv_secure_file_manager_login', 'secure_file_manager_login');

// Función para manejar la subida de archivos cifrados
function secure_file_manager_handle_upload() {
    if (isset($_FILES['secure_file'])) {
        $file = $_FILES['secure_file'];
        $key = 'your-encryption-key'; // Cambia esta clave por una segura

        // Asegúrate de que el nombre del archivo no tenga caracteres no válidos
        $filename = sanitize_file_name($file['name']);
        $filepath = wp_upload_dir()['path'] . '/' . $filename . '.enc';

        $data = file_get_contents($file['tmp_name']);
        $iv = random_bytes(16);
        $encrypted_data = openssl_encrypt($data, 'AES-128-CBC', $key, 0, $iv);

        file_put_contents($filepath, $iv . $encrypted_data);
    }
}

// Función para listar los archivos
function secure_file_manager_list_files() {
    $files = glob(wp_upload_dir()['path'] . '/*.enc');
    return array_map('basename', $files);
}

// Función para generar la lista de archivos con estilo
function secure_file_manager_generate_file_list() {
    $files = secure_file_manager_list_files();
    $output = '';

    if (!empty($files)) {
        foreach ($files as $file) {
            $output .= '<li style="padding: 10px; margin-bottom: 10px; background-color: #0c356a; border-radius: 50px;">
                <a href="' . esc_url(admin_url('admin-ajax.php?action=download_secure_file&file=' . urlencode($file))) . '" style="color: #fff; text-decoration: none;">' . esc_html($file) . '</a>
            </li>';
        }
    } else {
        $output .= '<li style="color: #fff;">No hay archivos disponibles.</li>';
    }

    return $output;
}

// Función para descargar y descifrar archivos
function secure_file_manager_download_file() {
    if (isset($_GET['file']) && isset($_SESSION['secure_file_user'])) {
        $filename = sanitize_file_name($_GET['file']);
        $filepath = wp_upload_dir()['path'] . '/' . $filename;

        if (file_exists($filepath)) {
            $key = 'your-encryption-key'; // Usa la misma clave que para el cifrado
            $encrypted_data = file_get_contents($filepath);
            $iv = substr($encrypted_data, 0, 16);
            $data = substr($encrypted_data, 16);
            $decrypted_data = openssl_decrypt($data, 'AES-128-CBC', $key, 0, $iv);

            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filename, '.enc') . '"');
            echo $decrypted_data;
            exit;
        }
    }
}
add_action('wp_ajax_download_secure_file', 'secure_file_manager_download_file');
add_action('wp_ajax_nopriv_download_secure_file', 'secure_file_manager_download_file');

// Función para cerrar sesión
function secure_file_manager_logout() {
    session_destroy();
    wp_redirect(home_url()); // Redirigir a la página de inicio o donde desees
    exit;
}
add_action('wp_ajax_secure_file_manager_logout', 'secure_file_manager_logout');
add_action('wp_ajax_nopriv_secure_file_manager_logout', 'secure_file_manager_logout');

?>
