<?php

function secure_file_manager_settings_page() {
    ?>
    <div class="wrap">
        <h1>Secure File Manager</h1>
        
        <!-- Navegación entre pestañas -->
        <h2 class="nav-tab-wrapper">
            <a href="?page=secure-file-manager&tab=files" class="nav-tab <?php echo $_GET['tab'] == 'files' || !isset($_GET['tab']) ? 'nav-tab-active' : ''; ?>">Archivos</a>
            <a href="?page=secure-file-manager&tab=users" class="nav-tab <?php echo $_GET['tab'] == 'users' ? 'nav-tab-active' : ''; ?>">Usuarios</a>
        </h2>

        <?php
        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'files';

        if ($tab == 'files') {
            // Sección de archivos
            ?>
            <!-- Subida de archivos -->
            <form method="post" enctype="multipart/form-data">
                <h2>Subir archivos cifrados</h2>
                <input type="file" name="secure_file" />
                <button type="submit" name="upload_file">Subir</button>
            </form>

            <?php
            if (isset($_POST['upload_file'])) {
                secure_file_manager_handle_upload();
            }

            // Listar archivos
            $files = secure_file_manager_list_files();
            if (!empty($files)) {
                echo '<h2>Archivos Subidos</h2><ul>';
                foreach ($files as $file) {
                    echo '<li>' . esc_html($file) . ' - <a href="?page=secure-file-manager&delete_file=' . urlencode($file) . '">Borrar</a></li>';
                }
                echo '</ul>';
            } else {
                echo '<p>No hay archivos subidos.</p>';
            }

            // Borrar archivo
            if (isset($_GET['delete_file'])) {
                $file_to_delete = sanitize_file_name($_GET['delete_file']);
                $filepath = wp_upload_dir()['path'] . '/' . $file_to_delete;
                if (file_exists($filepath)) {
                    unlink($filepath);
                    echo '<p>Archivo borrado: ' . esc_html($file_to_delete) . '</p>';
                }
            }
        } elseif ($tab == 'users') {
            // Sección de usuarios
            ?>
            <h2>Usuarios Creados</h2>

            <?php
            global $wpdb;
            $table_name = $wpdb->prefix . 'secure_file_users';
            $users = $wpdb->get_results("SELECT * FROM $table_name");

            if (!empty($users)) {
                echo '<ul>';
                foreach ($users as $user) {
                    echo '<li>' . esc_html($user->username) . ' - <a href="?page=secure-file-manager&tab=users&delete_user=' . urlencode($user->id) . '">Borrar</a></li>';
                }
                echo '</ul>';
            } else {
                echo '<p>No hay usuarios creados.</p>';
            }

            // Borrar usuario
            if (isset($_GET['delete_user'])) {
                $user_id = intval($_GET['delete_user']);
                $wpdb->delete($table_name, array('id' => $user_id));
                echo '<p>Usuario eliminado.</p>';
            }
            ?>

            <!-- Creación de usuarios -->
            <form method="post">
                <h2>Crear usuario</h2>
                <label for="new_username">Nombre de usuario</label>
                <input type="text" name="new_username" required />
                <label for="new_password">Contraseña</label>
                <input type="password" name="new_password" required />
                <button type="submit" name="create_user">Crear usuario</button>
            </form>

            <?php
            if (isset($_POST['create_user'])) {
                secure_file_manager_create_user(sanitize_text_field($_POST['new_username']), sanitize_text_field($_POST['new_password']));
            }
        }
        ?>

        <!-- Pie de página -->
        <div style="margin-top: 50px;">
            <p>Developed By:</p>
            <img src="<?php echo plugins_url('assets/images/mdevignlogo.png', dirname(__FILE__)); ?>" alt="Mdevign Logo" style="max-width: 150px;">
        </div>
    </div>
    <?php
}

function secure_file_manager_create_user($username, $password) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'secure_file_users';

    // Cifrar la contraseña
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Insertar usuario en la base de datos
    $wpdb->insert(
        $table_name,
        array(
            'username' => $username,
            'password' => $hashed_password
        )
    );

    if ($wpdb->insert_id) {
        echo '<p>Usuario creado: ' . esc_html($username) . '</p>';
    } else {
        echo '<p>Error al crear usuario: ' . $wpdb->last_error . '</p>';
    }
}

?>
