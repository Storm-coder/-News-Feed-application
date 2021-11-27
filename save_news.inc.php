<?php
/* файл подключается в "news.php" */

// принять данные из формы
	$title = $news -> clearStr($_POST['title']);
	$category = $news -> clearInt($_POST['category']);
	$description = $news -> clearStr($_POST['description']);
	$source = $news -> clearStr($_POST['source']);
	// возможно, нужно добавить $dt ( date() )

// проверить, была ли корректным образом отправлена HTML-форма
if (empty($title) || empty($description)){
	$errMsg = "Заполните все поля формы";
} else{
	// если данные из формы не сохранились (с помощью возвращаемого методом saveNews() значения проверьте, был ли запрос успешным)
	if (!$news -> saveNews($title, $category, $description, $source)){
		$errMsg = "Ошибка при добавлении новости";
	} else{ // а если все ок и данные сохранились - переадресовать
		header("Location: news.php");
		exit;
	}
}