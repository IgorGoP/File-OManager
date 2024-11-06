<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../fom.php");
    exit();
}

require_once('../config/db_config.php');

$user_id = $_SESSION['user_id'];
$user_rol = $_SESSION['rol'];

// Inicializar variables
$nom_completo = '';
$email = '';
$idioma = '';
$avatar = '';
$rol = '';

// Obtener los detalles del usuario si no se ha enviado el formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $query = "SELECT nombre, email, idioma, avatar, nom_completo, rol FROM usuarios WHERE id = ?";
    $stmt = $db->prepare($query);
    if (!$stmt) {
        die('Error al preparar la consulta: ' . $db->error);
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($nombre, $email, $idioma, $avatar, $nom_completo, $rol);
    $stmt->fetch();
    $stmt->close();

    // Ruta del avatar
    $avatar_url = (!empty($avatar) && file_exists(__DIR__ . '/../public/avatars/' . $avatar))
        ? '/FileOManager/public/avatars/' . rawurlencode($avatar)
        : '/FileOManager/public/avatars/Avatar_Default_1.jpg';

} else {
    // Procesar el formulario de guardar cambios
    if (isset($_POST['action']) && $_POST['action'] === 'save_user_settings') {
        $nom_completo = $_POST['nom_completo'] ?? '';
        $email = $_POST['email'] ?? '';
        $rol = $_POST['rol'] ?? '';
        $language = $_POST['language'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            die('Error: Email no válido.');
        }

        $query = "UPDATE usuarios SET nom_completo = ?, email = ?, rol = ?, idioma = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        if (!$stmt) {
            die('Error al preparar la consulta de actualización: ' . $db->error);
        }
        $stmt->bind_param("ssssi", $nom_completo, $email, $rol, $language, $user_id);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $_SESSION['mensaje'] = 'Cambios guardados con éxito.';
        } else {
            $_SESSION['mensaje'] = 'No se realizaron cambios.';
        }
        $stmt->close();

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Incluir archivo de idioma
$lang = include("../languages/lang_{$idioma}.php");

// Archivos de idiomas disponibles
$language_files = array_map(function($file) {
    return str_replace(['lang_', '.php'], '', $file);
}, array_filter(scandir('../languages'), function($file) {
    return strpos($file, 'lang_') === 0;
}));

?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($idioma); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($lang['settings']); ?></title>
    <link rel="stylesheet" href="/FileOManager/backend/settings.css">
    <script src="/FileOManager/backend/settings.js" defer></script>
</head>
<body>
    <div class="header-container">
        <h1>Configuración</h1>
    </div>

    <?php if (isset($_SESSION['mensaje'])): ?>
        <p class="message"> <?php echo htmlspecialchars($_SESSION['mensaje'], ENT_QUOTES, 'UTF-8'); ?> </p>
        <?php unset($_SESSION['mensaje']); ?>
    <?php endif; ?>

    <div class="settings-content">
        <!-- Formulario de configuración -->
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="settings-section" id="userSettingsForm">
            <fieldset>
                <legend>Información del Usuario</legend>
                <label for="nom_completo">Nombre Completo:</label>
                <input type="text" id="nom_completo" name="nom_completo" value="<?php echo htmlspecialchars($nom_completo); ?>" readonly>

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" readonly>

                <label for="rol">Rol:</label>
                <input type="text" id="rol" name="rol" value="<?php echo htmlspecialchars($rol); ?>" readonly>

                <label for="language">Idioma:</label>
                <select id="language" name="language" disabled>
                    <?php foreach ($language_files as $language_code): ?>
                        <option value="<?php echo htmlspecialchars($language_code); ?>" <?php echo ($idioma == $language_code ? 'selected' : ''); ?>>
                            <?php echo ucfirst(htmlspecialchars($language_code)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="hidden" name="action" value="save_user_settings">
                <!-- Botón Editar y Guardar/Cancelar -->
                <button type="button" id="editButton">Editar</button>
                <button type="submit" id="saveButton" style="display:none;">Guardar</button>
                <button type="button" id="cancelButton" style="display:none;">Cancelar</button>
            </fieldset>
        </form>
    </div>
</body>
</html>

