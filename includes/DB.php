<?php

if (!isset($_SESSION)) {
    session_start();
}

/**
 * @author Isidoro Cornelio
 */
class DB {

    private $conn = false;

    /**
     * Constructor para la apertura de conexion
     */
    public function __construct($host = null, $username = null, $passwd = null, $dbname = null, $port = null, $socket = null) {

        // Conexion
        $this->conn = new mysqli(
            $host,
            $username,
            $passwd,
            $dbname,
            $port,
            $socket
        );

        if ($this->conn->connect_errno) {
            printf("Failed connection: %s\n", $this->conn->connect_error);
            exit();
        }

        // Codificacion
        if (!$this->conn->set_charset('utf8')) {
            printf('Error loading utf8 character set: %s\n', $this->conn->error);
            exit();
        }

        // Auto-commit deshabilitado
        $this->conn->autocommit(false);
    }

    /**
     * Destructor para manejar el cierre de la conexion
     */
    public function __destruct() {
        try {
            if ($this->conn) {
                $this->conn->close();

                $this->conn = false;
            }
        } catch (\Exception $e) {
            //
        }
    }

    /**
     * Cierra la conexion actual
     */
    public function closeConnection() {
        $this->conn->close();
    }

    /**
     * Obtiene la conexion actual
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Asigna la base de datos a la conexion actual
     */
    public function setDataBase($dbname) {
        if (!empty($dbname)) {
            $this->conn->select_db($dbname);
        }
    }

    /**
     * Conversion de result set a array
     */
    public function toArray($result) {
        if (is_bool($result)) {
            return $result;
        }

        $rows = [];

        while($row = $result->fetch_array(MYSQLI_ASSOC)) {
            array_push($rows, $row);
        }

        return $rows;
    }

    /**
     * Genera una cadena limpia entre comillas dobles
     */
    public function getQuotesStr($str) {
        return '"' . htmlspecialchars($str, ENT_COMPAT) . '"';
    }

    /**
     * Manejador de errores de consulta
     */
    private function queryErrorHandler($result) {
        if (!$result) {
            $err = $this->conn->error;

            throw new \mysqli_sql_exception($err);
        }

        return $result;
    }

    /**
     * Ejecuta una consulta de solo lectura con la capacidad de devolver un arreglo asociativo
     */
    public function select($sql, $to_array = true) {
        // Limpieza
        $sql = preg_replace('!\s+!', ' ', str_replace(["\r", "\n", "\r\n"], '', $sql));

        // Transaccion de solo lectura
        $this->conn->begin_transaction(MYSQLI_TRANS_START_READ_ONLY);

        // Ejecucion de la consulta
        $result = $this->queryErrorHandler($this->conn->query($sql));

        // Se aplica la trasaccion y cierra
        $this->conn->commit();

        // Conversion a array
        return $to_array ? $this->toArray($result) : $result;
    }

    /**
     * Ejecuta una consulta de insercion mapeada por un arreglo asociativo
     */
    public function insert($table, $values_map) {
        // Se inicia una nueva transaccion de lectura y escritura
        $this->conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

        // Mapeado de columnas y valores
        $cols   = array_keys($values_map);
        $values = array_values($values_map);

        array_walk($cols, function (&$c) {
            $c = "`$c`";
        });

        array_walk($values, function (&$v) {
            $v = gettype($v) === 'NULL' ? 'null' : (gettype($v) === 'string' ? $this->getQuotesStr($v) : $v);
        });

        // Ejecucion de consulta
        $result = $this->queryErrorHandler($this->conn->query("INSERT INTO `$table` (" . join(', ', $cols)
            . ") VALUES (" . join(', ', $values) . ")"));

        // Se valida el valor obtenido de la insercion almacenando el ID resultante
        $new_id = $result ? $this->conn->insert_id : $result;

        // Persistencia
        $this->conn->commit();

        // Se devuelve el ID
        return $new_id;
    }



    /**
     * Ejecuta una transaccion de lectura y escritura, de una consulta directa, con la capacidad de devolver un arreglo asociativo
     */
    public function sqlPure($sql, $to_array = true) {
        $data = null;

        try {
            // Limpieza
            $sql = preg_replace('!\s+!', ' ', str_replace(["\r", "\n", "\r\n"], '', $sql));

            // Se inicia una nueva transaccion de lectura y escritura
            $this->conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
       
            // Ejecucion de la consulta
            $result = $this->conn->query($sql);
            /*echo $sql;
            die();*/
            // Se aplica la trasaccion y cierra
            $this->conn->commit();

           // Conversion a array
            $data = $to_array ? $this->toArray($result) : $result;
        } catch (\Exception $e) {
            #Utils::log($e->getMessage());

            $data = false;
        }

        return $data;
    }

