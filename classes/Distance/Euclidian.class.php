<?php
/**
 * Класс вычисления евклидова расстояния
 * Минимум - 0, максимум не ограничен
 */
class Distance_Euclidian extends Distance
{
	public function get($x, $y)
	{
		$sumSq = 0;
		foreach($x as $i => $valX)
		{
			$sumSq += ($valX-$y[$i])*($valX-$y[$i]);
		}
		
		return sqrt($sumSq);
	}

}