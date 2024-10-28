<?php
use Slim\Psr7\Response as ResponseMW;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

require_once __DIR__."/Usuario.php";
class Mw{

    public function verificarCamposVacios(Request $request, RequestHandler $handler):ResponseMW{
        
        $parametros = $request->getParsedBody();//recupero los paramteros via post
        $std = new stdClass();
        $std->error = "ninguno";

        //verifico que haya parametros y si existe el param obj_json
        if(array_key_exists("user",$parametros)){

            $json = json_decode($parametros["user"]);

        }else if(array_key_exists("usuario",$parametros)){

            $json = json_decode($parametros["usuario"]);

        }

        if($json->correo != "" && $json->clave != ""){

            $response = $handler->handle($request);
            $resultado = (string)$response->getBody();

        }else if($json->correo == "" && $json->clave != ""){

            $std->error = "el valor correo esta vacio";
            $resultado = json_encode($std);
            $std->status = 409;

        }else if($json->correo != "" && $json->clave == ""){

            $std->error = "el valor clave esta vacio";
            $std->status = 409;
            $resultado = json_encode($std);
        }else{
            $std->error = "los dos valores estan vacios";
            $resultado = json_encode($std);
            $std->status = 409;
        }

        // creo el middleware
        $responseMw = new ResponseMW();

        if($std->error = "ninguno"){
            $responseMw->withStatus(200);
        }else{
            $responseMw->withStatus(403);
        }
        $responseMw->getBody()->write($resultado);
        return $responseMw;

    }

    public function verificarCorreoClaveBd(Request $request, RequestHandler $handler):ResponseMW{

        //Se envían el correo y la clave (parámetro obj_json)
        $params = $request->getParsedBody();
        $obj = json_decode($params["user"]);

        $std = new stdClass();
        $std->mensaje = "no existe el usuario";

        // me devuelve un array asociativo del usuario encontrado o false
        $arrayUsuarios = Usuario::VerificarUsuario($obj->correo,$obj->clave);

        if($arrayUsuarios["resultado"]){

            $response = $handler->handle($request);
            $resultado = (string)$response->getBody();

        }else{
            $resultado = json_encode($std);

        }

        // creo el middleware
        $responseMw = new ResponseMW();

        if($std->error = "ninguno"){
            $responseMw->withStatus(200);
        }else{
            $responseMw->withStatus(403);
        }
        $responseMw->getBody()->write($resultado);
        return $responseMw;

    }

    

    public function mostrarTablaUsuarios(Request $request, RequestHandler $handler):ResponseMW{

        $response = $handler->handle($request);
        $contenidoAPI = $response->getBody();
        
        $std = new stdClass();

        $std->array = $contenidoAPI->dato;

        foreach($std->array as $usuario){

            unset($usuario->clave);

        }

        $respuesta = new ResponseMW();
        $respuesta = $respuesta->withStatus(403);
        $respuesta->getBody()->write(json_encode($std));

        return $respuesta;


    }

    public function verificarPorAuth(Request $request, RequestHandler $handler):ResponseMW{

        $tokenBearer = $request->getHeader("Authorization")[0];
        $token = explode("Bearer ", $tokenBearer)[1];

        $obj_rta = Autentificadora::verificarJWT($token);//verifico que sea valido

        if($obj_rta->verificado)//si es valido entra
        {
            $obj_rta->exito = true;

            $response = $handler->handle($request);
            $contenidoAPI = (string) $response->getBody();    
            $respuesta = new ResponseMW();
            $respuesta = $respuesta->withStatus(200);
            $respuesta->getBody()->write($contenidoAPI);
        }
        else
        {
            $respuesta = new ResponseMW();
            $respuesta = $respuesta->withStatus(403);
            $respuesta->getBody()->write(json_encode($obj_rta));
        }

        return $respuesta;


    }


}