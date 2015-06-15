<?php
function __autoload($name)
{
    require "classes/".$name.".class.php";
}
$data = new WriteDbMysql();
// делаем выборку данных
$dataPages = $data->selectAll();
//echo "<xmp>"; var_dump($dataPages); echo "</xmp>";

// построение постраничной навигации
$navPagin = new Pagination();
$pag = $navPagin->getLinks($dataPages['all'],$dataPages['count'],$dataPages['start'],10,'start');

function dateToUtc($data)
{
    return date('d-m-Y H:i:s',$data);
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Форма</title>
    <meta http-equiv="content-type" content="text/html" charset="utf-8">
    <!-- jquery -->
    <script src = "js/jquery-2.1.0.min.js"></script><!-- jquery -->

    <link rel="stylesheet" type="text/css" href="css/style.css">
    <link rel="stylesheet" type="text/css" href="lib/bootstrap/css/bootstrap.css" media='all'>
</head>
<body>


<div id = "textArea">
    <?php foreach($dataPages['pages'] as $value): ?>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Текст</th>
                    <th>Картинка</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <?php foreach($value as $k=>$v): ?>
                            <?php switch($k): case 'id':?>
                                №<?=$v?>
                                <?php break; ?>
                            <?php case 'data':?>
                                <!--<p>--><?//=$k?><!--:--><?//=dateToUtc($v)?><!--</p>-->
                                <p><?=$k?>:<?=$v?></p>
                                <?php break; ?>
                            <?php case 'subject':?>
                                <p><?=$k?>:<?=$v?></p>
                                <?php break; ?>
                            <?php case 'message':?>
                                <p><?=$k?>:<?=$v?></p>
                                <?php break; ?>
                            <?php case 'message':?>
                                <p><?=$k?>:<?=$v?></p>
                                <?php break; ?>
                            <?php endswitch; ?>
                        <?php endforeach;?>
                    </td>
                    <td>
                        <?php foreach($value as $k=>$v): ?>
                            <?php if($k == 'fileResize'):?>
                                <img src="<?='/www/'.$v?>" />
                            <?php endif;?>
                        <?php endforeach;?>
                    </td>
                </tr>
                <tr>

                </tr>
            </tbody>
        </table>


    <?php endforeach;?>
</div>
<div id = "pageNav">
    <?=$pag?>;
</div>

</body>
</html>