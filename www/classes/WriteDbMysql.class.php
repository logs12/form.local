<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 29.04.2015
 * Time: 21:09
 */
//require_once('DB.class.php');

class WriteDbMysql
{
    const DB_NAME = 'data.db';

    public $db;

    public function __construct()
    {
        $this->db = DB::getInstance();
    }

    /**
     * @param $data - data save
     * @return mixed - delete result
     */
    public function save($data)
    {
        $sqlQuote = $this->arrRecursQuote($data);
        $email = isset($sqlQuote['email']) ?  $sqlQuote['email'] : '';
        $subject = isset($sqlQuote['subject']) ?  $sqlQuote['subject'] : '';
        $message = isset($sqlQuote['message']) ?  $sqlQuote['message'] : '';
        $file = isset($sqlQuote['file']) ?  $sqlQuote['file'] : '';
        $fileResize = isset($sqlQuote['fileResize']) ?  $sqlQuote['fileResize'] : '';
        //$data = isset($sqlQuote['data']) ?  explode('/',$sqlQuote['data']) : '';
        $data = isset($sqlQuote['data']) ? $sqlQuote['data'] : '';

        /*
        $format = 'd-m-y H:i';
        $date = date_create_from_format($format, $sqlQuote['data']);
        var_dump(strtotime($date->format('Y-m-d H:i:s')));
        */

        //$format = '06/11/2015';
        //$date = date_create_from_format($format, '22-02-12');
        //print strtotime($date);

        //var_dump($data);
        // преобразуем в UNIX
        //$dataUn = mktime(0, 0, 0, (int)$data['0'], (int)$data['1'], (int)$data['2']);
        //echo $dataUn;
        $sql = "INSERT INTO msgs(email,subject,message,file,fileResize,data)
                VALUES ($email,$subject,$message,$file,$fileResize,$data)";

        //echo "<br>sql = ".$sql;
        $result = $this->db->exec($sql);

        return $result;
    }

    /**
     * @return mixed - result data array
     */
    public function selectAll()
    {

        $count = isset($_GET['count']) ? (int)$_GET['count'] : 10;
        $start = isset($_GET['start']) ? (int)$_GET['start'] : 0;

        try
        {
            // получаем общее кол-во страниц
            $sql = "SELECT COUNT(*) FROM msgs";
            $stmt = $this->db->query($sql);
            $all = $this->db->fetchColumn($stmt,0);

            // получаем данные для текущей страницы (доделать обертку PDO)
            $sql = "SELECT * FROM msgs LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);

            $this->db->bindValue($stmt,':limit',$count);
            $this->db->bindValue($stmt,':offset',$start);
            $this->db->execute($stmt);
            $pages = $this->db->fetchAll($stmt);
            return array('count'=>$count,'start'=>$start,'all'=>$all,'pages'=>$pages);
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }

    }

    /**
     * @param $id - id data delete
     * @return mixed - delete result
     */
    public function delete($id)
    {
        $id = (int)$id;
        $sql = "DELETE * FROM msgs WHERE id = ".$id;
        $result = $this->db->exec($sql);
        return $result;
    }

    /**
     * Рекурсивное обезвреживание массива с данными от sql инъекций
     * @param $data - массив с данными
     * @param string $k - ключ массива
     * @return array = массив с обезвреженными значениями
     */
    private function arrRecursQuote($data,$k = '')
    {
        $result = array();
        if (is_array($data))
        {
            foreach ($data as $key=>$value)
            {
                $result[$key] = $this->arrRecursQuote($value,$key);
            }
        }
        else
        {
            if ($k == 'tmp_name') $result = $data;
            else $result = $this->db->quote($data);
        }
        return $result;
    }

    public function __clone(){}
    public function __destruct(){}
}
?>