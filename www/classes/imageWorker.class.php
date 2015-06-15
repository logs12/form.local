<?php
/**
 * Класс нормальзующий картинку по заданному разрешению
 */
class ImageWorker {

    public $outImageWidth = 100;  // длина изображения после обработки
    public $outImageHeight = 100; // высота изображения после обработки
    public $fileName = '';
    public $dir = '';  // расположения набора картинок
    public $dirUpload = 'files/result/'; // папка для выгрузки (должна быть создана)

    function __construct($filename)
    {
        $this->fileName = $filename;
        $this->dir = 'files/'.$this->fileName;
    }

    public function resizeImage()
    {
        list($width, $height) = getimagesize($this->dir);
        $new_width = 100;
        $new_height = 100;

        // возвращает идентификатор изображения, представляющий черное изображение заданного размера
        $image_p = imagecreatetruecolor($new_width, $new_height) or die('Невозможно инициализировать GD поток');

        // возвращает идентификатор изображения, представляющего изображение полученное из файла с заданным именем
        $image = imagecreatefrompng($this->dir);

        // Копирование и изменение размера изображения с ресемплированием
        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

        $newFilePath = $this->dirUpload.uniqid().'resize.png';

        // Выводит изображение в браузер или пишет в файл
        imagepng($image_p,$newFilePath, 5); //50% это качество 0-100%

        return $newFilePath;
    }
}// конец тела класса

