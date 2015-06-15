<!DOCTYPE html>
<html>
<head>
<title>Форма</title>
<meta http-equiv="content-type" content="text/html" charset="utf-8">
<!-- jquery -->
<script src = "js/jquery-2.1.0.min.js"></script><!-- jquery -->
<script src = "lib/datepicker/js/bootstrap-datepicker.min.js"></script>
<script src = "js/FormValidate.js"></script>
<script src = "js/jquery.form.js"></script>

<link rel="stylesheet" type="text/css" href="css/style.css">
<link rel="stylesheet" type="text/css" href="lib/bootstrap/css/bootstrap.css" media='all'>
<link rel="stylesheet" type="text/css" href="lib/datepicker/css/bootstrap-datepicker.css" media='all'>
</head>
<body>

	<form id = "mail"  method = "post"  action = "handler.php" enctype="multipart/form-data">

		<div class = "form-group">
			<label for = "email">Email:</label>
			<input type = "text" class = "form-control email required" name = "email" placeholder = "Введите email">
		</div>
		
		<div class = "form-group">
			<label for = "subject">Subject:</label>
			<input type = "text" class = "form-control empty required" name = "subject" placeholder = "Введите заголовок письма">
		</div>
		
		<div class = "form-group">
			<label for = "message">Message:</label>
            <textarea class = "form-control empty required" name = "message" placeholder = "Введите содержание письма"></textarea>
		</div>

		<div class = "form-group">
			<label for = "inputFile">Выберите файл с компьютера:</label>
			<input type = "file" name = "file" class = "form-control" >
			<p class="help-block">Файлы формата .jpg, .gif, .png до 2 Mb</p>
		</div>

        <div class="input-group date">
            <input type="text" class="form-control" name = "data"><span class="input-group-addon"><i class="glyphicon glyphicon-th"></i></span>
        </div>

        <div class = "form-group" >
            <label class="radio-inline">
                <input type="radio"  name="options"  value = "html" checked> HTML
            </label>
            <label class="radio-inline">
                <input type="radio"  name="options" value = "plain text"> plain text
            </label>
        </div>
		
		<input type="submit" class="btn btn-success" value = "Отправить">
		
	</form>
    <a href = "/www/page.php">Посмотреть результаты</a>

</body>
</html>


<script>


    $('.input-group.date').datepicker({
        format: 'mm/dd/yyyy',
        startDate: '-0d'
    });


    var form = new FormValidate('mail','bootstrap');
    form.success = function(datajson){ }

    function toDate(date)
    {
        var d = new Date();
        if (d != 0) d.setTime(date*1000);
        d.toUTCString();
        var tmpy = d.getFullYear();
        var tmpm = d.getMonth();
        var tmpd = d.getDay();
        var tmph = d.getHours();
        var tmpmin = d.getMinutes();
        var tmps = d.getSeconds();
        if (tmpm < 10) tmpm = '0'+tmpm;
        if (tmpd < 10) tmpd = '0'+tmpd;
        if (tmph < 10) tmph = '0'+tmph;
        if (tmpmin < 10) tmpmin = '0'+tmpmin;
        if (tmps < 10) tmps = '0'+tmps;
        return tmph+':'+tmpmin+':'+tmps+' '+tmpd+'-'+tmpm+'-'+tmpy
    }

</script>

