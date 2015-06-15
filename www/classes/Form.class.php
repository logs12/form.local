<?php

/**
EXAMPLE:
$fields = array(
		'validation_function' => array('validation_empty'=>array()),
		'items' => array(
			'data'	=> array(
						'validation_function' => array('validation_empty'=>array()),
						'error_message'	=> array('validation_empty' => "Заполните поле!"),
						'items' => array(
							'a' => array(
								'label'=>'Текст',
								'error_message'	=> array('validation_empty' => "Заполните поле 'Текст'"),
								'items' => array(
									'b' => array(
										'label'=>'E-mail',
										'validation_function' =>array('validation_email'=>array()),
										'error_message'	=> array(
											'validation_empty'=>array() => "Заполните поле 'Email'",
											'validation_email' => "Неверный формат email"
										),
									)
								)
							),
							
						)
			),
			
			'file'		=>array(
								'type' => 'file',
								'label'=>'file 1',	
								'validation_function' => array( 'validation_File'=>array(), 
																'validation_FileError'=>array(), 
																'validation_FileExt'=>array('allowed_ext'=>array('png', 'jpg')), 
																'validation_FileSize'=>array('max_file_size'=>9)
								),
								'error_message' => array(
														'validation_File' =>'файл не найден', 
														'validation_FileError'=>'Ошибка, файл не загружен',
														'validation_FileExt'=>'Неверное расширение', 
														'validation_FileSize'=>'Превышен размер файла'
														
								),
								'items'=>array(
									'a'=>array(
										'items'=>array(
											'b'=>array()
										)
									),
									'c'=>array()
								)
			),
			
		)		
);

$form = new form($fields);
$res = $form -> sendMail('qwe@qwe.ru', 'qwe@qwe.ru', 'test', 'tpl_mail.php');

Ответ для ajax
$json_arr = array();
$json_arr['IS_SUCCESS'] = 0;
$json_arr['ERROR_FIELDS'] = $form::getAllErrMess(__CLASS__);
$json_arr['message']="";
**/
class Form
{
	/**
	* Константа обозначения целого типа
	*/
	const TYPE_INT = 'int';
	
	/**
	* Константа обозначения строчного типа
	*/
	const TYPE_STRING = 'string';
	
	/**
	* Константа обозначения типа поля 'file'
	*/
	const TYPE_FILE = 'file';
	
	/**
	* версия класса
	*/
	public $version = "3.0";
	
	/**
	* Массив всех полей с атрибутами, настройками валидации, обработки и т.п. (см. описание конструктора)
	*/
	private $fields;
	
	/**
	* массив сформированных ключей с учетом вложенности через разделитель
	*/
	private $fields_key = array();
		
	/**
	* массив названий полей
	*/
	private $label = array();
	
	/**
	* массив функций проверки 
	*/
	private $validation_function = array();
	
	/**
	* массив функций обработки значений
	*/
	private $processing_functions = array();
	
	/**
	* массив сообщений об ошибках, генерируются функциями фалидации. 
	* ключи массива - имена функций валидации
	*/
	private $error_message = array();
	
	/** 
	* Массив атрибутов поля
	*/
	private $attributes = array();
	
	/**
	* Массив типов для полей, поддерживается file, string - строковый (по умолчанию), int - целый
	*/
	private $type = array();
	
	/**
	* Флаг экранировать переменную в соответствии с типом
	*/
	private $isEscape = array();
	
	
	/**
	* Массив полей, содержащих ошибки. 
	* Ключи - имена полей, 
	* значение - массив, ключи которого - имена функций валидации, а значения - сообщения об ошибке
	*/
	private $error_fields=array();
	
	
	/**
	* Разделитель для приведения массива большой вложенности к одномерному
	*/
	private $sep = "/";
	
	
	/**
	* массив файлов, ключи которого - имя поля, а значение массив, содержащий filepath и filename
	*/
	private $files; 
	
