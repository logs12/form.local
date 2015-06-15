<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 27.04.2015
 * Time: 20:48
 */

class Mail
{

    /**
     * @param TPLSUBJ - name
     */
    const TPLSUBJ = 'Subj';
    const TPLMESS = 'Mess';

    /**
     * @param $tplSubj
     * @param $tplMess
     */
    public function __construct($tplSubj,$tplMess)
    {
        //$this->tplSubj=$_SERVER['DOCUMENT_ROOT'].'/tpl/'.$tplSubj.self::TPLSUBJ.'.html';
        $this->tplSubj='/tpl/'.$tplSubj.self::TPLSUBJ.'.html';
        //$this->tplMess=$_SERVER['DOCUMENT_ROOT'].'/tpl/'.$tplSubj.self::TPLMESS.'.html';
        $this->tplMess='/tpl/'.$tplSubj.self::TPLMESS.'.html';
    }

    /**
     * Send mail with classes
     * @param $from
     * @param $to
     * @param string $encoding
     * @param string $is_html
     * @param $values - массив значений
     * @return bool
     */
    public function send($from, $to, $encoding = 'UTF-8', $values, $is_html = 'html')
    {
        if ($is_html == 'html')
            $mime = 'text/html';
        else if($is_html == 'plain')
            $mime = 'text/plain';
        $textSubj = $this->getContent($this->tplSubj,$values);
        $textMess = $this->getContent($this->tplMess,$values);
        $headers = "From: {$from}\r\n"
                    ."Content-type:{$mime}; charset={$encoding}\r\n"
                    ."Mime-Version:1.0\r\n";
        $subj = '=?'.$encoding.'?B?'.base64_encode($textSubj).'?=';
        $mess = $textMess;
        return mail($to, $subj, $mess, $headers);
    }

    /**
     * Get content from files
     * @param $tpl - путь к шаблону
     * @param $values array  - массив с данными
     * @return string - текстовое содержимое шаблона
     */
    public function getContent($tpl,$values=array())
    {
        // запускаем буферизацию
        ob_start();
        // Импортирует переменные из массива в текущую таблицу символов
        extract($values, EXTR_OVERWRITE);
        // подключаем шаблон
        include($tpl);
        //Возвращает содержимое буфера вывода
        $text = ob_get_contents();
        ob_end_clean();
        return $text;
    }

    public function __destruct(){}
}


?>