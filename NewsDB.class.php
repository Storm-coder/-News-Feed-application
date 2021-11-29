<?php
// автозагрузка класса (interface INewsDB)
function my_autoloader($class) {
	require_once $class . '.class.php';
}

// в параметр передаем метод 'my_autoloader'
spl_autoload_register('my_autoloader');

// класс NewsDB реализующий интерфейс INewsDB
class NewsDB implements INewsDB{

	// константа класса (имя БД) Файл должен создаваться в корневойдиректории сайта!
	const DB_NAME = "../news.db";

	// константа класса, для хранения имени RSS-файла
	const RSS_NAME = "rss.xml";

	// константа класса, для хранения заголовка новостной ленты, например, "Последние новости"
	const RSS_TITLE = "Последние новости";

	// константа класса, для хранения ссылки на саму новостную ленту
	const RSS_LINK = "http://level3/news/news.php";

	// закрытое (private) свойство $_db для хранения экземпляра класса SQLite3
	private $_db = null; // если свойство не public - его начинают с нижнего подчеркивания

	// конструктор класса, в котором выполняется подключение к базе данных SQLite
	function __construct(){
		// создать БД
		$this -> _db = new SQLite3(self::DB_NAME); // присвоить свойству $_db значение, которое является экземпляром класса SQLite3 и выполнить SQL запросы для добавления таблиц
		// существует ли БД
		if(filesize(self::DB_NAME) == 0) { // если файл БД существует ( и файл равен 0 (0 байт) )
			// отлов ошибок
			try {
				// SQL запросы для добавления таблиц msgs и category
				$sql = "CREATE TABLE msgs(
						id INTEGER PRIMARY KEY AUTOINCREMENT,
						title TEXT,
						category INTEGER,
						description TEXT,
						source TEXT,
						datetime INTEGER
						)";
				// выполнить запрос
				if (!$this->_db->exec($sql)) // если запрос не случился
					throw new Exception($this -> _db ->lastErrorMsg()); // lastErrorMsg() - возвращает описание ошибки
				// а если запрос выполняется - продолжить
				$sql = "CREATE TABLE category(
						id INTEGER,
						name TEXT
						)";
				// выполнить запрос
				if (!$this->_db->exec($sql)) // если запрос не случился
					throw new Exception($this -> _db ->lastErrorMsg()); // lastErrorMsg() - возвращает описание ошибки

				$sql = "INSERT INTO category(id, name)
						SELECT 1 as id, 'Политика' as name
						UNION SELECT 2 as id, 'Культура' as name
						UNION SELECT 3 as id, 'Спорт' as name ";
				// выполнить запрос
				if (!$this->_db->exec($sql)) // если запрос не случился
					throw new Exception($this -> _db ->lastErrorMsg()); // lastErrorMsg() - возвращает описание ошибки

			} catch (Exception $e){
				/* $e -> getMessage(); */ // вывод ошибки (в лог, например) для разработчика
				echo "Err"; // вывод ошибки для юзера
			}
		}
	}

	// деструктор класса, в котором выполняется удаление экземпляра класса SQLite3
	function __destruct(){
		unset($this -> _db);
	}

	// если данный класс будет наследоваться? Надо обеспечить доступ на чтение значения свойства $_db классам-наследникам
	function __get($name){
		if ($name == "_db")
			return $this -> _db;
		throw new Exception("Unknow property");
	}

	// добавление новой записи в новостную ленту (результат выборки вернуть в виде массива)
	function saveNews($title, $category, $description, $source){
		$dt = time();
		// в поля таблицы msgs вставить значения
		$sql = "INSERT INTO msgs(
								title,
								category,
								description,
								source,
								datetime)
							VALUES(
									'$title',
									$category,
									'$description',
									'$source',
									$dt)"; // значения в кавычках '$title' - потому, что это строка (уточнения есть в SQL запросах для добавления таблиц msgs и category)
		// выполнить запрос
		$result = $this -> _db -> exec($sql);
		if (!$result)
			return false;
		$this->createRss(); // формирует RSS-документ
		return true;
	}

	/**
	 * промежуточный метод (чтобы вернуть массив в getNews())
	 * в этот метод передаем $result
	 * private - метод для внутреннего пользования
	 */
	private function db2Arr($data){
		$arr = [];
		// получаем массив массивов
		while ($row = $data -> fetchArray(SQLITE3_ASSOC)){
			$arr[] = $row;
		}
		return $arr;
	}

	// выборка всех записей из новостной ленты
	function getNews(){
		// выборка в обратном порядке (чтобы последняя новость показывалась первой)
		$sql = "SELECT msgs.id as id, title, category.name as category,
					   description, source, datetime
					FROM msgs, category
					WHERE category.id = msgs.category
					ORDER BY msgs.id DESC";
		// запрос
		$result = $this-> _db -> query($sql);
		// если запрос не случился
		if (!$result) return false;
		// вернуть результат выборки в виде массива (массив массивов)
		return $this -> db2Arr($result);
	}

	// удаление записи из новостной ленты
	function deleteNews($id){
		$sql = "DELETE FROM msgs WHERE id = $id";
		// запрос (return true/false)
		return $this -> _db -> exec($sql);
	}

	// вспомогательная функция (функция-полезняшка)
	function clearStr($data){
		$data = strip_tags($data);
		return $this -> _db -> escapeString($data);
	}

	// вспомогательная функция (функция-полезняшка)
	function clearInt($data){
		$data = strip_tags($data);
		return abs((int)$data);
	}

	// метод, который формирует RSS-документ (XML-файл)
	private function createRss(){
		// Создание объекта, экземпляра класса DomDocument
		$dom = new DomDocument("1.0","utf-8");
		// 2 строки кода - для форматирования, если их не будет, файл запишется в одну строку
		$dom->formatOutput = true;
		$dom->preserveWhiteSpace = false;
		// создаем корневой элемент (узел, корневой тег <rss>) )
		$rss = $dom->createElement("rss");
		// атрибут корневого элемента
		$version = $dom->createAttribute("version");
		$version->value = '2.0';
		// добавляем $version в "rss"
		$rss->appendChild($version);
		// сам корневой тег <rss> добавить к документу (к DOM)
		$dom->appendChild($rss);

		// создать узел (тег в xml-документе) "channel"
		$channel = $dom->createElement("channel");
		// создать узел "title" (self::RSS_TITLE - обращаемся к константе класса)
		$title = $dom->createElement("title", self::RSS_TITLE);
		// создать узел "link"
		$link = $dom->createElement("link", self::RSS_LINK);
		// добавить узел (тег) $title в "channel" ( $channel-> добавляет к себе $title )
		$channel->appendChild($title);
		// добавить узел (тег) $link в "channel"
		$channel->appendChild($link);
		// тег "channel" добавить в корневой элемент "rss" (в тег <rss>)
		$rss->appendChild($channel);

		// зачитать новости
		$lenta = $this->getNews();
		// новостей может не быть (или плохо все)
		if (!$lenta) return false;
		// если все ок - получить данные в виде массива из базы данных и дальнейшие действия производить в цикле
		foreach ($lenta as $news){
			// новый XML-элемент (тег/узел) item для очередной новости
			$item = $dom->createElement("item");
			// XML-элементы для всех данных новостной ленты (вместе с текстовыми узлами): title, link, description, pubDate, category; обернуть текст для элемента description секцией CDATA
			$title = $dom->createElement("title", $news['title']); // заголовок новостной ленты
			$category = $dom->createElement("category", $news['category']);
			$description = $dom->createElement("description");
			$cdata = $dom->createCDATASection($news['description']); // !
			$description->appendChild($cdata);
			$linkText = self::RSS_LINK . '?id=' . $news['id']; // фейковая ссылка, т.к. изначально нет ссылки на отдельную новость
			$link = $dom->createElement("link", $linkText);
			$dt = date('r', $news['datetime']);
			$pubDate = $dom->createElement("pubDate", $dt);
			// привязать созданные XML-элементы с данными к XML-элементу item
			$item->appendChild($title); // $title  добавить в корневой элемент "item" (в тег <item>)
			$item->appendChild($link);
			$item->appendChild($description);
			$item->appendChild($pubDate);
			$item->appendChild($category);
			// добавить узел (тег) item в "channel"  ( $channel-> добавляет к себе $item )
			$channel->appendChild($item);
		}
		// cохранить файл, имя файла - константа RSS_NAME
		$dom->save(self::RSS_NAME);
	}
}