	/**
	* префикс для сессий, чтоб избежать пересечений
	*/
	private $namespace_prefix;
	

	
	/**
	* Инициализация объекта - заполнение массива полей
	* @param $fields - Массив всех полей с атрибутами, ключи которого - имена полей
	* 'label' - текст	
	* 'validation_function' - массив, ключи которого название проверочных функций, а значения - их опции
	*	проверочные функций могут принимать следующие названия
	*		validation_empty 	- Проверка на пустое значение строк
	*		validation_email 	- Проверка email
	*		validation_date 	- Проверка даты
	*		validation_str		- Проверка строки на символы "A-Za-z0-9-_"
	*		validation_length 	- Проверка строки на максимальное и минимальное значение символов
	*		validation_Ip		- Проверка ip адреса
	*		validation_IpList	- Проверка списка ip адресов
	*		validation_File 	- Проверка файла на успешную загрузку
	*		validation_FileError - Проверка файла на ошибки
	*		validation_FileExt 	- Проверяет допустимо ли расширение файла (валидные расширения указываются в атрибуте allowed_ext)
	*		validation_FileSize - Проверка допустимости размера файла (сравнивается со значением атрибута max_file_size)
	*		validation_inlist - Проверка вхождения значения в список (список указывается в опции list)
	* 'processing_functions' - массив, ключи которого - названия функций обработки, а значения - опции
	*	поддерживаются нативные функции, а также следующие функции
	*
	* 'error_message' - массив сообщений об ошибках, ключи которого названия проверочных функций
	* 'attributes' - массив дополнительных аттрибутов (
	*	ARRAY allowed_ext - разрешенные расширения, 
	*	DOUBLE max_file_size - максимальный размер файла Mб
	*	ARRAY processing - способ обработки строк, поддерживает trim - обрезание пробелов с концов
	*	INT max_length - максимальная длина строки
	*	INT min_length - минимальная длина строки
	* )
	* 'type' - тип поля, поддерживается file, string - строковый (по умолчанию), int - целый
	* 'isEscape' - Флаг экранировать переменную в соответствии с типом, true - экранировать, false - не экранировать
	* Поддерживается вложенность через атрибут items, одноименный родительские поля будут применяться к дочерним
	*
	* @param $namespace_prefix - уникальный ключ, для разделения данных, хранящихся в сессии
	*
	*/
	public function __construct($fields, $namespace_prefix="")
	{	
		$this->namespace_prefix = $namespace_prefix;
		
		$property = array(
			'validation_function' => array(),
			'processing_functions' => array(),
			'error_message' => array(),
			'label' => '',
			'attributes' => array(),
			'type' => self::TYPE_STRING,
			'isEscape' => true
		);
				
		
		$this->walkRecurs($fields, $property);
	}

	
	/**
	* Переберает рекурсивно заданные поля, накапливая параметры
	*/
	private function walkRecurs($property, $propertyResult, $keys=array())
	{
		
		foreach($property as $prop_el=>$val)
		{
			switch($prop_el)
			{
				case 'validation_function':
					$propertyResult['validation_function'] = array_merge($propertyResult['validation_function'], $val);
					break;
				case 'processing_functions':
					$propertyResult['processing_functions'] = array_merge($propertyResult['processing_functions'], $val);
					break;
				case 'error_message':
					$propertyResult['error_message'] = array_merge($propertyResult['error_message'], $val);
					break;
				case 'label':
					$propertyResult['label'] = $val;
					break;
				case 'attributes':
					$propertyResult['attributes'] = array_merge($propertyResult['attributes'], $val);
					break;
				case 'type':
					$propertyResult['type'] = $val;
					break;
				case 'isEscape':
					$propertyResult['isEscape'] = $val;
					break;
			}
		}
		
		if(isset($property['items']))
		{
			foreach($property['items'] as $name=>$pr)
			{
				$keys[]=$name;
				$this->walkRecurs($pr, $propertyResult, $keys);
				array_pop($keys);
			}
		}
		else
		{
			$field_key = implode($this->sep, $keys);
			$this->fields_key[] = $field_key;
			$this->label[$field_key] = $propertyResult['label'];
		
			$this->validation_function[$field_key] = $propertyResult['validation_function'];
			$this->processing_functions[$field_key] = $propertyResult['processing_functions'];
			$this->error_message[$field_key] = $propertyResult['error_message'];
			$this->attributes[$field_key] = $propertyResult['attributes'];
			$this->type[$field_key] = empty($propertyResult['type']) ? self::TYPE_STRING : $propertyResult['type'];
			$this->isEscape[$field_key] = $propertyResult['isEscape'] ? true : false;
		}	
	}
	
