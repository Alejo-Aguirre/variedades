<?php
// authController.php
session_start();
require_once('../model/Administrador.php');

$response = array('success' => false, 'message' => 'Usuario o contraseña incorrectos.');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['check_session'])) {
        // Verificar si el usuario está logueado
        if (isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] === true) {
            $response['loggedIn'] = true;
            $response['userName'] = $_SESSION['username'];
        } else {
            $response['loggedIn'] = false;
        }
        echo json_encode($response);
        exit();
    }

    if (isset($_POST['logout'])) {
        // Cerrar sesión
        session_unset();
        session_destroy();
        echo 'logout_successful';
        exit();
    }

    if (isset($_POST['usuario']) && isset($_POST['clave']) && isset($_POST['tipo_cuenta'])) {
        $usuario = $_POST['usuario'];
        $clave = $_POST['clave'];
        $tipo_cuenta = $_POST['tipo_cuenta'];

        // Solo permitir autenticación para el tipo de cuenta 'admin'
        if ($tipo_cuenta !== 'admin') {
            $response['message'] = "Tipo de cuenta no válido.";
            echo json_encode($response);
            exit();
        }

        $user = new Administrador(); // Crear una instancia de Administrador

        $autenticado = $user->autenticar($usuario, $clave);

        if ($autenticado) {
            $_SESSION['loggedIn'] = true;
            $_SESSION['username'] = $usuario;
            $_SESSION['tipo_cuenta'] = $tipo_cuenta;

            $response['success'] = true;
            $response['username'] = $usuario;
            $response['tipo_cuenta'] = $tipo_cuenta;
            echo json_encode($response);
            exit();
        } else {
            $response['message'] = 'Usuario o contraseña incorrectos.';
            echo json_encode($response);
            exit();
        }
    } else {
        $response['message'] = "No se recibieron los datos completos del formulario.";
        echo json_encode($response);
        exit();
    }
}

echo json_encode($response);
?>
