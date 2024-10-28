<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

require_once __DIR__."/accesoDatos.php";

class Juguete{

    public string $marca;
    public int $precio;
    public string | null $path_foto;
    public int | null $id;

    public function __construct($id=-1, $marca ="",$precio=0,$path_foto=null)
    {
        $this->id = $id;
        $this->marca = $marca;
        $this->precio = $precio;
        $this->path_foto = $path_foto;
    }

    ////////////////////////////// ALTA JUGUETE /////////////////////////////////////////////

    public function agregarJuguete(Request $request, Response $response, array $args):Response{

        $std = new stdClass();
        $std->exito = false;
        $std->mensaje = "Alta auto incompletado...hubo un error.";


        $param = $request->getParsedBody();
        $obj = json_decode($param["juguete_json"]);

        $foto = $_FILES["foto"];

        $marca = $obj->marca;

        $jueguete = new Juguete(-1,$marca,$obj->precio,$foto["tmp_name"]);

        $std->exito = $jueguete->Agregar();

        $newResponse = $response->withStatus(200);
        
        if($std->exito){
            $std->mensaje = "Alta jueguete completado.";
            $std->status = 200;

        }else{
            $newResponse = $response->withStatus(418);
            $std->status = 418;
        }

        $newResponse->getBody()->write(json_encode($std));

        return $newResponse->withHeader('Content-Type', 'application/json');

    }

    public function Agregar(): bool
    {
        $objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); // genero un objeto pdo para poder realizar consulta y usar sus funciones

        $directorioFotos = "./src/fotos/"; // genero el directorio

        if (!is_dir($directorioFotos)) // te cheque si esta el directorio o sino te lo crea
        {
            mkdir($directorioFotos, 0755, true);// aca te lo crea
        }

        $nombreFoto = $this -> marca . ".jpg";//primero el nombre el date: son los minutos,segundos etc y despues la extension
        $rutaFoto = $directorioFotos . $nombreFoto;

        if (is_uploaded_file($this -> path_foto))//pregunta si el atributo no esta vacio
        {
            if (move_uploaded_file($this -> path_foto, $rutaFoto)) //aca se sube la foto al direcctorio en este cas a la carpeta fotos
            {   //realizo la consulta, aca le pido que me agregue l nuevo usuario
                $consulta = $objetoAccesoDato->retornarConsulta("INSERT into juguetes (marca,path_foto,precio)
                    values(:marca,:path_foto,:precio)");
                // aca le agrego los valores con los parametros y como tercer param le deigo su tipo de dato
                $consulta->bindValue(':marca',$this->marca, PDO::PARAM_STR);
                $consulta->bindValue(':precio', $this->precio, PDO::PARAM_INT);
                $consulta->bindValue(':path_foto', $rutaFoto, PDO::PARAM_STR);
        
                $consulta->execute();	

                if ($consulta -> rowCount() == 1)// para verificar si se modifico una linea
                {
                    return true;
                }
            }
        }
        return false;
    }

    //////////////////////////////////////////// LISTADO JUGUETE /////////////////////////////////////

    public function listadoJuguetes(Request $request, Response $response, array $args){

        $std = new stdClass();
        $std->exito = false;
        $std->mensaje = "no se pudo hacer el listado";

        $arrayAutos = Juguete::traerTodosJuguetes();

        if(!empty($arrayAutos)){

            $std->exito = true;
            $std->mensaje = "se pudo hacer el listado";
            $std->dato = $arrayAutos;

            $newResponse = $response->withStatus(200);
            $std->status = 200;
        }else{
            $newResponse = $response->withStatus(418);
            $std->dato = null;
            $std->status = 418;
        }

        $newResponse->getBody()->write(json_encode($std));

        return $newResponse->withHeader('Content-Type', 'application/json');

    }

    public static function traerTodosJuguetes(){

        try{

            $arrayJuguetes = array();

            $objpdo = AccesoDatos::dameUnObjetoAcceso();

            
            $query = $objpdo->retornarConsulta("SELECT id,marca, precio,path_foto FROM juguetes ");

            $query->execute();//  base de datos la procese y devuelva los resultados.

            //el fetch_assoc:obtiene una fila de resultados como un array asociativo,
            while($fila = $query->fetch(PDO::FETCH_ASSOC)){
                //donde las claves del array son los nombres de las columnas seleccionadas
                $instanciaAuto = new self($fila["id"],$fila["marca"],$fila["precio"],$fila["path_foto"]);

                array_push($arrayJuguetes,$instanciaAuto);
                 
            }

        }catch(PDOException $err){

            echo"ERROR: ".$err->getMessage();

        }

        return  $arrayJuguetes;

    }

    //////////////////////////////////// ELIMINAR JUGUETE //////////////////////////////////////////////////////


