<?php
/**
 * Класс классификатора
 *
 * @author Gleb Belogortcev (belgleb@gmail.com)
 *
 */
class Classifier
{
	
	/**
	 * @var strin имя (ник) проекта
	 */
	protected $projectName = '';
	
	/**
	 * @var PDO объект для работы с БД
	 */
	protected $db;

	/**
	 * Создание классификатора
	 * 
	 * @param string $projectName "ник" проекта по классификации, 
	 * 								по нему при следующих запусках можно получить 
	 * 								накопленные в ходе работы данные о признаках
	 */
	public function __construct($projectName)
	{
		if(!$projectName)
		{
			throw new Exception('No project name given');
		}
		
		$this->initDb('$projectName');
	}

	/**
	 * Обучение классификатора
	 *
	 * @param array $features
	 * @param string $category
	 */
	public function train($features, $category)
	{
		if(!is_array($features) || !$category)
		{
			throw new Exception('No features or category given');
		}

		foreach($features as $feature)
		{
			$this->incF($feature, $category);
		}

		$this->incC($category);
	}



	/**
	 * Вероятность того, что слово принадлежит категории (Pr(Слово|Категория))
	 *
	 * @param string $feature
	 * @param string $category
	 * @return float
	 */
	public function fProb($feature, $category)
	{
		if(!$feature || !$category)
		{
			throw new Exception('No feature or category given');
		}

		if($this->catCount($category)==0)
		{
			return 0;
		}

		$pr = $this->fCount($feature, $category)/$this->catCount($category);

		return $pr;
	}


	/**
	 * Взвешенная вероятность того, что слово принадлежит категории
	 *
	 * @param string $feature
	 * @param string $category
	 * @param string $probeFunction
	 * @param float $weight
	 * @param float $assumedProb
	 * @return float
	 */
	public function weightedProb($feature, $category, $probeFunction, $weight=1, $assumedProb=0.5)
	{
		if(!$feature || !$category || !$probeFunction)
		{
			throw new Exception('No feature, category or probe function given');
		}

		$basicProb = $this->$probeFunction($feature, $category);

		//Сколько раз этот признак встречался во всех категориях 
		$totals = 0;
		foreach($this->categories() as $cat)
		{
			$totals += $this->fCount($feature,$cat);
		}

		//Вычислить средневзвешенное значение
		$bp = (($weight*$assumedProb)+($totals*$basicProb))/($weight+$totals);

		return $bp;
	}

	/**
	 * Сколько раз признак появлялся в данной категории
	 *
	 * @param string $feature
	 * @param string $category
	 * @return int
	 */
	public function fCount($feature, $category)
	{
		if(!$feature || !$category)
		{
			throw new Exception('No feature or category given');
		}

		$sql = 'SELECT count FROM fc WHERE feature=:f AND category=:cat';
		$sth = $this->db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		$sth->execute(array(':f' => $feature, ':cat' => $category));
		$countRow = $sth->fetch();
		
		if($countRow)
		{
			return $countRow['count'];
		}

		return 0;
	}


	/**
	 * Сколько образцов отнесено к данной категории
	 *
	 * @param string $category
	 * @return int
	 */
	public function catCount($category)
	{
		if(!$category)
		{
			throw new Exception('No category given');
		}
		
		$sql = 'SELECT count FROM cc WHERE category=:cat';
		$sth = $this->db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		$sth->execute(array(':cat' => $category));
		$countRow = $sth->fetch();
		
		if($countRow)
		{
			return $countRow['count'];
		}

		return 0;
	}


	/**
	 * Общее число образцов
	 *
	 */
	public function totalCount()
	{
		$sql = 'SELECT sum(count) FROM cc';
		$sth = $this->db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		$sth->execute();
		$totalCount = $sth->fetch();

		if($totalCount[0])
		{
			return $totalCount[0];
		}
		
		return 0;
	}

	/**
	 * Список всех категорий
	 *
	 */
	public function categories()
	{
		$sql = 'SELECT category FROM cc';
		$sth = $this->db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		$sth->execute();
		$categories = $sth->fetchAll(PDO::FETCH_COLUMN);

		return array_values($categories);
	}

	/**
	 * Увеличить счётчик признак/категория
	 *
	 * @param string $feature
	 * @param string $category
	 */
	protected function incF($feature, $category)
	{
		if(!$feature || !$category)
		{
			throw new Exception('No feature or category given');
		}
		
		$count = $this->fCount($feature, $category);
		if($count == 0)
		{
			$sql = 'INSERT INTO fc values (:f,:cat, 1);';
			$sth = $this->db->prepare($sql);
			$sth->execute(array(':f' => $feature, ':cat' => $category));
		}
		else
		{
			$sql = 'UPDATE fc SET count =:count WHERE feature=:f AND category=:cat;';
			$sth = $this->db->prepare($sql);
			$sth->execute(array(':count' => $count+1, ':f' => $feature, ':cat' => $category));
		}

	}

	/**
	 * Увеличить счётчик применений категории
	 *
	 * @param string $category
	 */
	protected function incC($category)
	{
		if(!$category)
		{
			throw new Exception('No category given');
		}
		
		$count = $this->catCount($category);
		if($count==0)
		{
			$sql = 'INSERT INTO cc values (:cat, 1);';
			$sth = $this->db->prepare($sql);
			$sth->execute(array(':cat' => $category));
		}
		else
		{
			$sql = 'UPDATE cc SET count =:count WHERE category=:cat;';
			$sth = $this->db->prepare($sql);
			$sth->execute(array(':count' => $count+1, ':cat' => $category));
		}

	}
	
	/**
	 * Подключение к базе с ранее накопленными данными о признаках
	 * или создание новой, если еще нет.
	 * 
	 * @param string $projectName "ник" проекта по классификации, 
	 * 								по нему можно потом получить данные из базы
	 */
	protected function initDb($projectName)
	{
		if(!$projectName)
		{
			throw new Exception('No project name given');
		}
		
		$this->projectName = $projectName;
		$this->db = new PDO('sqlite:'.PROJECT_ROOT.'/db/'.$this->projectName.'.db');
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		// таблица со счётчиками комбинаций признак-категория
		$this->db->exec('CREATE TABLE IF NOT EXISTS 
							fc(feature varchar(255), category varchar(255), count int,
							CONSTRAINT uc_fc UNIQUE (feature, category) ON CONFLICT IGNORE
							)');
		// таблица со счетчиками документов в каждой категори
		$this->db->exec('CREATE TABLE IF NOT EXISTS 
							cc(category varchar(255), count int,
							PRIMARY KEY (category) ON CONFLICT IGNORE
							)');	
		return true;
	}
	
	/**
	 * Сбросить все накопленные в рамках проекта данные о признаках
	 */
	public function resetDb()
	{
		unset($this->db);
		unlink(PROJECT_ROOT.'/db/'.$this->projectName.'.db');
		$this->initDb($this->projectName);
	}
}