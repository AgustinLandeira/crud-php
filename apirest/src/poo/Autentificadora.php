<?php

use Firebase\JWT\JWT;
use Firebase\JWT\JWTdecode;

class Autentificadora
{
    private static string $secret_key = 'landeira.agustin';
    private static array $encrypt = ['HS256'];
    private static string $aud = "";

    // el segundo parametro opcional es para manejar el tiempo de disponibilidad
    public static function crearJWT(mixed $data, int $exp = (30*4)) : string
    {
        $time = time();
        self::$aud = self::aud();

        $token = array(
            'iat'=>$time,//identifica la fecha de creacion del token,válido si se quiere poner una fecha de caducidad.
            'exp' => $time + $exp, //Identifica a la fecha de expiración del token.se calcula apartir del iat
            'aud' => self::$aud, //identifica el destinatario del token.
            'data' => $data,
            "alumno" =>[
                "nombre" => "Agustin", // nombre del quien firma el token
                "apellido" => "Landeira",// apellido del quien firma el token
                "dni_alumno" => "45580032" // DNI del alumno que firma el token.
            ],
            'app'=> "API REST 2024"
        );

        return JWT::encode($token, self::$secret_key);
    }

    public static function obtenerPayLoad(string $token) : object
    {
        $datos = new stdClass();
        $datos->exito = FALSE;
        $datos->payload = NULL;
        $datos->mensaje = "";

        try {
            //decodifico el token
            $datos->payload = JWT::decode(
                                            $token,//token
                                            self::$secret_key,//llave secreta
                                            self::$encrypt//el encriptado
                                        );
            $datos->exito = TRUE;

        } catch (Exception $e) { 

            $datos->mensaje = $e->getMessage();
        }

        return $datos;
    }
    public static function verificarJWT(string $token) : stdClass
    {
        $datos = new stdClass();
        $datos->verificado = FALSE;
        $datos->mensaje = "";

        try 
        {
            if( ! isset($token))
            {
                $datos->mensaje = "Token vacío!!!";
            }
            else
            {
                $decode = JWT::decode(
                    $token,
                    self::$secret_key,
                    self::$encrypt
                );

                if($decode->aud !== self::aud())
                {
                    throw new Exception("Usuario inválido!!!");
                }
                else
                {
                    $datos->verificado = TRUE;
                    $datos->mensaje = "Token OK!!!";
                } 
            }
        } 
        catch (Exception $e) 
        {
            $datos->mensaje = "Token inválido!!! - " . $e->getMessage();
        }

        return $datos;
    }

    private static function aud() : string
    {
        $aud = new stdClass();
        $aud->ip_visitante = "";

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $aud->ip_visitante = $_SERVER['HTTP_CLIENT_IP'];
        } 
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $aud->ip_visitante = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $aud->ip_visitante = $_SERVER['REMOTE_ADDR'];//La dirección IP desde la cual está viendo la página actual el usuario.
        }

        $aud->user_agent = @$_SERVER['HTTP_USER_AGENT'];
        $aud->host_name = gethostname();

        return json_encode($aud);//sha1($aud);
    }
}