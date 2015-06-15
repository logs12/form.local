<?php
class Validation
{
    const TYPE_INT = 'int';

    const TYPE_STRING = 'string';

    const TYPE_FILE = 'file';

    /**
     * массив сформированных ключей с учетом вложенности через разделитель
     */
    private $fields_key = array();

    /**
     * @var array - массив функций валидации
     */
    private $validation_function = array();

    /**
     * массив сообщений об ошибках, генерируются функциями фалидации.
     * ключи массива - имена функций валидации
     */
    private $error_message = array();

    /**
     * Массив полей, содержащих ошибки.
     * Ключи - имена полей,
     * значение - массив, ключи которого - имена функций валидации, а значения - сообщения об ошибке
     */
    private $error_fields=array();

    /**
     * Массив типов для полей, поддерживается file, string - строковый (по умолчанию), int - целый
     */
    private $type = array();

    /**
     * @var = массив файлов, ключи которого - имя поля, а значение массив, содержащий filepath и filename
     */
    private $files;
	
	/**
	* Флаг экранировать переменную в соответствии с типом
	*/
	private $isEscape = array();

	/**
	* Разделитель для приведения массива большой вложенности к одномерному
	*/
	private $sep = "/";


    public function __construct($fields)
    {
        $property = array(
            'validation_function' => array(),
            'error_message' => array(),
            'type' => self::TYPE_STRING,
			'isEscape' => true,
        );
        $this->arrRecurs($fields,$property);


    }

	/**
	* 
	*
	*/
    private function arrRecurs($fields,$property, $keys = array())
    {
        foreach ($fields as $prop=>$val)
        {
            switch ($prop)
            {
                case 'validation_function':
                    $property['validation_function'] = array_merge($property['validation_function'],$val);
                    break;
                case 'error_message':
                    $property['error_message'] = array_merge($property['error_message'],$val);
                    break;
                case 'type':
                    $property['type'] = $val;
                    break;
				case 'isEscape':
					$propertyResult['isEscape'] = $val;
					break;
            }
        }
        if (isset($fields['items']))
        {
            foreach($fields['items'] as $name=>$val)
            {
                $keys[] = $name;
                $this->arrRecurs($val,$property,$keys);
                array_pop($keys);
            }
        }
        else
        {
			$fields_key = implode($this->sep,$keys);
            $this->fields_key[] = $fields_key;
            $this->validation_function[$fields_key] = $property['validation_function'];
            $this->error_message[$fields_key] = $property['error_message'];
            $this->type[$fields_key] = empty($property['type']) ? self::TYPE_STRING : $property['type'];
			$this->isEscape[$fields_key] = $property['isEscape'] ? true : false;
	   }
    }

    /*
     * получает и обезвреживает значения из Request
	* @param STRING name - имя поля, если вложенно, то через разделитель
	* @return MIXED $val - обезвреженное значение поля или, если не существует, 0 для типа int и false для остальных
     */
    public function getVal($name)
    {
        $type = $this->type[$name];

        $keys = explode($this->sep, $name);
        $val = $_REQUEST;

		foreach($keys as $key)
		{
			if (isset($val[$key])) $val = $val[$key];
			else
			{
				switch($type)
				{
					case self::TYPE_INT : $negative_result = 0; break;
					default: $negative_result = false;
				}
				return $negative_result;
			}
		}
		if ($this->isEscape[$name])
		{
			if(is_array($val)) $val = $this->escapeArr($val, $type);
			else
			{
				switch($type)
				{
					case self::TYPE_INT : $val = (int) $val; break;
					default: $val = htmlspecialchars($val);
				}
			}
		}
        return $val;
    }