    public function eliminar(Request $request, Response $response, array $args):Response{

        $std = new stdClass();
        // Obtener datos de la solicitud DELETE
        //$parametros = json_decode(file_get_contents("php://input"));

        //$id = $parametros->id;
        $id = $args["id_juguete"];

        $cantidad = Juguete::EliminarJuguete($id);

        if($cantidad > 0){
            $std->mensaje = "se pudo eliminar el juguete de la base de datos";
            $newResponse = $response->withStatus(200);
            $std->exito = true;

        }else{
            $newResponse = $response->withStatus(418);
            $std->mensaje = "no se pudo eliminar el juguete";
            $std->exito = false;
        }

        
        $newResponse->getBody()->write(json_encode($std));

        return $newResponse->withHeader('Content-Type', 'application/json');

    }

    public static function EliminarJuguete($id){
        $objPdo = AccesoDatos::dameUnObjetoAcceso();

        $consulta = $objPdo->retornarConsulta("SELECT path_foto FROM juguetes WHERE id = :id"); //obtengo la ruta de la foto actual del empleado

        $consulta->bindValue(":id",$id,PDO::PARAM_INT);
        $consulta->execute();
        $juguete = $consulta->fetch(PDO::FETCH_ASSOC);//me devuelve verdadero o falso
        
        if($juguete){// es un tru o false
            $fotoActual = $juguete["path_foto"];
            $consulta =$objPdo->retornarConsulta("DELETE FROM juguetes WHERE id = :id");
            $consulta->bindValue(':id', $id, PDO::PARAM_INT);
            $consulta->execute();

            if ($consulta -> rowCount() == 1)// //si se elimino exitosamente
            {   
                unlink($fotoActual);//$fotoActual = empleados/fotos/nuevo_empleado.060601.jpg -> por ejemplo...
                return true;
            }
        }

        return false;
    }

    

    ////////////////////////////////////////////// MODIFICAR JUGUETE /////////////////////////////////////////////////////////

    public function modificar(Request $request, Response $response, array $args): Response
	{
        $param = $request->getParsedBody();
        $obj = json_decode($param["juguete"]);
    

        //$foto = $_FILES["foto"];
        $foto = $_FILES['foto'];
        
        $juguete = new Juguete($obj->id_juguete,$obj->marca,$obj->precio,$foto["tmp_name"]);

		$resultado = $juguete->modificarJuguete();
		
        // genero una instancia del objeto std
	   	$objDelaRespuesta = new stdclass();
		$objDelaRespuesta->resultado = $resultado;
        $objDelaRespuesta->exito = false;

        if($resultado){
            $objDelaRespuesta->exito = true;
        }

        //INDICO CÓDIGO DE ESTADO Y MENSAJE ASOCIADO.
		$newResponse = $response->withStatus(200, "OK");

        //GENERO EL JSON A PARTIR DEL objeto
		$newResponse->getBody()->write(json_encode($objDelaRespuesta));

        //INDICO EL TIPO DE CONTENIDO QUE SE RETORNARÁ (EN EL HEADER).
        
		return $newResponse->withHeader('Content-Type', 'application/json');		
	}

    public function modificarJuguete()// MOFICO EL USUARIO QUE TRAGO DE LA BD
	{
		$objPdo = AccesoDatos::dameUnObjetoAcceso();

        $consulta = $objPdo->retornarConsulta("SELECT path_foto FROM juguetes WHERE id = :id"); //obtengo la ruta de la foto actual del empleado

        $consulta->bindValue(":id",$this->id,PDO::PARAM_INT);
        $consulta->execute();
        $usuario = $consulta->fetch(PDO::FETCH_ASSOC);// el resultado lo manipulo como un array associativo
        $fotoActual = $usuario["path_foto"]; //recupero el path de la foto

        $directorioFotos = "./src/fotos/"; //recupero el directorio
        $rutaFoto = $fotoActual; //rutafoto seria la foto que recupere de la  bd

        if($this->path_foto !== null){

            if(is_uploaded_file($this->path_foto)){

                if(file_exists($fotoActual)){// si existe la foto  entra al if
    
                    unlink($fotoActual);//borra la foto si esta repetida
    
                }
    
                $nombreFoto = $this -> marca . "_modificacion" . ".jpg";
                $rutaFoto = $directorioFotos . $nombreFoto;// concateno el directorio con el nombre
    
                if (!move_uploaded_file($this->path_foto, $rutaFoto)) //sino se mueve la foto, me retorna false 
                {
                    return false;
                }
    
            }
        }

        $query = $objPdo->retornarConsulta("UPDATE juguetes SET marca = :marca, precio = :precio, path_foto = :path_foto
            WHERE id = :id");//dameUnObjetoAcceso

        $query -> bindValue(":id", $this->id, PDO::PARAM_INT);
        $query -> bindValue(":marca", $this->marca, PDO::PARAM_STR);
        $query -> bindValue(":precio", $this->precio, PDO::PARAM_INT);
        $query -> bindValue(":path_foto", $rutaFoto, PDO::PARAM_STR);
      
        return $query->execute();
	}

    


}