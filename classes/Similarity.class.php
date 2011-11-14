<?php
class Similarity
{
	public static function pearson($x, $y)
	{
		$n = count($x);

		$sumX = array_sum($x);
		$sumY = array_sum($y);

		$sumXSq = 0;
		$sumYSq = 0;
		$pSum = 0;
		for($i = 0; $i <= $n-1; $i++)
		{
			$sumXSq += $x[$i]*$x[$i];
			$sumYSq += $y[$i]*$y[$i];
			$pSum += $x[$i]*$y[$i];
		}

		$num = $pSum-(($sumX*$sumY)/$n);

		$den = sqrt( ( $sumXSq-pow($sumX, 2)/$n ) * ( $sumYSq-pow($sumY, 2)/$n ) );

		if($den == 0) return 0;
		
		$r = $num/$den;

		return $r;
	}
	
	public static function tanimoto($x, $y)
	{
		$nX = 0;
		$nY = 0;
		$nC = 0;
		foreach($x as $i => $val)
		{
			if($val)
			{
				$nX ++;
			}
			if($y[$i])
			{
				$nY ++;
			}
			if($val && $y[$i])
			{
				$nC++;
			}
		}
		
		return ($nC/($nX+$nY-$nC));
	}
	
	
	public static function dst_euclidian($x, $y)
	{
		$sumSq = 0;
		foreach($x as $i => $valX)
		{
			$sumSq += ($valX-$y[$i])*($valX-$y[$i]);
		}
		
		return sqrt($sumSq);
	}		
}