    /**
     * Получает name, type, tmp_name, error, size из массива FILES для поля с учетом вложенности
     * @param STRING $k - ключ поля, если вложен, то через разделитель
     * @return ARRAY - массив описывающий файл с ключами name, type, tmp_name, error, size
     */
	private function getFile($k)
	{
		$keys = explode($this->sep, $k);
        $key_name = array_shift($keys);

        $filename 	= isset($_FILES[$key_name]['name']) ? $_FILES[$key_name]['name'] : '';
        $type		= isset($_FILES[$key_name]['type']) ? $_FILES[$key_name]['type'] : '';
        $tmp_name	= isset($_FILES[$key_name]['tmp_name']) ? $_FILES[$key_name]['tmp_name'] : '';
        $error		= isset($_FILES[$key_name]['error']) ? $_FILES[$key_name]['error'] : '';
        $size		= isset($_FILES[$key_name]['size']) ? $_FILES[$key_name]['size'] : '';

        if(count($keys)>0)
        {
            foreach($keys as $key)
            {
                if(isset($filename[$key])) $filename = $filename[$key];
                else return false;

                if (isset($type[$key])) $type = $type[$key];
                else return false;

                if (isset($tmp_name[$key])) $tmp_name = $tmp_name[$key];
                else return false;

                if (isset($error[$key])) $error = $error[$key];
                else return false;

                if (isset($size[$key])) $size = $size[$key];
                else return false;

            }
        }
        return array('name'=>$filename, 'type'=>$type, 'tmp_name'=>$tmp_name, 'error'=>$error, 'size'=>$size);
	}
	
	
    /**
     * Выполняет проверки, в случае успеха возвращает обезвреженные данные в виде массива,
     * ключ которого - имя поля (в случае вложенности имена через разделитель), а значение - значение поля
     */
    public function validate($key, $val, $validation_func)
    {
        $err = 0;
        foreach($validation_func as $func=>$options)
        {
            if(is_array($val) && $this->type[$key]!=='file')
            {
                $err += $this->validateRecurs($key, $val, $func, $options);
            }
            else if($this->type[$key]==='file' && is_array($val['name']))
            {
                $l = count($val['name']);
                for($i=0; $i<$l; $i++)
                {
                    $key_new = $i;
                    $file_arr = array(
                            'name' =>$val['name'][$i],
                            'type' =>$val['type'][$i],
                            'tmp_name' => $val['tmp_name'][$i],
                            'error' => $val['error'][$i],
                            'size' => $val['size'][$i]
                    );
                    $result = $this->$func($key_new, $file_arr, $options);
                    if(!$result) $err++;
                }
            }
            else
            {

                $result = $this->$func($key,$val,$options);
                if(!$result) $err++;
            }

        }
        if($err>0) return false;
        else return true;
    }

    /**
     * Вспомогательная функция валидации для случая вложенности
     * @param $key - ключ поля
     * @param $val - значение поля
     * @param $func - имя функции валидации
     * @param $options - асоциативный массив опций валидации
     * @param $err - счетчик ошибок
     * @return $err - количество ошибок
     */
    private function validateRecurs($key, $val, $func, $options, $err=0)
    {

        foreach($val as $k=>$v)
        {
            $key_new = $key . $this->sep . $k;

            if (is_array($v))
            {
                $err = $this->validateRecurs($key_new, $v, $func, $options, $err);
            }
            else
            {
                $result = $this->$func($key_new, $v, $options);
                if(!$result) $err++;
            }
        }
        return $err;
    }

    /**
     * возвращает обезвреженные значения
     * @param $value - массив с обезвреженными отвалидированными значениями
     * @return mixed
     */
    public function execute()
    {
        $fields_key = $this->fields_key;
        $err = 0;
        $values = array();

        foreach($fields_key as $key)
        {
            switch($this->type[$key])
            {
                case 'file':
                    $val = $this->getFile($key);

                    $this->files[$key] = $val;
                    break;
                default:
                    $val = $this->getVal($key);
                    $values[$key] = $val;
                    break;
            }

            $is_validate = $this->validate($key, $val, $this->validation_function[$key]);
            if (!$is_validate) $err++;
        }
        if ($err>0)
        {
            $this->countErr = $err;
            return false;
        }
        else
        {

            $values = self::explodeArrayKeys($values, $this->sep);
			if (is_array($this->files) && count($this->files)>0)
			{
				$values = array_merge($values, $this->files);
			}
			return $values;
        }
    }

    /**
     * Преобразует массив вида array('a/b/c'=>'val') в массив array('a'=>array('b'=>array('c'=>'val')))
     * @param ARRAY $arr - входной массив с разделителем в ключах
     * @param STRING $sep - разделитель, по-умолчанию "/"
     * @return ARRAY $arrResult - преобразованный массив
     */
    private static function explodeArrayKeys($arr, $sep="/")
    {

        //echo "<xmp>values = ";print_r($arr);echo "</xmp>";

        //Выбрать все ключи массива
        $keys = array_keys($arr);
        $vals = array_values($arr);

        $l = count($keys);
        $arrResult = array();

        for ($i=0; $i<$l; $i++)
        {
            $k = explode($sep, $keys[$i]);

            $ll = count($k);

            $buf = array();
            $b = &$buf;

            for($j=0; $j<$ll; $j++)
            {
                if($j == $ll-1) $b[$k[$j]] = $vals[$i];
                else
                {
                    $b[$k[$j]] = array();
                    $b = &$b[$k[$j]];
                }
            }
            $arrResult = array_merge($arrResult, $buf);
        }

        return $arrResult;

    }


    /**
     * escapeArr рекурсивно раскручивает массив и обезвреживает элементы для вывода на экран
     * @param ARRAY $arr - массив исходных данных
     * @return ARRAY $arr - обезвреженный массив
     */
    private function escapeArr($arr, $type=self::TYPE_STRING)
    {
        foreach($arr as $key=>&$value)
        {
            if(is_array($value)) $value = $this->escapeArr($value, $type);
            else
            {
                switch($type)
                {
                    case self::TYPE_INT : $value = (int)$value; break;
                    default : $value = htmlspecialchars($value);
                }

            }

        }
        unset($value);

        return $arr;
    }

