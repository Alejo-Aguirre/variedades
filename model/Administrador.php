<?php
class Administrador {
    private $conexion;

    // Constantes para la conexión
    private $DB_USER = 'SARAY';
    private $DB_PASSWORD = 'root';
    private $DB_HOST = '//localhost/XE';

    function __construct() {
        // Conexión persistente a la base de datos Oracle
        $this->conexion = oci_pconnect($this->DB_USER, $this->DB_PASSWORD, $this->DB_HOST);
        if (!$this->conexion) {
            $e = oci_error();
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        }
    }

    public function autenticar($usuario, $clave) {
        // Consulta para buscar al administrador en la base de datos
        $consulta = "SELECT * FROM administrador WHERE usuario = :usuario AND contra = :clave";

        // Preparar y ejecutar la consulta
        $stmt = oci_parse($this->conexion, $consulta);
        oci_bind_by_name($stmt, ':usuario', $usuario);
        oci_bind_by_name($stmt, ':clave', $clave);
        oci_execute($stmt);

        // Verificar si se encontró al administrador
        if ($row = oci_fetch_array($stmt, OCI_ASSOC)) {
            return true;
        } else {
            return false;
        }
    }
}
?>