	/**
	* Выполняет проверки, в случае успеха возвращает обезвреженные данные в виде массива, 
	* ключ которого - имя поля (в случае вложенности имена через разделитель), а значение - значение поля
	*/
	public function execute()
	{
		$this->removeData();
		$this->removeError();
		
		$fields_key = $this->fields_key;
		$err = 0;
		$values = array();
		
		foreach($fields_key as $key)
		{
			switch($this->type[$key])
			{
				case 'file' : 
					$val = $this->getFile($key);
					$this->files[$key] = $val;
					break;
					
				default : 
					$val = $this->getVal($key);
					$val = $this->processing($key, $val);
					
					//$processing_functions
					/*
					if(array_key_exists('processing', $this->attributes))
					{
						foreach($this->attributes['processing'] as $proc)
						{
							switch($proc)
							{
								case 'trim' : $val = trim($val); break;
							
							}
						
						}
					
					}
					*/
					$values[$key] = $val;
					$this->setData($key, $val);
					break;
			}
			
			$is_validate = $this->validate($key, $val, $this->validation_function[$key]);
			if(!$is_validate) $err++;
		}
		
		if($err>0)
		{
			$this->countErr=$err;
			return false;
		}
		else
		{
			$this->removeData();
			$this->removeError();
			$values = self::explodeArrayKeys($values, $this->sep);
			if(is_array($this->files) && count($this->files)>0) 
			{
				$values = array_merge($values, $this->files);
			}
			return $values;
		}
	}
	
