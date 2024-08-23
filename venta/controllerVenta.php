<?php
session_start(); // Inicia la sesión para mantener el estado de inicio de sesión

// Verifica si el usuario está autenticado
if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    echo 'Debe iniciar sesión para crear una venta.';
    exit();
}

// Verifica si la solicitud es una petición POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include '../venta/Venta.php'; // Ruta correcta al archivo Venta.php
    $modelo = new Venta(); // Crear una instancia de la clase Venta

    // Recibir datos del formulario
    $nombre = isset($_POST['nombre']) ? $_POST['nombre'] : null;
    $fecha = isset($_POST['fecha']) ? $_POST['fecha'] : null;
    $producto = isset($_POST['producto']) ? $_POST['producto'] : null;
    $precio = isset($_POST['precio']) ? str_replace('.', '', $_POST['precio']) : null; // Elimina puntos para almacenar el valor numérico correctamente

    // Convertir fecha a formato Oracle
    $fecha = date('d-M-Y', strtotime($fecha));

    // Verificar si se recibieron todos los datos necesarios
    if ($nombre && $fecha && $producto && $precio) {
        // Obtener el nuevo ID para la venta
        $id = $modelo->obtenerNuevoId();
        if ($id === null) {
            echo "Error al obtener el nuevo ID.";
            exit();
        }

        echo "ID obtenido para la nueva venta: $id<br>"; // Muestra el ID antes de la inserción

        // Guardar la venta en la base de datos
        $guardado = $modelo->crearVenta($id, $nombre, $fecha, $producto, $precio);

        if ($guardado) {
            echo "Venta creada exitosamente con ID $id";
        } else {
            echo "Error al crear la venta.";
        }
    } else {
        echo "Por favor completa todos los campos.";
    }
} else {
    echo "Método de solicitud no permitido.";
}
?>
