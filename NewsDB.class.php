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
		return $this -> _db -> exec($sql);
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
}

// продолжить с лабары 2.4 (стр. 78) "Вывод записей"
