<?php
/**
 * Класс вычисления расстояния по Танимото
 * Минимум - 0, максимум не ограничен
 */
class Distance_Tanimoto extends Distance
{
	public function get($x, $y)
	{
		return 1-$this->similarity($x, $y);
	}

	private function similarity($x, $y)
	{
		$c1=0;
		$c2=0;
		$shr=0;
		
		for($i=0; $i<count($x); $i++)
		{
			if($x[$i])
			{
				$c1++;
			}
			if($y[$i])
			{
				$c2++;
			}
			if($x[$i] && $y[$i])
			{
				$shr++;
			}
		}

		return ($shr/($c1+$c2-$shr));
	}
}