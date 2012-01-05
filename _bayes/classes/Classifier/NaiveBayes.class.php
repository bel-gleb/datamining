<?php
/**
 * Наивный байесовский классификатор
 * 
 * @author Gleb Belogortcev (belgleb@gmail.com)
 *
 */
class Classifier_NaiveBayes extends Classifier
{
	/**
	 * @var array пороги вероятностей попадания в категорию
	 */
	protected $thresholds = array();
	
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
		
		$probs = array();
		//Найти категорию с максимальной вероятностью
		$max = 0;
		$best = '';
		foreach ($this->categories() as $cat)
		{
			$probs[$cat] = $this->prob($features, $cat);

			if($probs[$cat]>$max)
			{
				$max = $probs[$cat];
				$best = $cat;
			}
		}

		//убедиться, что найденная вероятность больше чем threshold*следующая по величине
		foreach($probs as $cat=>$catProb)
		{
			if($cat==$best)
			{
				continue;
			}
			
			if( $catProb * $this->getThreshold($best) > $probs[$best] )
			{
				return $default;
			}
			
			return $best;
		}
	}
	
	/**
	 * Установка порога вероятности попадания в категорию
	 * Чем больше порог, тем сильнее должна отличаться вероятность попадания документа
	 * в данную категорию от вероятностей попадания в остальные, чтобы классификатор
	 * отнес его к ней.
	 * 
	 * @param string $category
	 * @param float $threshold
	 */
	public function setThreshold($category, $threshold)
	{
		if(!$category || !$threshold)
		{
			throw new Exception('No category or threshold given');
		}
		
		$this->thresholds[$category] = $threshold;
	}

	/**
	 * Получение порога вероятности попадания в категорию
	 * 
	 * @param string $category
	 * @return float
	 */	
	public function getThreshold($category)
	{
		if(!$category)
		{
			throw new Exception('No category given');
		}
		
		if(isset($this->thresholds[$category]))
		{
			return $this->thresholds[$category];
		}
		
		return 1;
	}
	
	/**
	 * Вычисление вероятности попадания доумента в категорию
	 * 
	 * @param array $features
	 * @param string $category
	 * @return float
	 */
	public function prob($features, $category)
	{
		if(!is_array($features) || !$category)
		{
			throw new Exception('No features or category given');
		}
		
		$catprob = $this->catCount($category)/$this->totalCount();
		$docprob = $this->docprob($features, $category);
		return $docprob*$catprob;
	}
	
	/**
	 * Вычисление Pr(Документ|Категория)
	 * 
	 * @param array $features
	 * @param string $category
	 * @return float
	 */
	protected function docprob($features, $category)
	{
		if(!is_array($features) || !$category)
		{
			throw new Exception('No features or category given');
		}
		
		$p=1;
		foreach ($features as $f)
		{
			$p *= $this->weightedProb($f, $category, 'fProb');
		}
		return $p;
	}

}