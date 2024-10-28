<?php
class AccesoDatos
{
    private static AccesoDatos $objetoAcceosDatos;
    private PDO $objetoPDO;
 
    private function __construct()
    {
        try { 
            $this->objetoPDO = new PDO('mysql:host=localhost;dbname=jugueteria_bd;charset=utf8', 'root', '', array(PDO::ATTR_EMULATE_PREPARES => false,PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
            $this->objetoPDO->exec("SET CHARACTER SET utf8");
            } 
        catch (PDOException $e) { 
            print "Error!: " . $e->getMessage(); 
            die();
        }
    }
 
    public function retornarConsulta(string $sql)
    { 
        return $this->objetoPDO->prepare($sql); 
    }
    public function retornarUltimoIdInsertado()
    { 
        return $this->objetoPDO->lastInsertId(); 
    }
 
    public static function dameUnObjetoAcceso()
    { 
        if (!isset(self::$objetoAcceosDatos)) {          
            self::$objetoAcceosDatos = new AccesoDatos(); 
        } 
        return self::$objetoAcceosDatos;        
    }
 
    // Evita que el objeto se pueda clonar
    public function __clone()
    { 
        trigger_error('La clonación de este objeto no está permitida', E_USER_ERROR); 
    }
}