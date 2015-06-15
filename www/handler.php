<?php
function __autoload($name)
{
    require "classes/".$name.".class.php";
}

//echo "<xmp>";print_r($_REQUEST);echo "</xmp>";
//echo "<xmp>";print_r($_FILES);echo "</xmp>";


$form = new Valid();

$values = $form->execute();

$result = array();

//var_dump($_REQUEST);
try
{


    if (!$values) throw new Exception();
    //echo "<xmp>values = ";print_r($values);echo "</xmp>";

    // загрузка файла в защищенную директорию
    $fileInfo = pathinfo($values['file']['name']);
    $fileName = uniqid().$fileInfo['filename'].'.'.$fileInfo['extension'];
    $filePath = "files/".$fileName;
    if (!move_uploaded_file($_FILES['file']['tmp_name'],$filePath)) throw new Exception('Ошибка при загрузке файла');

    // сохранение данных в БД
    $values['file'] = $filePath;

    // сжимаем картинку и добавляем путь к ней в массив для БД
    $objImg = new ImageWorker($fileName);
    $values['fileResize'] = $objImg->resizeImage();

    //echo "<xmp>values = ";print_r($values);echo "</xmp>";

    $data = new WriteDbMysql();
    $data->save($values);



    // отсылка письма
    $sendMail = new Mail('mail','mail');
    $sendMail->send('form@form.ru', 'client@client.ru', 'UTF-8', $values, $values['options']);

    $result['IS_SUCCESS'] = 1;
}
catch (Exception $e)
{
    $result['IS_SUCCESS'] = 0;

    $result['ERROR_FIELDS'] = $form->fields_error;
}

echo json_encode($result);

exit();

?>