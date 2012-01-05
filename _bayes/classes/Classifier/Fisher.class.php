<?php
/**
 * Классификатор, использующий метод Фишера
 * 
 * @author Gleb Belogortcev (belgleb@gmail.com)
 *
 */
class Classifier_Fisher extends Classifier
{
	
	/**
	 * Нижние границы вероятностей для категорий
	 * @var array
	 */
	private $minimums = array();
	
	
	/**
	 * Определение категории для набора признаков
	 * 
	 * @param array $features
	 * @param string $default категория по умолчанию
	 * @return string категория
	 */
	public function classify($features, $default='unknown')
	{
		if(!is_array($features))
		{
			throw new Exception('No features given');
		}
		
		//цикл для поиска наилучшего результата
		$max = 0;
		$best = $default;
		foreach ($this->categories() as $c)
		{
			$p = $this->fisherProb($features, $c);
			//проверяем, что значение больше минимума
			if($p>$this->getMinimun($c) && $p>$max)
			{
				$max = $p;
				$best = $c;
			}
		}
		
		return $best;
	}
	
	/**
	 * Вычисление вероятности попадания доумента в категорию
	 * 
	 * @param array $features
	 * @param string $category
	 * @return float
	 */
	public function fisherProb($features, $category)
	{
		if(!is_array($features) || !$category)
		{
			throw new Exception('No features or category given');
		}
		
		//перемножить все вероятности
		$p=1;
		foreach ($features as $f)
		{
			$p *= $this->weightedProb($f, $category, 'сProb');
		}
		
		//взять натуральный логарифм и умножить на -2
		$fScore = -2*log($p);
		
		//для получения вероятности пользуемся обратной функцией хи-кватрат
		return $this->invchi2($fScore, count($features)*2);
	}

	/**
	 * Установка нижней границы вероятности попадания в категорию
	 * Чем выше граница, тем меньше документов попадет в категорию.
	 * 
	 * @param string $category
	 * @param float $minimum
	 */
	public function setMinimun($category, $minimum)
	{
		if(!$category || !$minimum)
		{
			throw new Exception('No category or minimum given');
		}
		
		$this->minimums[$category] = $minimum;
	}

	/**
	 * Получение нижней границы вероятности попадания в категорию
	 * 
	 * @param string $category
	 * @return float
	 */	
	public function getMinimun($category)
	{
		if(!$category)
		{
			throw new Exception('No category given');
		}
		
		if(isset($this->minimums[$category]))
		{
			return $this->minimums[$category];
		}
		
		return 0;
	}	
	
	/**
	 * Вычисление вероятности того, что образец с указанным признаком принадлежит указанной категории
	 * в предположении, что в каждой категории будет одинаковое число образцов
	 * 
	 * @param string $feature
	 * @param string $category
	 * @return float
	 */
	public function сProb( $feature, $category)
	{
		if(!$feature || !$category)
		{
			throw new Exception('No feature or category given');
		}
		//частота появления данного признака в данной категории
		$clf = $this->fProb($feature, $category);
		if($clf==0)
		{
			return 0;
		}
		
		//частота появления данного признака во всех категориях
		$freqsum = 0;
		foreach($this->categories() as $c)
		{
			$freqsum += $this->fProb($feature,$c);
		}
		
		//Вероятность равна частоте появления в данной категории, поделенной на частоту появления во всех
		$p = $clf/$freqsum;
		
		return $p;		
	}
	
	/**
	 * Обратная функция хи-квадрат
	 */
	private function invchi2($chi, $df)
	{
		$m = $chi/2;
		$term = exp(-1*$m);
		$sum = $term;
		for($i=1; $i<floor($df/2); $i++)
		{
			$term *= $m/$i;
			$sum += $term;
		}
		return min($sum, 1);
	}
	

}