    /**
     * устанавливает ошибку
     * @param STRING $key - ключ поля с ошибкой, разделённый '/' в случае вложенности
     * @param STRING $validation_function - имя функции валидации (ключ сообщения)
     */
    public function setError($key, $validation_function)
    {
        $message = "";
        if(!isset($this->error_fields[$key]))
        {
            if(isset($this->error_message[$key][$validation_function])) $message = $this->error_message[$key][$validation_function];
            else
            {
                $arr_k = explode($this->sep, $key);
                $l=count($arr_k);
                $buf_k_str='';
                for($i=0; $i<$l; $i++)
                {
                    $buf_k_str.=$arr_k[$i];
                    if(isset($this->error_message[$buf_k_str][$validation_function]))
                    {
                        if(!empty($this->error_message[$buf_k_str][$validation_function])) $message = $this->error_message[$buf_k_str][$validation_function];
                    }

                    $buf_k_str.=$this->sep;
                }
            }

            $this->error_fields[$key]=$message;
        }
    }

    ############## VALIDATION FUNCTION ###############

    /**
     * @param $field_name
     * @param $field_value
     * @param $option
     * @return bool true - не пустое, false - пустое значение
     */
    public function validation_empty($field_name,$field_value, $option)
    {
        if (empty($field_value) && !is_numeric($field_value))
        {
            $this->setError($field_name,__FUNCTION__);
            return false;
        }
        else return true;
    }

    /**
     * @param $email - value fields type email validation
     * @return bool
     */
    public function validation_email($email)
    {
            return preg_match("/^[-0-9a-z_\.]+@[-0-9a-z^\.]+\.[a-z]{2,4}$/i",$email);
    }

    /**
     * @param $tell - value fields type tell validation
     * @return bool
     */
    public function validation_tell($tell)
    {
        if (!preg_match("/((8|\+7)-?)?\(?\d{3,5}\)?-?\d{1}-?\d{1}-?\d{1}-?\d{1}-?\d{1}((-?\d{1})?-?\d{1})?/",$tell))
            return true;
        else
            return false;
    }


    public function validation_data($val, $arData)
    {
        foreach ($arData as $dat)
        {
            switch ($dat)
            {
                case 'old':
                    if ($val < time())
                        break;
                case 'now':
                    if ($val == time())
                        break;
                case 'future':
                    if ($val > time())
                        break;

            }

        }

    }


    /**
     * Проверка файла на успешную загрузку
     */
    private function validation_File($field_name, $file, $options)
    {
        if( ($file===false) || (!is_array($file)) || empty($file['name']) || !is_uploaded_file($file['tmp_name']))
        {
            $this->setError($field_name, __FUNCTION__);
            return false;
        }
        else return true;
    }

    /**
     * Проверка файла на ошибки
     */
    private function validation_FileError($field_name, $file, $options)
    {
        if($file['error'] !== 0)
        {
            $this->setError($field_name, __FUNCTION__);
            return false;
        }
        else return true;
    }

    /**
     * Проверяет допустимо ли расширение файла (валидные расширения указываются в атрибуте allowed_ext)
     * @param STRING $field_name - ключ
     * @param ARRAY $file - массив описывающий файл с ключами аналогичными $_FILES
     * @return BOOL true - допустимое расширение || false - не допустимое
     */
    private function validation_FileExt($field_name, $file, $options)
    {
        $fileName=$file['name'];  // получение имени
        $ext = strtolower(substr(strrchr($fileName, '.'), 1));
        $allowed_ext = array();

        if(isset($options['allowed_ext'])) $allowed_ext = $options['allowed_ext'];
        else $allowed_ext = $this->getAttribute($field_name, 'allowed_ext');

        if(!$allowed_ext) $allowed_ext = array();

        if(!in_array($ext, $allowed_ext))
        {
            $this->setError($field_name, __FUNCTION__);
            return false;
        }
        else return true;
    }

    /**
     * Проверка допустимости размера файла (сравнивается со значением атрибута max_file_size)
     * @param STRING $field_name - ключ
     * @param ARRAY $file - массив описывающий файл
     * @return BOOL true - размер не превышает заданный || false - размер превышен
     */
    private function validation_FileSize($field_name, $file, $options)
    {
        if(isset($options['max_file_size'])) $max_file_size = $options['max_file_size'];
        else $max_file_size = $this->getAttribute($field_name, 'max_file_size');

        if($file['size']>($max_file_size*1048576))
        {
            $this->setError($field_name, __FUNCTION__);
            return false;
        }
        else return true;
    }
}

?>