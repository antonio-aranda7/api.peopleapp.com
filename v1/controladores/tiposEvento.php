<?php

class tiposEvento
{
    const NOMBRE_TABLA = "tipoEvento";
    const ID_TIPOSEVENTO = "idtipoEvento";
    const DECRIPCION = "descripcion";

    const CODIGO_EXITO = 1;
    const ESTADO_EXITO = 1;
    const ESTADO_ERROR = 2;
    const ESTADO_ERROR_BD = 3;
    const ESTADO_ERROR_PARAMETROS = 4;
    const ESTADO_NO_ENCONTRADO = 5;

    public static function get($peticion)
    {
        $idUsuario = usuarios::autorizar();

        if (empty($peticion[0]))
            return self::obtenerTiposEvento($idUsuario);
        else
            return self::obtenerTiposEvento($idUsuario, $peticion[0]);

    }

    public static function post($peticion)
    {
        $idUsuario = usuarios::autorizar();

        $body = file_get_contents('php://input');
        $TiposEvento = json_decode($body);

        $idTiposEventos = tiposEventos::crear($idUsuario, $tiposEventos);

        http_response_code(201);
        return [
            "estado" => self::CODIGO_EXITO,
            "mensaje" => "TipoEventos creado",
            "id" => $idTiposEventos
        ];

    }

    public static function put($peticion)
    {
        $idUsuario = usuarios::autorizar();

        if (!empty($peticion[0])) {
            $body = file_get_contents('php://input');
            $TiposEvento = json_decode($body);

            if (self::actualizar($idUsuario, $TiposEvento, $peticion[0]) > 0) {
                http_response_code(200);
                return [
                    "estado" => self::CODIGO_EXITO,
                    "mensaje" => "Registro actualizado correctamente"
                ];
            } else {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                    "El TiposEvento al que intentas acceder no existe", 404);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_ERROR_PARAMETROS, "Falta id", 422);
        }
    }

    public static function delete($peticion)
    {
        $idUsuario = usuarios::autorizar();

        if (!empty($peticion[0])) {
            if (self::eliminar($idUsuario, $peticion[0]) > 0) {
                http_response_code(200);
                return [
                    "estado" => self::CODIGO_EXITO,
                    "mensaje" => "Registro eliminado correctamente"
                ];
            } else {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                    "El TiposEvento al que intentas acceder no existe", 404);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_ERROR_PARAMETROS, "Falta id", 422);
        }

    }

    /**
     * Obtiene la colecci�n de TiposEvento o un solo TiposEvento indicado por el identificador
     * @param int $idUsuario identificador del usuario
     * @param null $idTiposEvento identificador del TiposEvento (Opcional)
     * @return array registros de la tabla TiposEvento
     * @throws Exception
     */
    private function obtenerTiposEvento($idUsuario, $idTiposEvento = NULL)
    {
        try {
            if (!$idTiposEvento) {
                $comando = "SELECT * FROM " . self::NOMBRE_TABLA;

                // Preparar sentencia
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
                // Ligar idUsuario

            } else {
                $comando = "SELECT * FROM " . self::NOMBRE_TABLA .
                    " WHERE " . self::ID_TIPOSEVENTO . "=? AND " .
                    self::ID_USUARIO . "=?";

                // Preparar sentencia
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
                // Ligar idTiposEvento e idUsuario
                $sentencia->bindParam(1, $idTiposEvento, PDO::PARAM_INT);
                $sentencia->bindParam(2, $idUsuario, PDO::PARAM_INT);
            }

            // Ejecutar sentencia preparada
            if ($sentencia->execute()) {
                http_response_code(200);
                return
                    [
                        "estado" => self::ESTADO_EXITO,
                        "datos" => $sentencia->fetchAll(PDO::FETCH_ASSOC)
                    ];
            } else
                throw new ExcepcionApi(self::ESTADO_ERROR, "Se ha producido un error");

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    /**
     * A�ade un nuevo TiposEvento asociado a un usuario
     * @param int $idUsuario identificador del usuario
     * @param mixed $TiposEvento datos del TiposEvento
     * @return string identificador del TiposEvento
     * @throws ExcepcionApi
     */
    private function crear($idUsuario, $TiposEvento)
    {
        if ($TiposEvento) {
            try {

                $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

                // Sentencia INSERT
                $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                    self::PRIMER_NOMBRE . "," .
                    self::PRIMER_APELLIDO . "," .
                    self::TELEFONO . "," .
                    self::CORREO . "," .
                    self::ID_USUARIO . ")" .
                    " VALUES(?,?,?,?,?)";

                // Preparar la sentencia
                $sentencia = $pdo->prepare($comando);

                $sentencia->bindParam(1, $primerNombre);
                $sentencia->bindParam(2, $primerApellido);
                $sentencia->bindParam(3, $telefono);
                $sentencia->bindParam(4, $correo);
                $sentencia->bindParam(5, $idUsuario);


                $primerNombre = $TiposEvento->primerNombre;
                $primerApellido = $TiposEvento->primerApellido;
                $telefono = $TiposEvento->telefono;
                $correo = $TiposEvento->correo;

                $sentencia->execute();

                // Retornar en el �ltimo id insertado
                return $pdo->lastInsertId();

            } catch (PDOException $e) {
                throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
            }
        } else {
            throw new ExcepcionApi(
                self::ESTADO_ERROR_PARAMETROS,
                utf8_encode("Error en existencia o sintaxis de par�metros"));
        }

    }

    /**
     * Actualiza el TiposEvento especificado por idUsuario
     * @param int $idUsuario
     * @param object $TiposEvento objeto con los valores nuevos del TiposEvento
     * @param int $idTiposEvento
     * @return PDOStatement
     * @throws Exception
     */
    private function actualizar($idUsuario, $TiposEvento, $idTiposEvento)
    {
        try {
            // Creando consulta UPDATE
            $consulta = "UPDATE " . self::NOMBRE_TABLA .
                " SET " . self::PRIMER_NOMBRE . "=?," .
                self::PRIMER_APELLIDO . "=?," .
                self::TELEFONO . "=?," .
                self::CORREO . "=? " .
                " WHERE " . self::ID_TIPOSEVENTO . "=? AND " . self::ID_USUARIO . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

            $sentencia->bindParam(1, $primerNombre);
            $sentencia->bindParam(2, $primerApellido);
            $sentencia->bindParam(3, $telefono);
            $sentencia->bindParam(4, $correo);
            $sentencia->bindParam(5, $idTiposEvento);
            $sentencia->bindParam(6, $idUsuario);

            $primerNombre = $TiposEvento->primerNombre;
            $primerApellido = $TiposEvento->primerApellido;
            $telefono = $TiposEvento->telefono;
            $correo = $TiposEvento->correo;

            // Ejecutar la sentencia
            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }


    /**
     * Elimina un TiposEvento asociado a un usuario
     * @param int $idUsuario identificador del usuario
     * @param int $id identificador del TiposEvento
     * @return bool true si la eliminaci�n se pudo realizar, en caso contrario false
     * @throws Exception excepcion por errores en la base de datos
     */
    private function eliminar($idUsuario, $idTiposEvento)
    {
        try {
            // Sentencia DELETE
            $comando = "DELETE FROM " . self::NOMBRE_TABLA .
                " WHERE " . self::ID_TIPOSEVENTO . "=? AND " .
                self::ID_USUARIO . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            $sentencia->bindParam(1, $idTiposEvento);
            $sentencia->bindParam(2, $idUsuario);

            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }
}

?>