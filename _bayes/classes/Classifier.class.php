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
	 * @var array счетчики комбинаций признак/категория
	 */
	protected $fc = array();
	/**
	 * @var array счетчики документов в каждой категори
	 */
	protected $cc = array();


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

		if(isset($this->fc[$feature][$category]))
		{
				
			return $this->fc[$feature][$category];
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

		if(isset($this->cc[$category]))
		{
			return $this->cc[$category];
		}

		return 0;
	}


	/**
	 * Общее число образцов
	 *
	 */
	protected function totalCount()
	{
		return array_sum(array_values($this->cc));
	}

	/**
	 * Список всех категорий
	 *
	 */
	protected function categories()
	{
		return array_keys($this->cc);
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

		if(!isset($this->fc[$feature]))
		{
			$this->fc[$feature] = array();
		}

		if(!isset($this->fc[$feature][$category]))
		{
			$this->fc[$feature][$category] = 0;
		}

		$this->fc[$feature][$category]++;
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

		if(!isset($this->cc[$category]))
		{
			$this->cc[$category] = 0;
		}

		$this->cc[$category]++;
	}
}