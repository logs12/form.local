<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 19.05.2015
 * Time: 23:07
 */
class Valid
{
    /**
    * Массив полей, содержащих ошибки.
    * Ключи - имена полей,
    * значение - массив, ключи которого - имена функций валидации, а значения - сообщения об ошибке
    */
    public $fields_error = array();

    public function __construct()
    {

    }
    public function execute()
    {
        $val = $this->getVal();
        $file =$this->getFile();
        $err = 0;
        $value = array();
        if (!empty($val))
        {
            if (!$this->validationEmpty($val['email'])) {
                $this->fields_error['email'] = "Заполните пожалуйста поле";
                $err++;
            } else $value['email'] = $val['email'];

            if (!$this->validationEmpty($val['subject'])) {
                $this->fields_error['subject'] = "Заполните пожалуйста поле";
                $err++;
            } else $value['subject'] = $val['subject'];

            if (!$this->validationEmpty($val['message'])) {
                $this->fields_error['message'] = "Заполните пожалуйста поле";
                $err++;
            } else $value['message'] = $val['message'];

            /*
             * if (!$this->validationData($val['data'])) {
                $this->fields_error["data"] = "Дата должна быть не меньше настоящей.";
                $err++;
            } else
            */
                $value['data'] = $val['data'];

            if (!$this->validationEmail($val['email'])) {
                $this->fields_error['email'] = "Email не корректен.";
                $err++;
            } else $value['email'] = $val['email'];
        }
        if (!empty($file))
        {

            if (!$this->validationFile($file)) {
                $this->fields_error['file'] = "Файл не загружен";
                $err++;
            } else $value['file'] = $file;

                        if (!$this->validationFileError($file)) {
                            $this->fields_error['file'] = "Ошибка загрузки";
                            $err++;
                        } else $value['file'] = $file;

                        if (!$this->validationFileExt($file,array('png','jpg'))) {
                            $this->fields_error['file'] = "Неверное расширение файла";
                            $err++;
                        } else $value['file'] = $file;

                    if (!$this->validationFileSize($file,2))
                    {
                        $this->fields_error['file'] = "Превышен допустимый размер файла";
                        $err++;
                    } else $value['file'] = $file;

        }

        if ($err>0)
            return false;
            //echo 'false';
        else
            return $value;
            //var_dump($value);

    }

    /**
     * @return $arrVal - массив обезвреженных данных из REQUEST
     */
    public function getVal()
    {
        $val = $_REQUEST;
        if (!empty($val))
        {
            foreach ($val as $k => $v) {
                if (is_array($v))
                    $this->getVal();
                else
                    $arrVal[$k] = htmlspecialchars($v);
            }
        }
        return $arrVal;
    }

    /**
     * @return array - массив с данными о файле
     * upd - доработать рекурсивный обход массива FILES вида $_FILES['file']['name'][0]
     */
    public function getFile()
    {
        $filename = $_FILES['file']['name'];
        $type = $_FILES['file']['type'];
        $tmp_name = $_FILES['file']['tmp_name'];
        $error = $_FILES['file']['error'];
        $size = $_FILES['file']['size'];
        //var_dump($filename);
        return array('name'=>$filename, 'type'=>$type, 'tmp_name'=>$tmp_name, 'error'=>$error, 'size'=>$size);
    }

    ############## VALIDATION FUNCTION ###############

    /**
     * @param $field_name
     * @param $field_value
     * @param $option
     * @return bool true - не пустое, false - пустое значение
     */
    public function validationEmpty($field_value)
    {
        if (empty($field_value) && !is_numeric($field_value))
        {
            return false;
        }
        else return true;
    }

        /**
         * @param $email - value fields type email validation
         * @return bool
         */
    public function validationEmail($email)
    {
        return preg_match("/^[-0-9a-z_\.]+@[-0-9a-z^\.]+\.[a-z]{2,4}$/i",$email);
    }

        /**
         * @param $tell - value fields type tell validation
         * @return bool
         */
    public function validationTell($tell)
    {
        if (!preg_match("/((8|\+7)-?)?\(?\d{3,5}\)?-?\d{1}-?\d{1}-?\d{1}-?\d{1}-?\d{1}((-?\d{1})?-?\d{1})?/",$tell))
            return true;
        else
            return false;
    }

    /**
     * Validation data
     * @param $val - data validation
     * @return bool false - no validation, true - validation
     */
    public function validationData($val)
    {
        $stamp = strtotime($val);
        if(!$stamp) return false;
        else
        {
            if (($stamp < time()))
            {
                return false;
            }
        }
        return true;
    }


    /**
     * Проверка файла на успешную загрузку
     */
    private function validationFile($file)
    {
        //var_dump($file);
        if( ($file===false) || (!is_array($file)) || empty($file['name']) || !is_uploaded_file($file['tmp_name']))
        {
            return false;
        }
        else return true;
    }

    /**
    * Проверка файла на ошибки
    */
    private function validationFileError($file)
    {
        if($file['error'] !== 0)
        {
            return false;
        }
        else return true;
    }

    /**
    * Проверяет допустимо ли расширение файла (валидные расширения указываются в атрибуте allowed_ext)
    * @param ARRAY $file - массив описывающий файл с ключами аналогичными $_FILES
    * @param ARRAY $options - массив c заданными расширениями
    * @return BOOL true - допустимое расширение || false - не допустимое
    */
    public function validationFileExt($file, $options)
    {
        $fileName=$file['name'];  // получение имени
        $ext = strtolower(substr(strrchr($fileName, '.'), 1));
        $cnt = 0;
        if(!in_array($ext,$options)) $cnt++;
        if($cnt > 0) return false;
        else return true;
    }

        /**
         * Проверка допустимости размера файла (сравнивается со значением атрибута max_file_size)
         * @param INTEGER $options - заданный размер
         * @param ARRAY $file - массив описывающий файл
         * @return BOOL true - размер не превышает заданный || false - размер превышен
         */
    private function validationFileSize($file, $options)
    {
        if($file['size']>($options*1048576))
        {
            return false;
        }
        else return true;
    }
}
