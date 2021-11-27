<?php
// вызвать метод getNews()
$posts = $news -> getNews();

/**
 * С помощью возвращаемого методом значения проверить, был ли запрос успешным ()
 * ошибка (!$posts) здесь может быть в двух случаях: getNews() вернет false - ошибка; или пустой массив - ошибки нет (просто новостей нет)
 */
// если нет
if ($posts === false):
	$errMsg = "Произошла ошибка при выводе новостной ленты";
elseif (!count($posts)):
	$errMsg = "Новостей нет";
// если да
else:
	foreach ($posts as $item){
		$dt = date("d-m-Y H:m:s", $item["datetime"]);
		$desc = nl2br($item["description"]);
		echo <<<ITEM
		<h3>{$item['title']}</h3>
		<p>
			$desc<br>{$item["category"]} @ $dt
		</p>
		<p align='right'>
			<a href='news.php?del={$item['id']}'>Удалить</a>
		</p>
ITEM;

	}
endif;

// 1:39 (стр. 80)