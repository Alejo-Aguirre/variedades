<?php
// Configuración de conexión a la base de datos
$host = 'localhost';
$port = '1521';  // Puerto por defecto de Oracle
$sid = 'ORCL';   // SID de tu base de datos Oracle
$username = 'Saray';  // Usuario
$password = 'root';  // Contraseña

// Cadena de conexión
$connectionString = "oci:host=$host;port=$port;dbname=$sid";

// Crear una instancia de PDO
try {
    $pdo = new PDO($connectionString, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'getSales':
        getSales();
        break;

    case 'getSaleById':
        getSaleById();
        break;

    case 'deleteSale':
        deleteSale();
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}

// Obtener todas las ventas de un mes específico
function getSales() {
    global $pdo;

    $month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
    $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

    $query = "BEGIN obtener_ventas(:p_mes, :p_anio, :p_cursor); END;";
    $stmt = oci_parse($pdo, $query);

    $cursor = oci_new_cursor($pdo);
    oci_bind_by_name($stmt, ":p_mes", $month, -1, SQLT_INT);
    oci_bind_by_name($stmt, ":p_anio", $year, -1, SQLT_INT);
    oci_bind_by_name($stmt, ":p_cursor", $cursor, -1, OCI_B_CURSOR);

    if (!oci_execute($stmt)) {
        $e = oci_error($stmt);
        echo json_encode(['error' => 'Error al ejecutar el procedimiento: ' . htmlentities($e['message'])]);
        return;
    }

    if (!oci_execute($cursor)) {
        $e = oci_error($cursor);
        echo json_encode(['error' => 'Error al ejecutar el cursor: ' . htmlentities($e['message'])]);
        return;
    }

    $sales = [];
    while ($row = oci_fetch_assoc($cursor)) {
        $sales[] = $row;
    }

    oci_free_statement($stmt);
    oci_free_statement($cursor);

    header('Content-Type: application/json');
    echo json_encode($sales);
}

// Obtener una venta por ID
function getSaleById() {
    global $pdo;

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    $query = "BEGIN obtener_venta_por_id(:p_id, :p_cursor); END;";
    $stmt = oci_parse($pdo, $query);

    $cursor = oci_new_cursor($pdo);
    oci_bind_by_name($stmt, ":p_id", $id, -1, SQLT_INT);
    oci_bind_by_name($stmt, ":p_cursor", $cursor, -1, OCI_B_CURSOR);

    if (!oci_execute($stmt)) {
        $e = oci_error($stmt);
        echo json_encode(['error' => 'Error al ejecutar el procedimiento: ' . htmlentities($e['message'])]);
        return;
    }

    if (!oci_execute($cursor)) {
        $e = oci_error($cursor);
        echo json_encode(['error' => 'Error al ejecutar el cursor: ' . htmlentities($e['message'])]);
        return;
    }

    $sale = oci_fetch_assoc($cursor);

    oci_free_statement($stmt);
    oci_free_statement($cursor);

    header('Content-Type: application/json');
    echo json_encode($sale);
}

// Eliminar una venta por ID
function deleteSale() {
    global $pdo;

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    $query = "DELETE FROM ventas WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        $e = $stmt->errorInfo();
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Error al eliminar la venta: ' . htmlentities($e[2])]);
    }
}
?>
