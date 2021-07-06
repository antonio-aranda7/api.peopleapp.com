<?php

class documentos
{
    const NOMBRE_TABLA = "documento";
    const ID_EVENTO = "idEvento";
    const ID_DOCUMENTO = "idDocumento";
    const ID_TIPODOCUMENTO = "idTipoDocumento";
    const NOMBRE = "nombre";
    const RUTA = "ruta";
    const DOCUMENTO = "documento";
    const CODIGO_VALIDACION = "codigoValidacion";

    const CODIGO_EXITO = 1;
    const ESTADO_EXITO = 1;
    const ESTADO_ERROR = 2;
    const ESTADO_ERROR_BD = 3;
    const ESTADO_ERROR_PARAMETROS = 4;
    const ESTADO_NO_ENCONTRADO = 5;


//api.people.com/v1/documentos/paramEvent/idEvento/paramDocumento/idDocumento
//api.people.com/v1/documentos/idEvento/idDocumento -******-
    public static function get($peticion)
    {
        $idUsuario = usuarios::autorizar();

        if (empty($peticion[1]))  //si se envío idDoc
            return self::obtenerDocumentos($peticion[0]); //idEvento
        else
            return self::obtenerDocumentos($peticion[0], $peticion[1]); //IdEvento, IdDoc

    }

    public static function post($peticion)
    {
        $idUsuario = usuarios::autorizar();

        $body = file_get_contents('php://input');
        $documento = json_decode($body);

        $iddocumento = documentos::crear($idUsuario, $documento);

        http_response_code(201);
        return [
            "estado" => self::CODIGO_EXITO,
            "mensaje" => "documento creado",
            "id" => $iddocumento
        ];

    }

    public static function put($peticion)
    {
        $idUsuario = usuarios::autorizar();

        if (!empty($peticion[0])) {
            $body = file_get_contents('php://input');
            $documento = json_decode($body);

            if (self::actualizar($idUsuario, $documento, $peticion[0]) > 0) {
                http_response_code(200);
                return [
                    "estado" => self::CODIGO_EXITO,
                    "mensaje" => "Registro actualizado correctamente"
                ];
            } else {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                    "El documento al que intentas acceder no existe", 404);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_ERROR_PARAMETROS, "Falta id", 422);
        }
    }

    public static function delete($peticion)
    {
        $idUsuario = usuarios::autorizar();

        if (!empty($peticion[0])) {
            if ($numRegs = self::eliminar($idUsuario, $peticion[0]) > 0) {
                http_response_code(200);
                return [
                    "estado" => self::CODIGO_EXITO,
                    "mensaje" => "Registro eliminado correctamente",
                    "registroEliminados" => $numRegs
                ];
            } else {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                    "El documento al que intentas acceder no existe", 404);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_ERROR_PARAMETROS, "Falta id", 422);
        }
    }

    /**
     * Obtiene la colecci�n de documentos o un solo documento indicado por el identificador
     * @param int $idUsuario identificador del usuario
     * @param null $iddocumento identificador del documento (Opcional)
     * @return array registros de la tabla documento
     * @throws Exception
     */

    private function obtenerDocumentos($idEvento, $idDocumento = NULL)
    {
        try {
            if ($idEvento && !$idDocumento) {
                $comando = "SELECT * FROM " . self::NOMBRE_TABLA;

                // Preparar sentencia
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            } else if ($idEvento && $idDocumento){
                $comando = "SELECT * FROM " . self::NOMBRE_TABLA .
                    " WHERE " . self::ID_DOCUMENTO . "=?";

                // Preparar sentencia
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
                // Ligar iddocumento e idUsuario
                $sentencia->bindParam(1, $idDocumento, PDO::PARAM_INT);
            }

            // Ejecutar sentencia preparada
            if (isset($sentencia) && $sentencia->execute()) {
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
     * A�ade un nuevo documento asociado a un usuario
     * @param int $idUsuario identificador del usuario
     * @param mixed $documento datos del documento
     * @return string identificador del documento
     * @throws ExcepcionApi
     */
    private function crear($idEvento, $tipoDocumento, $archivo, $ruta, $cad64Doc) //Cadena en codeBase64
    {
        if ($cad64Doc) {
            try {

                $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

                // Sentencia INSERT
                $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                    self::ID_EVENTO . "," .
                    self::ID_TIPODOCUMENTO . "," .
                    self::NOMBRE . "," .
                    self::RUTA . "," .
                    self::DOCUMENTO . "," .
                    self::CODIGO_VALIDACION . ")" .
                    " VALUES(?,?,?,?,?)";


                //Subir el archivo en la ruta especificada en el server


                //Generar el código hash con los bits del archivo
                $codigoHash = hash_file('md5', $ruta . '/' . $archivo);

                // Preparar la sentencia
                $sentencia = $pdo->prepare($comando);

                $sentencia->bindParam(1, $idEvento);
                $sentencia->bindParam(2, $tipoDocumento);
                $sentencia->bindParam(3, $archivo);
                $sentencia->bindParam(4, $ruta);
                $sentencia->bindParam(5, $cad64Doc);
                $sentencia->bindParam(6, $codigoHash);

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
     * Actualiza el documento especificado por idUsuario
     * @param int $idUsuario
     * @param object $documento objeto con los valores nuevos del documento
     * @param int $iddocumento
     * @return PDOStatement
     * @throws Exception
     */
    private function actualizar($idUsuario, $documento, $iddocumento)
    {
        try {
            // Creando consulta UPDATE
            $consulta = "UPDATE " . self::NOMBRE_TABLA .
                " SET " . self::PRIMER_NOMBRE . "=?," .
                self::PRIMER_APELLIDO . "=?," .
                self::TELEFONO . "=?," .
                self::CORREO . "=? " .
                " WHERE " . self::ID_documento . "=? AND " . self::ID_USUARIO . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

            $sentencia->bindParam(1, $primerNombre);
            $sentencia->bindParam(2, $primerApellido);
            $sentencia->bindParam(3, $telefono);
            $sentencia->bindParam(4, $correo);
            $sentencia->bindParam(5, $iddocumento);
            $sentencia->bindParam(6, $idUsuario);

            $primerNombre = $documento->primerNombre;
            $primerApellido = $documento->primerApellido;
            $telefono = $documento->telefono;
            $correo = $documento->correo;

            // Ejecutar la sentencia
            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }


    /**
     * Elimina un documento asociado a un usuario
     * @param int $idUsuario identificador del usuario
     * @param int $iddocumento identificador del documento
     * @return bool true si la eliminaci�n se pudo realizar, en caso contrario false
     * @throws Exception excepcion por errores en la base de datos
     */
    private function eliminar($idUsuario, $iddocumento)
    {
        try {
            // Sentencia DELETE
            $comando = "DELETE FROM " . self::NOMBRE_TABLA .
                " WHERE " . self::ID_documento . "=? AND " .
                self::ID_USUARIO . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            $sentencia->bindParam(1, $iddocumento);
            $sentencia->bindParam(2, $idUsuario);

            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }
}

