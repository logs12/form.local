<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 08.05.2015
 * Time: 21:44
 */
class DB
{
    /*
    const DB_HOSTNAME = 'localhost';
    const DB_DATABASE = 'form';
    const DB_USERNAME = 'root';
    const DB_PASSWORD = 'root';
    const DB_DRIVER = 'mysql';
    */
    protected $db;

    protected static $instance = null;

    protected function __construct(){
        $params = parse_ini_file('config.ini');
        $this->db = new PDO
        (
            $params['db.conn'],
            $params['db.user'],
            $params['db.pass']
        );
        /*
        $this->db = new PDO
        (
            self::DB_DRIVER.":"
            .self::DB_HOSTNAME.";"
            .self::DB_DATABASE,
            self::DB_USERNAME,
            self::DB_PASSWORD
        );
        */
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    static function getInstance()
    {
        if(!self::$instance instanceof self)
            self::$instance = new self;
        return self::$instance;

    }

    /**
     * @param $data - neutralization value from sql injection
     * @return mixed - neutralized value
     */
    public function quote($data)
    {
        try
        {
            return $this->db->quote($data);
        }
        catch(PDOException $e)
        {
            echo $e->getMessage();
        }

    }

    public function exec($sql)
    {
        try
        {
            return $this->db->exec($sql);
        }
        catch(PDOException $e)
        {
            echo $e->getMessage();
            //print_r($e->errorInfo());
        }
    }

    public function query($sql)
    {
        try
        {
            return $this->db->query($sql);
        }
        catch(PDOException $e)
        {
            echo $e->getMessage();
        }
    }

    /**
     * Подготавливает запрос к выполнению и возвращает ассоциированный с этим запросом объект
     * @param $sql - корректный запрос с точки зрения целевой СУБД
     */
    public function prepare($sql)
    {
        try
        {
            $data = $this->db->prepare($sql);
            return $data;
        }
        catch(PDOExeption $e)
        {
            echo $e->getMessage();
        }
    }

    /**
     * Связывает параметр с заданным значением
     * @param $stmt - объект ввращаемый методом prepare
     * @param $parameter - Идентификатор параметра запроса. Для подготавливаемых запросов с именованными параметрами это будет имя в виде :name. Если используются неименованные параметры (знаки вопроса ?)
     * @param $value - Значение, которое требуется привязать к параметру.
     * @param int $data_type - Явно заданный тип данных параметра. Тип задается одной из констант PDO::PARAM_*.
     */
    public function bindValue($stmt, $parameter ,$value, $data_type = PDO::PARAM_INT)
    {
        try
        {
            $data = $stmt->bindValue($parameter ,$value, $data_type);
            return $data;
        }
        catch(PDOException $e)
        {
            echo $e->getMessage();
        }
    }

    /**
     * Запускает подготовленный запрос на выполнение
     * @param $stmt - объект ввращаемый методом prepare
     * @param array $input_parameters - массив значений входных (только входных) параметров
     */
    public function execute($stmt)
    {
        try
        {
            $data = $stmt->execute();
            return $data;
        }
        catch(PDOException $e)
        {
            echo $e->getMessage();
        }
    }

    /**
     * Возвращает данные одного столбца следующей строки результирующего набора
     * @param $stmt - result from query
     * @param int $column_number - Номер столбца, данные которого необходимо извлечь.
     * @return mixed - data from fetch
     */
    public function fetchColumn($stmt, $column_number = 0)
    {
        try
        {
            $data = $stmt->fetchColumn($column_number);
            return $data;
        }
        catch(PDOException $e)
        {
            echo $e->getMessage();
        }
    }

    /**
     * @param $stmt - result from query
     * @param string $typeDate - parameters fom fetch
     * @return mixed - data from fetch
     */
    public function fetch($stmt,$typeDate = 'lazy')
    {
        try
        {
            switch($typeDate)
            {
                case 'assoc':
                    $data =  $stmt->fetch(PDO::FETCH_ASSOC);
                    break;
                case 'both':
                    $data = $stmt->fetch(PDO::FETCH_BOTH);
                    break;
                case 'lazy':
                    $data = $stmt->fetch(PDO::FETCH_LAZY);
                    break;
            }
            return $data;
        }
        catch (PDOException $e)
        {
            echo $e->getMessage();
        }
    }
    /**
     * @param $stmt
     * @param string $typeData
     * @param bool $nameClass
     */
    public  function fetchAll($stmt, $typeData = 'array', $nameClass = false, $column = 0)
    {
        try
        {
            switch ($typeData)
            {
                case 'array':
                {
                    $data = $stmt->fetchALL(PDO::FETCH_ASSOC);
                    break;
                }
                case 'obj':
                {
                    $data = $stmt->fetchALL(PDO::FETCH_CLASS,$nameClass);
                    break;
                }
                case 'column':
                {
                    $data = $stmt->fetchAll(PDO::FETCH_COLUMN,$column);
                    break;
                }
            }
            return $data;

        }
        catch(PDOException $e)
        {
            echo $e->getMessage();
        }
    }

    protected function __clone() {}
    public function __destruct() {}
}