    /**
     * Ejecuta una consulta de actualizacion (UPDATE)
     */
    public function update($table, $values_map, $where) {
        // Where es obligatorio
        $where = trim($where);

        if (empty($where)) {
            return false;
        }

        // Se inicia una nueva transaccion de lectura y escritura
        $this->conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

        // Mapeado de columnas y valores
        $set_values = [];

        foreach($values_map as $k => $v) {
            array_push($set_values, "`$k` = " . (gettype($v) === 'array' ? end($v) : (gettype($v) === 'string' ? $this->getQuotesStr($v) : $v)));
        }
/*echo ("UPDATE `$table` SET " . join(', ', $set_values) . " WHERE " . $where);
die();*/
        // Ejecucion de consulta
        $result = $this->queryErrorHandler($this->conn->query("UPDATE `$table` SET " . join(', ', $set_values) . " WHERE " . $where));

        // Se valida el numero de filas afectadas
        $affected_rows = $result ? $this->conn->affected_rows : $result;

        // Persistencia
        $this->conn->commit();

        // Se devuelve el numero de filas afectadas
        return $affected_rows;
    }

    /**
     * Elimina un registro especifico por su PK sobre una tabla unica
     */
    public function delete($table, $pk, $id) {
        $result = null;

        try {
            // Se inicia una nueva transaccion de lectura y escritura
            $this->conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

            // Ejecucion de consulta
            $result = $this->conn->query("DELETE FROM `$table` WHERE `$pk` = $id AND `$pk` IS NOT NULL AND CAST($id AS CHAR) != ''");

            // Se valida el numero de filas afectadas
            $result = $result ? $this->conn->affected_rows > 0 : $result;

            // Persistencia
            $this->conn->commit();
        } catch (\Exception $e) {
            #Utils::log($e->getMessage());
            $this->conn->rollback();

            $result = false;
        }

        return $result;
    }

    /**
     * Genera na estructura de llave - valor principalmente para uso en combos
     */
    public function getCombo($table, $key, $description, $where = null, $group = null, $order = null, $extra_cols = null) {
        $extra_cols = $extra_cols ? ", $extra_cols" : '';

        return $this->select("SELECT $key AS `key`, IF($description IS NULL OR TRIM($description) = '', $key, $description) AS `description`$extra_cols FROM $table"
            . (empty($where) ? '' : " WHERE $where") . ($group ? " GROUP BY $group" : '') . ' ORDER BY ' . (empty($order) ? '`description` ASC' : $order));
    }

    /**
     * Consulta abreviada de un SELECT
     */
    public function get($table, $where, $columns = '*', $to_array = true) {
        return $this->select("SELECT $columns FROM $table" . (empty($where) ? '' : " WHERE $where"), $to_array);
    }

    /**
     * Devuelve un valor entero con el numero de filas encontradas
     */
    public function count($table, $where = null) {
        return (int) $this->select("SELECT COUNT(1) AS count FROM $table" . (empty($where) ? '' : " WHERE $where"))[0]['count'];
    }

        /**
     * Ejecuta una consulta de insercion mapeada por un arreglo asociativo
     */
    public function insertHtml($table, $values_map) {
        // Se inicia una nueva transaccion de lectura y escritura
        $this->conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

        // Mapeado de columnas y valores
        $cols   = array_keys($values_map);
        $values = array_values($values_map);

        array_walk($cols, function (&$c) {
            $c = "`$c`";
        });

       array_walk($values, function (&$v) {
            $v = gettype($v) === 'NULL' ? 'null' : '"' . $v. '"';
        });

        /*echo "INSERT INTO `$table` (" . join(', ', $cols)
        . ") VALUES (" . join(', ', $values) . ")";
        die();*/
        // Ejecucion de consulta
        $result = $this->queryErrorHandler($this->conn->query("INSERT INTO `$table` (" . join(', ', $cols)
            . ") VALUES (" . join(', ', $values) . ")"));

        // Se valida el valor obtenido de la insercion almacenando el ID resultante
        $new_id = $result ? $this->conn->insert_id : $result;

        // Persistencia
        $this->conn->commit();

        // Se devuelve el ID
        return $new_id;
    }

        /**
     * Ejecuta una consulta de actualizacion (UPDATE)
     */
    public function updateHtml($table, $values_map, $where) {
        // Where es obligatorio
        $where = trim($where);

        if (empty($where)) {
            return false;
        }

        // Se inicia una nueva transaccion de lectura y escritura
        $this->conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

        // Mapeado de columnas y valores
        $set_values = [];

        foreach($values_map as $k => $v) {
            array_push($set_values, "`$k` = " . (gettype($v) === 'array' ? end($v) : '"' . $v. '"'));
        }
/*echo ("UPDATE `$table` SET " . join(', ', $set_values) . " WHERE " . $where);
die();*/
        // Ejecucion de consulta
        $result = $this->queryErrorHandler($this->conn->query("UPDATE `$table` SET " . join(', ', $set_values) . " WHERE " . $where));

        // Se valida el numero de filas afectadas
        $affected_rows = $result ? $this->conn->affected_rows : $result;

        // Persistencia
        $this->conn->commit();

        // Se devuelve el numero de filas afectadas
        return $affected_rows;
    }

}
