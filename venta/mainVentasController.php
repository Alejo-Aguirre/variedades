<?php

class MainVentasController {
    private $conexion;

    // Constantes para la conexión
    private $DB_USER = 'SARAY';
    private $DB_PASSWORD = 'root';
    private $DB_HOST = 'localhost/XE';

    function __construct() {
        // Conexión persistente a la base de datos Oracle
        $this->conexion = oci_pconnect($this->DB_USER, $this->DB_PASSWORD, $this->DB_HOST);
        if (!$this->conexion) {
            $e = oci_error();
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        }
    }

    function getVentas($ano = '', $mes = '') {
        $query = "BEGIN obtener_ventas1(:ventas_cursor, :ano, :mes); END;";
        $stmt = oci_parse($this->conexion, $query);

        $cursor = oci_new_cursor($this->conexion);
        oci_bind_by_name($stmt, ":ventas_cursor", $cursor, -1, OCI_B_CURSOR);
        oci_bind_by_name($stmt, ":ano", $ano);
        oci_bind_by_name($stmt, ":mes", $mes);

        if (!oci_execute($stmt)) {
            $e = oci_error($stmt);
            echo json_encode(["status" => "error", "message" => htmlentities($e['message'])]);
            return [];
        }

        if (!oci_execute($cursor)) {
            $e = oci_error($cursor);
            echo json_encode(["status" => "error", "message" => htmlentities($e['message'])]);
            return [];
        }

        $ventas = [];
        while ($row = oci_fetch_assoc($cursor)) {
            $ventas[] = $row;
        }

        oci_free_statement($stmt);
        oci_free_statement($cursor);

        return $ventas;
    }

    function getAnosDisponibles() {
        $query = "SELECT DISTINCT TO_CHAR(FECHA, 'YYYY') AS ANIO FROM ventas ORDER BY ANIO DESC";
        $stmt = oci_parse($this->conexion, $query);
        oci_execute($stmt);

        $anios = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $anios[] = $row['ANIO'];
        }

        oci_free_statement($stmt);

        return $anios;
    }

    public function editarVenta($id, $nombre, $fecha, $producto, $precio) {

        $fecha_formateada = DateTime::createFromFormat('d/m/Y', $fecha)->format('Y-m-d');

        $query = "BEGIN editar_venta_proc(:id, :nombre, :fecha, :producto, :precio); END;";
        $stmt = oci_parse($this->conexion, $query);

        oci_bind_by_name($stmt, ':id', $id);
        oci_bind_by_name($stmt, ':nombre', $nombre);
        oci_bind_by_name($stmt, ':fecha', $fecha);
        oci_bind_by_name($stmt, ':producto', $producto);
        oci_bind_by_name($stmt, ':precio', $precio);

        $resultado = oci_execute($stmt);

        if (!$resultado) {
            $e = oci_error($stmt);
            echo json_encode(["status" => "error", "message" => htmlentities($e['message'])]);
            return false;
        }

        oci_free_statement($stmt);

        return true;
    }

    function getVentaById($id) {
        $query = "SELECT * FROM ventas WHERE ID = :id";
        $stmt = oci_parse($this->conexion, $query);
        oci_bind_by_name($stmt, ':id', $id);

        if (!oci_execute($stmt)) {
            $e = oci_error($stmt);
            echo json_encode(["status" => "error", "message" => htmlentities($e['message'])]);
            return false;
        }

        $venta = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $venta;
    }

    function deleteVenta($id) {
        $query = "BEGIN eliminar_venta(:id); END;";
        $stmt = oci_parse($this->conexion, $query);
        oci_bind_by_name($stmt, ':id', $id);
        
        if (!oci_execute($stmt)) {
            $e = oci_error($stmt);
            echo json_encode(["status" => "error", "message" => htmlentities($e['message'])]);
            return false;
        }

        oci_free_statement($stmt);

        // Obtener la lista actualizada de ventas
        $ventas = $this->getVentas();

        echo json_encode(["status" => "success", "ventas" => $ventas]);
        return true;
    }

    function closeConnection() {
        oci_close($this->conexion);
    }
}

// Crear una instancia de la clase MainVentasController
$controller = new MainVentasController();

// Verificar si se ha enviado el ID para eliminar una venta
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $deleted = $controller->deleteVenta($_POST['delete_id']);
    if (!$deleted) {
        echo json_encode(["status" => "error", "message" => "Error al eliminar la venta."]);
    }
    exit();
}

// Verificar si se ha enviado el formulario para editar una venta
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $edited = $controller->editarVenta(
        $_POST['id'],
        $_POST['nombre'],
        $_POST['fecha'],
        $_POST['producto'],
        $_POST['precio']
    );
    if ($edited) {
        // Obtener la lista de ventas actualizada
        $ventas = $controller->getVentas();
        echo json_encode(["status" => "success", "ventas" => $ventas]);
        exit();
    } else {
        // Manejo de error
        echo json_encode(["status" => "error", "message" => "Error al editar la venta."]);
    }
}

// Verificar si se ha enviado el ID para obtener una venta específica
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    $venta = $controller->getVentaById($_GET['id']);
    if ($venta) {
        echo json_encode(["status" => "success", "venta" => $venta]);
    } else {
        echo json_encode(["status" => "error", "message" => "No se encontró la venta."]);
    }
    exit();
}

// Verificar si se han enviado los parámetros de año o mes para filtrar las ventas
$ano = isset($_GET['ano']) ? $_GET['ano'] : '';
$mes = isset($_GET['mes']) ? $_GET['mes'] : '';

// Obtener la lista de ventas con los filtros aplicados
$ventas = $controller->getVentas($ano, $mes);

// Obtener los años disponibles
$anios = $controller->getAnosDisponibles();

// Cerrar la conexión
$controller->closeConnection();

// Enviar los datos en formato JSON
/*echo json_encode([
    'status' => 'success',
    'ventas' => $ventas,
    'anios' => $anios
]);
*/
// Incluir el archivo HTML que muestra la tabla de ventas
include 'MainVenta1.html';
?>
