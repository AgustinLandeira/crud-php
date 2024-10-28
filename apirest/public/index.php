<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as ResponseMW;

use Slim\Factory\AppFactory;
use \Slim\Routing\RouteCollectorProxy;
use \Firebase\JWT\JWT;
use Slim\Routing\RouteGroup;

require __DIR__ . '/../vendor/autoload.php';




$app = AppFactory::create();


require_once __DIR__."/../src/poo/Usuario.php";
require_once __DIR__."/../src/poo/Juguete.php";
require_once __DIR__."/../src/poo/MW.php";

$app->get("/",\Usuario::class . ":mostrarListado");//nivel de app

$app->post('/',\Juguete::class . ":agregarJuguete")->add(Mw::class. ":verificarPorAuth");
$app->get('/juguetes',\Juguete::class . ":listadoJuguetes");

$app->post("/login",\Usuario::class . ":login")->add(Mw::class. ":verificarCorreoClaveBd")->add(Mw::class. ":verificarCamposVacios");

$app->get("/login",\Usuario::class . ":verificarPorAuth");

$app->group("/toys",function(RouteCollectorProxy $grupo){

    $grupo->delete("/{id_juguete}",\Juguete::class . ":eliminar");//->add(Mw::class. ":verificarPorAuth");
    $grupo->post("/",\Juguete::class . ":modificar");

})->add(Mw::class. ":verificarPorAuth");

// $app->group("/tablas",function(RouteCollectorProxy $grupo){

//     $grupo->get("/usuarios",\Usuario::class . ":mostrarListado")->add(Mw::class. ":mostrarTablaUsuarios");
//     //$grupo->post("/",\Juguete::class . ":modificar")->add(Mw::class. ":verificarToken");

// });
//CORRE LA APLICACIÓN.
$app->run();
