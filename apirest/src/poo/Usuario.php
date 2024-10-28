<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

require_once "accesoDatos.php";
require_once __DIR__."/Autentificadora.php";
class Usuario{

    public string $correo;
    public string | null $foto;
    public string $nombre;
    public string $apellido;
    public string $clave;
    public string $perfil;
    public int | null $id;

    public function __construct($id=-1, $correo ="", $clave="", $nombre="",$apellido="",$perfil="",$foto=null)
    {
        $this->id = $id;
        $this->correo = $correo;
        $this->clave = $clave;
        $this->nombre = $nombre;
        $this->apellido = $apellido;
        $this->perfil = $perfil;
        $this->foto = $foto;
    }

    ////////////////////// LISTADO DE USUARIOS //////////////////////////////////////////////////////////////

    public function mostrarListado(Request $request, Response $response, array $args):Response{

        $std = new stdClass();
        $std->exito = false;
        $std->mensaje = "no se pudo hacer el listado";

        $arrayUsuarios = Usuario::traerTodoLosUsuarios();

        if(!empty($arrayUsuarios)){

            $std->exito = true;
            $std->mensaje = "se pudo hacer el listado";
            $std->dato = $arrayUsuarios;

            $newResponse = $response->withStatus(200);
        }else{
            $newResponse = $response->withStatus(424);
            $std->dato = null;
        }

        $newResponse->getBody()->write(json_encode($std));

        return $newResponse->withHeader('Content-Type', 'application/json');

    }

    public static function traerTodoLosUsuarios(){

        try{

            $array_usuarios = array();

            $objpdo = AccesoDatos::dameUnObjetoAcceso();

            
            $query = $objpdo->retornarConsulta("SELECT id,nombre, apellido,correo,perfil ,foto,clave FROM usuarios ");

            $query->execute();//  base de datos la procese y devuelva los resultados.

            //el fetch_assoc:obtiene una fila de resultados como un array asociativo,
            while($fila = $query->fetch(PDO::FETCH_ASSOC)){
                //donde las claves del array son los nombres de las columnas seleccionadas
                $instanciaUsuario = new self($fila["id"],$fila["correo"],$fila["clave"],$fila["nombre"],$fila["apellido"],
                $fila["perfil"],$fila["foto"]);

                array_push($array_usuarios,$instanciaUsuario);
            }

        }catch(PDOException $err){

            echo"ERROR: ".$err->getMessage();

        }

        return  $array_usuarios;

    }


    //////////////////////////////77 LOGIN Y JWT ///////////////////////////////////////////////////////////
    //////////////////////////////////////// CREAR TOKEN //////////////////////////////////////////////7


    public function login(Request $request, Response $response, array $args){

        //Se envían el correo y la clave (parámetro obj_json)
        $params = $request->getParsedBody();
        $obj = json_decode($params["user"]);

        $datos = new stdClass();
        $datos->jwt = null;
        $datos->exito = false;

        // me devuelve un array asociativo del usuario encontrado o false
        $arrayUsuarios = Usuario::VerificarUsuario($obj->correo,$obj->clave);

        //Si el usuario existe en la base de datos, se creará un JWT
        if($arrayUsuarios["resultado"]){

            //creamos un array asociativo con los datos del usuario
            $data = array();
            $data["id"] = $arrayUsuarios["id"];
            $data["nombre"] = $arrayUsuarios["nombre"];
            $data["apellido"] = $arrayUsuarios["apellido"];
            $data["correo"] = $arrayUsuarios["correo"];
            //$data["clave"] = $arrayUsuarios["clave"];
            $data["perfil"] = $arrayUsuarios["perfil"];
            $data["foto"] = $arrayUsuarios["foto"];

            $datos->exito = $arrayUsuarios["resultado"];

            $jwt = Autentificadora::CrearJWT($data);// creo el jwt
            $datos->jwt = $jwt;
            $newResponse = $response->withStatus(200);
            $newResponse->getBody()->write(json_encode($datos));
            

        }else{
            $newResponse = $response->withStatus(403);
     

            $newResponse->getBody()->write(json_encode($datos));
        }

        return $newResponse->withHeader('Content-Type', 'application/json');

    }

    public static function VerificarUsuario($correo, $clave){

        $array = array();
        $array["resultado"] = false;
        $objPdo = AccesoDatos::dameUnObjetoAcceso();

        $query = $objPdo->retornarConsulta("SELECT id,nombre,apellido,correo,clave,perfil,foto FROM usuarios WHERE correo = :correo AND clave = :clave");
        $query->bindValue(':correo',$correo,PDO::PARAM_STR);
        $query->bindValue(':clave',$clave,PDO::PARAM_STR);

        $query->execute();

        while($fila = $query->fetch(PDO::FETCH_ASSOC))
        {   $array["nombre"] = $fila["nombre"];
            $array["apellido"] = $fila["apellido"];
            $array["id"] = $fila["id"];
            $array["perfil"] = $fila["perfil"];
            $array["foto"] = $fila["foto"];
            $array["correo"] = $fila["correo"];
            $array["clave"] = $fila["clave"];
            $array["resultado"] = true;
        }
        return $array;
    }

