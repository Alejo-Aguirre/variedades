<?php
class Compra {
    private $conn;

    public function __construct() {
        $DB_USER = 'SARAY';
        $DB_PASSWORD = 'root';
        $DB_HOST = '//localhost/XE';

        try {
            $this->conn = new PDO("oci:dbname=$DB_HOST;charset=UTF8", $DB_USER, $DB_PASSWORD);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "Conexión exitosa.<br>";
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    public function obtenerNuevoId() {
        try {
            // Llama al procedimiento almacenado que obtiene el nuevo ID
            $sql = "BEGIN obtener_nuevo_id1(:nuevo_id); END;";
            $stmt = $this->conn->prepare($sql);
    
            // Declara un parámetro de salida para el nuevo ID
            $nuevo_id = 0;
            $stmt->bindParam(':nuevo_id', $nuevo_id, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 10);
    
            // Ejecuta el procedimiento almacenado
            $stmt->execute();
    
            // El nuevo ID está en el parámetro de salida
            echo "Nuevo ID obtenido: $nuevo_id<br>";
            return $nuevo_id;
        } catch (Exception $e) {
            echo "Error al obtener el nuevo ID: " . $e->getMessage();
            return null;
        }
    }
    
    
    

    public function crearCompra($id, $nombre, $fecha, $producto, $precio) {
        try {
            $sql = "INSERT INTO compras (ID, Nombre, Fecha, Producto, Precio) VALUES (:id, :nombre, TO_DATE(:fecha, 'DD-MON-YYYY'), :producto, :precio)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
            $stmt->bindParam(':producto', $producto, PDO::PARAM_STR);
            $stmt->bindParam(':precio', $precio, PDO::PARAM_STR);

            $resultado = $stmt->execute();
            echo "Resultado de la inserción: " . ($resultado ? "éxito" : "fallo") . "<br>";
            return $resultado;
        } catch (PDOException $e) {
            echo "Error al crear la compra: " . $e->getMessage();
            return false;
        }
    }
}
?>

