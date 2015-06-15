<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 29.04.2015
 * Time: 21:09
 */
//require_once('DB.class.php');

class WriteDb
{
    const DB_NAME = 'data.db';

    public $db;

    public function __construct()
    {
        if (is_file('db/'.self::DB_NAME)) {
            echo 'true';
            $this->db = DB::getInstance();
        }
        else
        {
            $this->db = DB::getInstance();
            $sql = "CREATE TABLE msgs(
                  id INTEGER PRIMARY  KEY AUTOINCREMENT,
                  email text,
                  subject text,
                  message text,
                  file text,
                  data INTEGER
                )";
            $this->db->exec($sql);
        }
    }

    /**
     * @param $data - data save
     * @return mixed - delete result
     */
    public function save($data)
    {
        foreach($data as $key=>$value)
        {
           $sqlQuote[$key] = $this->db->quote($value);
        }
        $sql = "INSERT INTO msgs(email,subject,message,file,data)
                VALUES (
                  ".$sqlQuote['email'].",
                  ".$sqlQuote['subject'].",
                  ".$sqlQuote['message'].",
                  ".$sqlQuote['file'].",
                  ".$sqlQuote['data']."
                  )";
        $result = $this->db->exec($sql);
        return $result;

    }

    /**
     * @return mixed - result data array
     */
    public function selectAll()
    {
        $sql = "SELECT * FROM msgs";
        $stmt = $this->db->query($sql);
        var_dump($stmt);
        //$result = $this->db->fetch($stmt,'assoc');
        //return $result;
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

    public function __clone(){}
    public function __destruct(){}
}
?>