    ////////////////////////////////////// VERIFICAR TOKEN /////////////////////////////////////////

    public function verificarJwt(Request $request, Response $response, array $args):Response{

        $headers = getallheaders();//obtengo todos los encabezados

        if(isset($headers["Authorization"]))//veriifico que el encabezado sea el parametro token
        {
            $token = $headers["Authorization"];//recupero el token
            $obj_rta = Autentificadora::verificarJWT($token);//verifico que sea valido

            if($obj_rta->verificado)//si es valido entra
            {
                $obj_rta->exito = true;

                $respuesta = $response->withStatus(200);
                $respuesta->getBody()->write(json_encode($obj_rta));
            }
            else
            {
                $obj_rta->exito = false;
                $respuesta = $response->withStatus(403);
                $respuesta->getBody()->write(json_encode($obj_rta));
            }
        }
        else
        {
            $data =  new stdClass();
            $data->exito = false;
            $data->mensaje = "no se recibio ningun token";
            $respuesta = $response->withStatus(403);
            $respuesta->getBody()->write(json_encode($data));
        }


        return $respuesta;

    }
    public function verificarPorAuth(Request $request, Response $response, array $args):Response{

        $tokenBearer = $request->getHeader("Authorization")[0];
        $token = explode("Bearer ", $tokenBearer)[1];

        $obj_rta = Autentificadora::verificarJWT($token);//verifico que sea valido

        if($obj_rta->verificado)//si es valido entra
        {
            $obj_rta->exito = true;

            $respuesta = $response->withStatus(200);
            $respuesta->getBody()->write(json_encode($obj_rta));
        }
        else
        {
            $obj_rta->exito = false;
            $respuesta = $response->withStatus(403);
            $respuesta->getBody()->write(json_encode($obj_rta));
        }

        return $respuesta;


    }

    /*
    public function Modificar(Request $request, Response $response, array $args): Response
	{

        $obj_rta = new stdClass();

        // Inicializo todo como ERROR
        $obj_rta->exito = FALSE;
        $obj_rta->mensaje = "";
        $status = 403;

		$data = $request->getParsedBody();
        $juguete_json = json_decode($data['juguete'], true);

        $juguete = new Juguete();
        $juguete->id = $juguete_json['id_juguete'];
        $juguete->marca = $juguete_json['marca'];
        $juguete->precio = $juguete_json['precio'];

		if($request->getUploadedFiles()){

			$archivos = $request->getUploadedFiles();
			$destino = __DIR__ . "/../fotos/";
		
			try {
				if(!is_dir($destino)) mkdir($destino, 0755, true);

				// Esto lo hacemos para obtener la extension
				$nombreAnterior = $archivos['foto']->getClientFilename();
				$extension = explode(".", $nombreAnterior);
				$extension = array_reverse($extension);

				$juguete->pathFoto = $juguete->marca . "_modificacion." . $extension[0];

				$pathFoto = $destino . $juguete->pathFoto;

                $archivos['foto']->moveTo($pathFoto);

				$resultado = $juguete->modificarJuguete();

				if($resultado) {
					$obj_rta->exito = $resultado;
					$obj_rta->mensaje = "JUGUETE modificado en la base de datos";
					$status = 200;
				}
	
				else {
					$obj_rta->mensaje = "No se pudo modificar el JUGUETE en la base de datos";
				}
			}

			catch(PDOException $e){
				$obj_rta->mensaje .= $e->message;
			}

		}
	
		$newResponse = $response->withStatus($status);
		$newResponse->getBody()->write(json_encode($obj_rta));
		return $newResponse->withHeader('Content-Type', 'application/json');

	}
		

	public function modificarJuguete()
	{
		$objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
		$consulta = $objetoAccesoDato->retornarConsulta("
				update juguetes 
				set marca=:marca,
				precio=:precio,
				path_foto=:pathFoto,
				WHERE id=:id");
		$consulta->bindValue(':id',$this->id, PDO::PARAM_INT);
		$consulta->bindValue(':marca',$this->marca, PDO::PARAM_STR);
		$consulta->bindValue(':precio', $this->precio, PDO::PARAM_DOUBLE);
		$consulta->bindValue(':pathFoto', $this->pathFoto, PDO::PARAM_STR);
		return $consulta->execute();
 	}
	
    
    
    */



}