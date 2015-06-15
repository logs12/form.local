<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 17.05.2015
 * Time: 22:27
 */
class Pagination
{

    protected $id;
    protected $startChar;
    protected $prevChar;
    protected $nextChar;
    protected $endChar;

    /**
     * Конструктор
     * @param string $id         - атрибут ID элемента <UL> - постраничной навигации
     * @param string $startChar  - текст ссылки "В начало"
     * @param string $prevChar   - текст ссылки "Назад"
     * @param string $nextChar   - текст ссылки "Вперед"
     * @param string $endChar    - текст ссылки "В конец"
     */
    public function __construct(
                                $class = 'pagination',
                                $startChar = '&laquo;',
                                $prevChar = '&lsaquo;',
                                $nextChar = '&rsaquo;',
                                $endChar = '&raquo;'
                                )
    {
        $this->id = $class;
        $this->startChar = $startChar;
        $this->prevChar = $prevChar;
        $this->nextChar = $nextChar;
        $this->endChar = $endChar;
    }

    /**
     * @param $all - Полное кол-во элементов (Материалов в категории)
     * @param $limit - Кол-во элементов на странице
     * @param $start - Текущее смещение элементов
     * @param int $limitLimit - Количество ссылок в состоянии
     * @param string $varName - Имя GET - переменной которая будет использоваться в постр. навигации.
     * @return string
     */
    public function getLinks($all,$limit,$start,$limitLimit = 10, $varName = 'start')
    {
        // Ничего не делаем если лимит больше или равен количеству всех элементов
        // Если лимит равен 0, на страницы не разбиваем
        if ($limit >= $all || $limit ==  0) return NULL;

        $pages      = 0; // кол-во страниц в пагинации
        $needChunk  = 0; // индекс нужного в данный момент чанка
        $queryVars  = array(); // ассоцативный массив полученный из строки запроса
        $pagesArr   = array(); // переменная для промежуточного хранения массива навигации
        $htmlOut    = ''; // HTML - код постраничной навигации
        $link       = NULL; // формируемая ссылка

        // Строим такую же ссылку, по которой перешли на данную страницу, извлекаем из нее нашу GET переменную
        parse_str($_SERVER['QUERY_STRING'],$queryVars);

       // echo "queryVars = ".$queryVars;
       // var_dump($queryVars);


        if (isset($queryVars[$varName])) unset ($queryVars[$varName]);

        // Формируем такую же ссылку ведущую на эту же страницу:
        $link = $_SERVER['PHP_SELF'].'?'.http_build_query($queryVars);
        //$link = '/www/index.php?'.http_build_query($queryVars);


        //echo "link = ".$link;
        //var_dump($link);


        $pages = ceil($all/$limit); // кол-во страниц

        //echo "pages = ".$pages;
        //var_dump($link);


        // заполняем массив: ключ - номер страницы, значение - смещение для БД.
        // Нумерация здесь нужна с единицы. А смещение с шагом = кол-ву материалов на странице

        for ($i = 0; $i < $pages; $i++)
        {
            $pagesArr[$i+1] = $i * $limit;
        }

        //var_dump($pagesArr);

        // Теперь что бы на странице отображать нужное кол-во ссылок
        // дробим массив со значениями [№ страницы] => "смещение" на
        // Части (чанки)
        $allPages = array_chunk($pagesArr, $limitLimit, true);

        //echo "allPages";
        //var_dump($allPages);

        // Получаем индекс чанка в котором находится нужное смещение.
        // И далее только из него сформируем список ссылок:
        $needChunk = $this->searchPage($allPages, $start);

        // Формруем ссылки "В начало", "предыдущая"

        if ($start >1)
        {
            $htmlOut .= '<li><a href="'.$link.'&'.$varName.'=0">'.$this->startChar.'</a></li>'.
                        '<li><a href="'.$link.'&'.$varName.'='.($start - $limit).'">'.$this->prevChar.'</a></li>';
        }
        else
        {
            $htmlOut .= '<li><span>'.$this->startChar.'</span></li>'.
                '<li><span>'.$this->prevChar.'</span></li>';
        }
        // Выводим ссылки из нужного чанка
        foreach( $allPages[$needChunk] AS $pageNum => $ofset )  {
            // Делаем текущую страницу не активной:
            if( $ofset == $start  ) {
                $htmlOut .= '<li><span>'. $pageNum .'</span></li>';
                continue;
            }
            $htmlOut .= '<li><a href="'.$link.'&'.$varName.'='. $ofset .'">'. $pageNum . '</a></li>';
        }

        // Формируем ссылки "следующая", "в конец"
        if ( ($all - $limit) >  $start) {
            $htmlOut .= '<li><a href="' . $link . '&' . $varName . '=' . ( $start + $limit) . '">' . $this->nextChar . '</a></li>'.
                '<li><a href="' . $link . '&' . $varName . '=' . array_pop( array_pop($allPages) ) . '">' . $this->endChar . '</a></li>';
        } else {
            $htmlOut .= '<li><span>' . $this->nextChar . '</span></li>'.
                '<li><span>' . $this->endChar . '</span></li>';
        }
        return '<ul class="'.$this->id.'">' . $htmlOut . '<ul>';
    }

    protected function searchPage(array $pageList, $needPage)
    {
        foreach($pageList as $chunk=>$pages)
        {
            if(in_array($needPage, $pages)) return $chunk;
        }
        return 0;
    }

}