	/**
	* Получает обезвреженное значение из $_REQUEST
	* @param STRING name - имя поля, если вложенно, то через разделитель
	* @return MIXED $val - обезвреженное значение поля или, если не существует, 0 для типа int и false для остальных
	*/
	private function getVal($name)
	{
		$type = $this->type[$name];
		
		$keys = explode($this->sep, $name);
		$val = $_REQUEST;
		
		foreach($keys as $key)
		{
			if(isset($val[$key])) $val = $val[$key];
			else
			{
				switch($type)
				{
					case self::TYPE_INT : $negative_result = 0; break;
					default : $negative_result = false;
				}
				return $negative_result;
			}
		}

		if($this->isEscape[$name])
		{
			if(is_array($val)) $val = $this->escapeArr($val, $type);
			else 
			{
				switch($type)
				{
					case self::TYPE_INT : $val = (int)$val; break;
					default : $val = htmlspecialchars($val);
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
				
				if(isset($type[$key])) 		$type = $type[$key];
				else return false;
				
				if(isset($tmp_name[$key])) 	$tmp_name = $tmp_name[$key];
				else return false;
				
				if(isset($error[$key])) 	$error = $error[$key];
				else return false;
				
				if(isset($size[$key])) 		$size = $size[$key];
				else return false;
			}
		}
		
		return array('name'=>$filename, 'type'=>$type, 'tmp_name'=>$tmp_name, 'error'=>$error, 'size'=>$size);
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
	* Последовательный запуск функций валидации для значения поля
	* @param STRING $key - имя поля, если вложенное, то через разделитель
	* @param STRING $val - значение поля
	* @param ARRAY  $validation_func - массив функций валидации
	* @return true - в случае успеха || false - в случае ошибки валидации
	*/
	private function validate($key, $val, $validation_func)
	{
		$err = 0;
		foreach($validation_func as $func=>$options)
		{
			if(is_array($val) && $this->type[$key]!=='file')
			{
				$err += $this->validateRecurs($key, $val, $func, $options);
			}
			else if($this->type[$key]==='file' && is_array($val['name']))  // для случая name = file[]
			{
				$l = count($val['name']);
				for($i=0; $i<$l; $i++)
				{
					$key_new = $key . $this->sep . $i;
					$file_arr = array(
						'name'		=>$val['name'][$i], 
						'type'		=>$val['type'][$i], 
						'tmp_name'	=>$val['tmp_name'][$i], 
						'error'		=>$val['error'][$i], 
						'size'		=>$val['size'][$i]
					);
					$result = $this->$func($key_new, $file_arr, $options);
					if(!$result)$err++;
				}
			}
			else
			{
				$result = $this->$func($key, $val, $options);
				if(!$result)$err++;
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
			
			if(is_array($v))
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
	* Последовательный запуск функций обработки поля
	* @param STRING $key - имя поля, если вложенное, то через разделитель
	* @param STRING $val - значение поля
	* @return MIXED - обработанное поле
	*/
	private function processing($key, $val)
	{
		$funcList = $this->getInheritedNode($key, $this->processing_functions);
		foreach($funcList as $func=>$options)
		{
			if(function_exists($func))
			{
				if(count($options)>0) $val = $func($val, implode(',',$options));
				else $val = $func($val);
			}
			else
			{
				$val = $this->$func($val, $options);
			}
		}
		return $val;
	}
	
	/**
	* Функция отправки email сообщения
	* @param STRING $to - адрес отправки
	* @param STRING $from - от кого
	* @param STRING $subject - тема сообщения
	* @param STRING $tpl - адрес шаблона сообщения
	* @param ARRAY  $val - дополнительные переменные (значения ро-умолчанию) для шаблона
	*/
	public function sendMail($to, $from, $subject, $tpl, $val=array())
	{
		$values = $this->execute();

		if($values!==false)
		{
			$mailresult = true;
			$sendMails = explode(',' , $to);
			foreach($sendMails as $mailto)
			{
				//if(!isset($values['mailto'])) $values['mailto']=$mailto;
				$values = array_merge($val, $values);
				$messages = $this->getTemplate($values, $tpl);
				
				//$subject = '=?utf-8?b?'. base64_encode($subject) .'?=';
				$subject = $subject;
				$boundary = "--".md5(uniqid(time())); // генерируем разделитель
				$headers  = "From: ".$from."\r\n";   
				$headers .= "MIME-Version: 1.0\r\n";
				$headers .= "Date: ". date('D, d M Y h:i:s O') ."\r\n"; 
				$headers .= "Content-Type: multipart/mixed; boundary=\"".$boundary."\"\r\n";
				
				$multipart = "--".$boundary."\r\n";
				
				$multipart .= "Content-type: text/html; charset='utf-8' \r\n";
				$multipart .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";

				$multipart .= $messages."\r\n\r\n";
							
				foreach($this->files as $field_file=>$file)
				{
					$filepath = $file['tmp_name'];		
					$filename = $file['name'];

					if(!empty($filename) && !empty($filepath))
					{
						$fp = fopen($filepath, "r");
						if($fp) 
						{
							$content = fread($fp, filesize($filepath));
							fclose($fp);
							$multipart .= "--".$boundary."\r\n";
							$multipart .= "Content-Type: application/octet-stream\r\n";
							$multipart .= "Content-Transfer-Encoding: base64\r\n";
							$multipart .= "Content-Disposition: attachment; filename=\"".$filename."\"\r\n\r\n";
							$multipart .= chunk_split(base64_encode($content))."\r\n";		
						}
					}
					
				}
				$multipart .= "--".$boundary."--\r\n";
			  
				$mailresult = $mailresult && mail($mailto, $subject, $multipart, $headers);
				
			}
			if($mailresult) return 1;
			else return -1;
		}
		else return 0;
	}
	
	
	/**
	* Возвращает активный шаблон $tpl с переменными массива $values
	* @param ARRAY $values - массив значений
	* @param $tpl - путь к файлу шаблона
	* @return STRING $messages - текст по шаблону
	*/
	private function getTemplate($values, $tpl)
	{
		ob_start();
		extract($values, EXTR_OVERWRITE);
		include($tpl);
		$messages =ob_get_contents();
		ob_end_clean();
		return $messages;
	}
	
	/**
	* Получает атрибут поля, если нет ищет у родителей
	* @param $field_name - ключ поля
	* @param $attr_nam - имя атрибута
	* @return MIXED - значение атрибута || false - если не найден
	*/
	private function getAttribute($field_name, $attr_name)
	{
		$attr_value = false;
		if(isset($this->attributes[$field_name][$attr_name]))
		{
			$attr_value = $this->attributes[$field_name][$attr_name];
		}
		else 
		{
			$arr_k = explode($this->sep, $field_name);
			$l=count($arr_k);
			$buf_k_str='';
			for($i=0; $i<$l; $i++)
			{
				$buf_k_str.=$arr_k[$i];
				if(isset($this->attributes[$buf_k_str][$attr_name]))
				{
					$attr_value = $this->attributes[$buf_k_str][$attr_name];
				}
				$buf_k_str.=$this->sep;
			}
		}
		
		return $attr_value;
	}
	
	/**
	* Получает значение узла для поля, если нет ищет у родителей
	* @param $field_name - ключ поля
	* @param $arr_search - массив для поиска
	* @param $key_arr_search - ключ для поиска, если не указан, возвращает массив
	* @return MIXED - значение узла || false - если не найден
	*/
	private function getInheritedNode($field_name, $arr_search, $key_arr_search=false)
	{
		$val = false;
		if($key_arr_search)
		{
			if(isset($arr_search[$field_name]) && array_key_exists($key_arr_search,$arr_search[$field_name])) 
			{
				$val = $arr_search[$field_name][$key_arr_search];
			}
			else
			{
				$arr_k = explode($this->sep, $field_name);
				$l=count($arr_k);
				$buf_k_str='';
				for($i=0; $i<$l; $i++)
				{
					$buf_k_str.=$arr_k[$i];
					if(isset($arr_search[$buf_k_str]) && array_key_exists($key_arr_search,$arr_search[$buf_k_str]))
					{
						$val = $arr_search[$buf_k_str][$key_arr_search];
					}
					$buf_k_str.=$this->sep;
				}
			}
		}
		else
		{
			if(isset($arr_search[$field_name])) $val = $arr_search[$field_name];
			else
			{
				$arr_k = explode($this->sep, $field_name);
				$l=count($arr_k);
				$buf_k_str='';
				for($i=0; $i<$l; $i++)
				{
					$buf_k_str.=$arr_k[$i];
					if(isset($arr_search[$buf_k_str]))
					{
						$val = $arr_search[$buf_k_str];
					}
					$buf_k_str.=$this->sep;
				}
			}
		}
		
		return $val;
	}
	
	
/******************************************* ФУНКЦИИ ВАЛИДАЦИИ ********************************************/	
	
	/*
	* Проверка на пустое значение строк
	* @param $field_name - ключ поля
	* @param $field_value - значение поля
	* @param $options - опции валидации
	* @return BOOL true - не пустое, false - пустое значение
	*/
	private function validation_empty($field_name, $field_value, $options)
	{
		if(empty($field_value) && !is_numeric($field_value))
		{
			$this->setError($field_name, __FUNCTION__);
			return false;
			
		}
		else return true;
	}
	
	
	/**
	* Проверка email
	* @param $field_name - ключ поля
	* @param $field_value - значение поля
	* @return BOOL true - успешная проверка, false - не успешная
	*/
	private function validation_email($field_name, $field_value, $options)
	{
		if(!preg_match( "#^[0-9a-z_\-\.]+@[0-9a-z\-\.]+\.[a-z]{2,6}$#i", $field_value ))
		{
			$this->setError($field_name, __FUNCTION__);
			return false;
		}
		else return true;
	}
	
	/**
	* Проверка даты
	* @param $field_name - ключ поля
	* @param $field_value - значение поля
	* @return BOOL true - успешная проверка, false - не успешная
	*/
	private function validation_date($field_name, $field_value, $options)
	{
		$stamp=strtotime($field_value);
		if(!$stamp) 
		{
			$this->setError($field_name, __FUNCTION__);
			return false;
		}
		else
		{
			$month = date( 'm', $stamp );
			$day = date( 'd', $stamp );
			$year = date( 'Y', $stamp );
			
			if(!checkdate($month, $day, $year))
			{
				$this->setError($field_name, __FUNCTION__);
				return false;
			}
		}
		
		return true;
	}
	
	/**
	* Проверка строки на символы "A-Za-z0-9-_"
	* @param $field_name - ключ поля
	* @param $field_value - значение поля
	* @return BOOL true - успешная проверка, false - не успешная
	*/
	public function validation_str($field_name, $field_value, $options)
	{
		if(!empty($field_value) && !preg_match("/^[a-zA-Z0-9_-]+$/",$field_value)) 
		{
			$this->setError($field_name, __FUNCTION__);
			return false;
		}
		else return true;
	}
	
	/**
	* Проверка строки на максимальное и минимальное значение символов 
	* (проходит валидацию в случае пустого значения)
	* @param $field_name - ключ поля
	* @param $field_value - значение поля
	* @return BOOL true - успешная проверка, false - не успешная
	*/
	public function validation_length($field_name, $field_value, $options)
	{
		if(isset($options['max_length'])) $maxLength = $options['max_length'];
		else $maxLength = $this->getAttribute($field_name, 'max_length');
		
		if(isset($options['min_length'])) $minLength = $options['min_length'];
		else $minLength = $this->getAttribute($field_name, 'min_length');
		
		$result = true;
		
		try 
		{
			$l = strlen($field_value);
			
			if($maxLength && $l>$maxLength)
			{
				throw new Exception('');
			}
			
			if($minLength && !empty($field_value) && $l < $minLength)
			{
				throw new Exception('');
			}
		} 
		catch (Exception $e) 
		{
			$this->setError($field_name, __FUNCTION__);
			$result = false;
		}
		
		return $result;
	}
	
	/**
	* Проверка ip адреса
	* @param $field_name - ключ поля
	* @param $field_value - значение поля
	* @param $options - массив опций
	* @return BOOL true - успешная проверка, false - не успешная
	*/
	public function validation_Ip($field_name, $field_value, $options)
	{
		if(empty($field_value)) return true;
		$result = true;
		try 
		{
			$parts = explode( '/', $field_value );
			if(isset($parts[1]))
			{
				if(($parts[1]>32) || ($parts[1]<0))
				{
					throw new Exception('incorrect mask');
				}
			}
			$ip = $parts[0];
			
			$ipPattern = '#^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$#';
			if(!preg_match( $ipPattern, $ip )) throw new Exception('mismatch format ip');
			
			$parts = explode( '.', $ip );
			$err = 0;
			for($i=0; $i<4; $i++)
			{
				if(((int)$parts[$i] > 255 ) || ((int)$parts[$i] < 0))
				{
					$err++;
				}
			}
			if($err>0) throw new Exception('mismatch format ip');
		
		}
		catch (Exception $e) 
		{
			$this->setError($field_name, __FUNCTION__);
			$result = false;
		}
		
		return $result;
	}
	
	/**
	* Проверка списка ip адресов, разделенных запятыми или пробельными символами
	* @param $field_name - ключ поля
	* @param $field_value - значение поля
	* @param $options - опции
	* @return BOOL true - успешная проверка, false - не успешная
	*/
	public function validation_IpList($field_name, $field_value, $options)
	{
		$result = true;
		
		if(empty($field_value)) return $result;
				
		try 
		{
			$field_value = preg_replace('/\s+/', ',', $field_value);
			$field_value = preg_replace('/,+/', ',', $field_value);
			if(empty($field_value)) throw new Exception('empty list');
			
			$listIp = explode(',',$field_value);
			if(count($listIp)==0) throw new Exception('empty list');
			
			$err = 0;
			$i=0;
						
			foreach($listIp as $ip)
			{
				$validate = $this->validation_Ip($field_name.$i, trim($ip), $options);
				if(!$validate) $err++;
				$i++;
			}
			
			if($err>0) throw new Exception('error ip');
		} 
		catch (Exception $e) 
		{
			$this->setError($field_name, __FUNCTION__);
			$result = false;
		}
		
		return $result;
			
	}
	
	/**
	* Проверка url адресов
	* @param $field_name - ключ поля
	* @param $field_value - значение поля
	* @param $options - опции
	* @return BOOL true - успешная проверка, false - не успешная
	*/
	public function validation_url($field_name, $field_value, $options)
	{
		
		if(empty($field_value)) return true;
		
		$opt = array(
			'regexp'=>"~^https?://(?:[а-яa-z0-9](?:[-а-яa-z0-9]*[а-яa-z0-9])?\.)+[а-яa-z](?:[-а-яa-z0-9]*[а-яa-z0-9])?\.?(?:$|/)~Diu"
		);
		if($this->validation_regexp($field_name, $field_value, $opt, false))
		{
			$result = true;
		}
		else
		{
			$this->setError($field_name, __FUNCTION__);
			$result = false;
		}
		return $result;
		/*
		if(empty($field_value)) return true;
		if(filter_var($field_value, FILTER_VALIDATE_URL))
		{
			$result = true;
		}
		else
		{
			$this->setError($field_name, __FUNCTION__);
			$result = false;
		}
		return $result;
		*/
	}
	
	public function validation_domain($field_name, $field_value, $options)
	{
		
		if(empty($field_value)) return true;
		
		$opt = array(
			'regexp'=>'/^([а-яa-z0-9]([а-яa-z0-9\-]{0,61}[а-яa-z0-9])?\.)+[а-яa-z]{2,6}$/ui'
		);
		if($this->validation_regexp($field_name, $field_value, $opt, false))
		{
			$result = true;
		}
		else
		{
			$this->setError($field_name, __FUNCTION__);
			$result = false;
		}
		return $result;
		
		
		/*
		try 
		{
			if(stripos($field_value, 'http://') === 0)
			{
				$field_value = field_value($field_value, 7);
			}
		 
			if(!substr_count($field_value, '.')) throw new Exception('');
					 
			if(stripos($field_value, 'www.') === 0)
			{
				$field_value = substr($field_value, 4);
			}
		 
			$again = 'http://' . $field_value;
			$result = filter_var ($again, FILTER_VALIDATE_URL);
			if(!$result) throw new Exception('');
		}
		catch (Exception $e) 
		{
			$this->setError($field_name, __FUNCTION__);
			$result = false;
		}
		*/
	}
	
	/**
	* Проверяет значение на соответствие regexp, Perl-совместимому регулярному выражению. 
	* @param $field_name - ключ поля
	* @param $field_value - значение поля
	* @param ARRAY $options - массив, содержащий опцию 'regexp'
	* @param BOOL $setError - устанавливать ошибки? по-умолчанию true
	* @return BOOL true - успешная проверка, false - не успешная
	*/
	public function validation_regexp($field_name, $field_value, $options, $setError = true)
	{
		if(filter_var($field_value, FILTER_VALIDATE_REGEXP, array('options'=>$options)))
		{
			$result = true;
		}
		else
		{
			$result = false;
		}
		
		if($result===false && $setError)
		{
			$this->setError($field_name, __FUNCTION__);
		}
		
		return $result;
	}
	
	/**
	* Проверяет вхождение значения в список
	* @param $field_name - ключ поля
	* @param $field_value - значение поля
	* @param ARRAY $options - массив, содержащий опцию 'list' - массив разрешенных значений
	* @return BOOL true - успешная проверка, false - не успешная
	*/
	public function validation_inlist($field_name, $field_value, $options)
	{
		if(isset($options['list']) && is_array($options['list']) && in_array($field_value, $options['list']))
		{
			$result = true;
		}
		else
		{
			$result = false;
			$this->setError($field_name, __FUNCTION__);
		}
		
		return $result;
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
	
	
	/*
	public function validation_login($login)
	{
		if($login=='' || strlen($login)>30 || strlen($login)<3 || !preg_match("/^[a-zA-Z0-9_-]+$/",$login)) return false;
		else return true;
	}
	
	public function validation_password($pass, $is_null=false)
	{
		if($is_null && empty($pass)) return true;
				
		if(empty($pass)) return false;
		if(strlen($pass)>30 || strlen($pass)<3) return false;
		if(!preg_match("/^[a-zA-Z0-9_-]+$/",$pass)) return false;
		
		return true;
	}
	
	public function validation_domain_name($domain_name)
	{
		return 	(preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name) //valid chars check
				&& preg_match("/^.{1,253}$/", $domain_name) //overall length check
				&& preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name)   ); //length of each label
	}
	*/
	
	
/*** END ФУНКЦИИ ВАЛИДАЦИИ ****/	

/******************************************* ФУНКЦИИ ОБРАБОТКИ ********************************************/	
	/**
	* Разбивает текст по регулярному выражению и разделителю в массив
	* @param $val - исходный текст
	* @param $options - массив опций
	*	'regexp' 	- регулярное выражение, если не указано, берется /\s+/
	*	'separator' - разделитель, если не указан, берется запятая
	* @return ARRAY - массив элементов
	*/
	public function parseTextList($val, $options)
	{
		if(array_key_exists('regexp', $options)) $regexp = $options['regexp'];
		else $regexp = '/\s+/';
		
		if(array_key_exists('separator', $options)) $separator = $options['separator'];
		else $separator = ',';
		
		$val = trim($val);
		if(empty($val)) return array();
		$val = preg_replace($regexp, $separator, $val);
		$val = preg_replace("/$separator+/", $separator, $val);
		
		$list = explode($separator,$val);
		
		return $list;
	}
/*** END ФУНКЦИИ ОБРАБОТКИ ****/	
	
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
			$_SESSION[$this->namespace_prefix .'error_fields'][$key]=$message;
		}
	}
	
	/**
	* удаляет ошибки (очищает сессию)
	*/
	private function removeError()
	{
		if(isset($_SESSION[$this->namespace_prefix  . 'error_fields'])) unset($_SESSION[$this->namespace_prefix .'error_fields']);
	}
	
	/**
	* Возвращает ошибку или false
	* @param $key - имя поля
	*/
	static function getErrMess($key, $namespace_prefix ="")
	{
		if(isset($_SESSION[$namespace_prefix . 'error_fields'][$key])) return $_SESSION[$namespace_prefix . 'error_fields'][$key];
		else return '';
	}
	
	/**
	* Возвращает асоциативный массив ошибок, где ключ - имя поля, 
	* вид которого зависит от параметра $view, а значение - сообщение об ошибке
	* @param STRING $namespace_prefix - префикс для идентификации списка ошибок
	* @param $view - вид ключа 0 - a/b/c, 1 - массив, 2 - a[b][c]
	* @return ARRAY асоциативный массив ошибок
	*/
	static function getAllErrMess($namespace_prefix="", $view = 1)
	{
		$result = false;
		if(isset($_SESSION[$namespace_prefix .'error_fields'])) 
		{
			switch($view)
			{
				case 0 : $result = $_SESSION[$namespace_prefix .'error_fields']; break;
				case 1 : $result = self::explodeArrayKeys($_SESSION[$namespace_prefix .'error_fields']); break;
				case 2 :
					$result = array();
					foreach($_SESSION[$namespace_prefix .'error_fields'] as $key=>$mess)
					{
						if(strpos($key, '/'))
						{
							$k = str_replace('/', '][', $key);
							$pos = strpos($k, '][');
							$k = substr_replace($k, '', $pos, 1);
							$k .= ']';
						}
						else
						{
							$k = $key;
						}
						$result[$k] = $mess;
					}
					
			}
			
		}
		
		return $result;
	}
	
	/**
	* Возвращает значение полей, если $key = false или поля с ключем $key
	* @param STRING $key - имя поля
	* @return MIXED : 
	*	$key === false - ARRAY
	*	$key - STRING
	* 	false - если ключ или значения не найдены
	*/
	static function getData($key, $namespace_prefix="")
	{
		if(isset($_SESSION[$namespace_prefix .'data']))
		{
			$data = $_SESSION[$namespace_prefix .'data'];
			if($key===false)
			{
				return $data;
			}
			else if(isset($data[$key]))
			{
				return $data[$key];
			}
		}
		
		return false;
	}
	
	/**
	* Устанавливает значение данных в сессию
	*/
	private function setData($key, $value)
	{
		$_SESSION[$this->namespace_prefix .'data'][$key] = $value;
	}
	
	/**
	* Очищает данные формы из сессии
	*/
	private function removeData()
	{
		if(isset($_SESSION[$this->namespace_prefix .'data'])) unset($_SESSION[$this->namespace_prefix .'data']);
	}
	
	/**
	* Проверяет выбрано ли значение списка
	* @param STRING $name	- имя списка
	* @param STRING $value  - значение опции
	*/
	static function isSelected($name, $value, $namespace_prefix="")
	{
		if(isset($_SESSION[$namespace_prefix .'data'][$name]))
		{
			if(is_array($_SESSION[$namespace_prefix .'data'][$name]))
			{
				if(in_array($value, $_SESSION[$namespace_prefix .'data'][$name]))	return "selected='selected'";
			}
			else
			{
				if($_SESSION[$namespace_prefix .'data'][$name]==$value)	return "selected='selected'";
			}
		}
		else return '';
	}
	
	/**
	* Преобразует массив вида array('a/b/c'=>'val') в массив array('a'=>array('b'=>array('c'=>'val')))
	* @param ARRAY $arr - входной массив с разделителем в ключах
	* @param STRING $sep - разделитель, по-умолчанию "/"
	* @return ARRAY $arrResult - преобразованный массив
	*/
	private static function explodeArrayKeys($arr, $sep="/")
	{
		$keys = array_keys($arr);
		$vals = array_values($arr);
		$l = count($keys);
		$arrResult = array();
		
		for($i=0; $i<$l; $i++)
		{
			$k = explode($sep,$keys[$i]);
			$ll = count($k);
			
			$buf = array();
			$b=&$buf;
			
			for($j=0; $j<$ll; $j++)
			{
				if($j == $ll-1) $b[$k[$j]] = $vals[$i]; 
				else 
				{
					$b[$k[$j]] = array();
					$b = &$b[$k[$j]];
				}
				
			}
			$arrResult=array_merge($arrResult, $buf);
		}
		
		return $arrResult;
	}
	
	/**
	* Возвращает загруженные файлы
	* @param $key - ключ поля или false для получения всех файлов
	* @return array - массив аналогичный $_FILES
	*/
	public function getUploadFiles($key=false)
	{
		if($key && array_key_exists($key, $this->files))
		{
			return $this->files[$key];
		}
		else if(is_array($this->files) && count($this->files)>0)
		{
			return $this->files;
		}
		else return false;
	}
}

?>