<?php
// подключить файл с описанием класса NewsDB
require_once "NewsDB.class.php";

// объект $news, экземпляр класса NewsDB
$news = new NewsDB();

// для показа ошибок
$errMsg = "";

// проверить, была ли отправлена HTML-форма
if ($_SERVER['REQUEST_METHOD'] == 'POST')
    require_once "save_news.inc.php";

// подключить файл news\delete_news.inc.phpс кодом для обработки данных для удаления записи
// перед подключением убедиться в наличии параметра, который указывает на удаление записи
if (isset($_GET['del']))
	require_once "delete_news.inc.php";
?>

<!DOCTYPE html>
<html>
<head>
	<title>Новостная лента</title>
	<meta charset="utf-8" />
</head>
<body>
  <h1>Последние новости</h1>

  <?php
  if ($errMsg)
      echo "<h3>$errMsg</h3>";
  ?>

  <form action="<?= $_SERVER['PHP_SELF']; ?>" method="post">
    Заголовок новости:<br />
    <input type="text" name="title" /><br />
    Выберите категорию:<br />
    <select name="category">
      <option value="1">Политика</option>
      <option value="2">Культура</option>
      <option value="3">Спорт</option>
    </select>
    <br />
    Текст новости:<br />
    <textarea name="description" cols="50" rows="5"></textarea><br />
    Источник:<br />
    <input type="text" name="source" /><br />
    <br />
    <input type="submit" value="Добавить!" />
</form>

<?php
    require_once "get_news.inc.php";
?>

</body>
</html>