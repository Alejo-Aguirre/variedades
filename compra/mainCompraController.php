<?php

class MainComprasController {
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

    function getCompras($ano = '', $mes = '') {
        $query = "BEGIN obtener_compras(:compras_cursor, :ano, :mes); END;";
        $stmt = oci_parse($this->conexion, $query);

        $cursor = oci_new_cursor($this->conexion);
        oci_bind_by_name($stmt, ":compras_cursor", $cursor, -1, OCI_B_CURSOR);
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

        $compras = [];
        while ($row = oci_fetch_assoc($cursor)) {
            $compras[] = $row;
        }

        oci_free_statement($stmt);
        oci_free_statement($cursor);

        return $compras;
    }

    function getAnosDisponibles() {
        $query = "SELECT DISTINCT TO_CHAR(FECHA, 'YYYY') AS ANIO FROM compras ORDER BY ANIO DESC";
        $stmt = oci_parse($this->conexion, $query);
        oci_execute($stmt);

        $anios = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $anios[] = $row['ANIO'];
        }

        oci_free_statement($stmt);

        return $anios;
    }

    public function editarCompra($id, $nombre, $fecha, $producto, $precio) {

        $query = "BEGIN editar_compra_proc(:id, :nombre, :fecha, :producto, :precio); END;";
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

    function getCompraById($id) {
        $query = "SELECT * FROM compras WHERE ID = :id";
        $stmt = oci_parse($this->conexion, $query);
        oci_bind_by_name($stmt, ':id', $id);

        if (!oci_execute($stmt)) {
            $e = oci_error($stmt);
            echo json_encode(["status" => "error", "message" => htmlentities($e['message'])]);
            return false;
        }

        $compra = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $compra;
    }

    function deleteCompra($id) {
        $query = "BEGIN eliminar_compra(:id); END;";
        $stmt = oci_parse($this->conexion, $query);
        oci_bind_by_name($stmt, ':id', $id);
        
        if (!oci_execute($stmt)) {
            $e = oci_error($stmt);
            echo json_encode(["status" => "error", "message" => htmlentities($e['message'])]);
            return false;
        }

        oci_free_statement($stmt);

        // Obtener la lista actualizada de compras
        $compras = $this->getCompras();

        echo json_encode(["status" => "success", "compras" => $compras]);
        return true;
    }

    function closeConnection() {
        oci_close($this->conexion);
    }
}

// Crear una instancia de la clase MainComprasController
$controller = new MainComprasController();

// Verificar si se ha enviado el ID para eliminar una compra
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $deleted = $controller->deleteCompra($_POST['delete_id']);
    if (!$deleted) {
        echo json_encode(["status" => "error", "message" => "Error al eliminar la compra."]);
    }
    exit();
}

// Verificar si se ha enviado el formulario para editar una compra
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $edited = $controller->editarCompra(
        $_POST['id'],
        $_POST['nombre'],
        $_POST['fecha'],
        $_POST['producto'],
        $_POST['precio']
    );
    if ($edited) {
        // Obtener la lista de compras actualizada
        $compras = $controller->getCompras();
        echo json_encode(["status" => "success", "compras" => $compras]);
        exit();
    } else {
        // Manejo de error
        echo json_encode(["status" => "error", "message" => "Error al editar la compra."]);
    }
}

// Verificar si se ha enviado el ID para obtener una compra específica
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    $compra = $controller->getCompraById($_GET['id']);
    if ($compra) {
        echo json_encode(["status" => "success", "compra" => $compra]);
    } else {
        echo json_encode(["status" => "error", "message" => "No se encontró la compra."]);
    }
    exit();
}

// Verificar si se han enviado los parámetros de año o mes para filtrar las compras
$ano = isset($_GET['ano']) ? $_GET['ano'] : '';
$mes = isset($_GET['mes']) ? $_GET['mes'] : '';

// Obtener la lista de compras con los filtros aplicados
$compras = $controller->getCompras($ano, $mes);

// Obtener los años disponibles
$anios = $controller->getAnosDisponibles();

// Cerrar la conexión
$controller->closeConnection();

// Enviar los datos en formato JSON
/*echo json_encode([
    'status' => 'success',
    'compras' => $compras,
    'anios' => $anios
]);
*/

// Incluir el archivo HTML que muestra la tabla de compras
include 'MainCompra.html';
?>
