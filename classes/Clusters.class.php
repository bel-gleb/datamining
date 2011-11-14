<?php
class Clusters
{
	private $_rownames = array();
	private $_colnames = array();
	private $_data = array();
	
	function __construct()
	{
	}
	
	public function loadData($filename)
	{
		$handle = fopen($filename, "r");
		$isHeaders = true;
		$this->_rownames = array();
		$this->_colnames = array();
		$this->_data = array();
		while (!feof($handle)) {
		    $string = fgets($handle, 1048576);
		    $string = trim($string);
		    if($isHeaders)
		    {
		    	$this->_colnames = explode("\t", $string);
		    	$isHeaders = false;
		    }
		    else
		    {
		    	if($string)
		    	{
			    	$stringChunks = explode("\t", $string);
			    	$this->_rownames[] = $stringChunks[0];
			    	$this->_data[] =  array_slice($stringChunks, 1);
		    	}
			}
		}

		fclose($handle);	
	}
	
	public function kClusters($k=4, Distance $distanceCalculator)
	{
		$rows = $this->_data;
	
		$ranges = array();
		//ищем максимумы и минимумы по всем столбцам
		foreach ($rows[0] as $i=>$value)
		{
			$colValues = array();
			foreach($rows as $row)
			{
				$colValues[] = $row[$i];
			}
			$ranges[$i][0]=min($colValues);
			$ranges[$i][1]=max($colValues);
		}
		
		//создаем k центроидов
		$clusters = array();
		for($j=0; $j<$k; $j++)
		{
			foreach ($rows[0] as $i=>$value)
			{
				$clusters[$j][$i]=$ranges[$i][0] + $this->rand()*($ranges[$i][1]-$ranges[$i][0]);
			}
		}
		
		
		$lastmatches = null;
		for($t=0; $t<50; $t++)
		{
			if(count($rows[0])==2) //для двухмерного случая визуализируем поле действий
			{
				for ($x=0; $x<=max($ranges[0][1], $ranges[0][1]); $x++)
				{
					for($y=0; $y<=max($ranges[1][1], $ranges[1][1]); $y++)
					{
						$freePoint = true;
						foreach ($clusters as $ci => $cluster)
						{
							if($cluster && round($cluster[0])==$x && round($cluster[1]) ==$y)
							{
								echo "\033[01;31m".$ci." \033[0m"; 
								$freePoint = false;
							}
						}
						foreach($rows as $ri => $row)
						{
							if($row[0]==$x && $row[1] ==$y)
							{
								//echo "\033[01;32m".$ri." \033[0m"; 
								echo "\033[01;32mX \033[0m"; 
								$freePoint = false;
							}
						}
						if($freePoint)
						{
							echo ". ";
						}
					}
					echo "\n";
				}
			}
			echo "Итерация ".$t."\n";
//print_r($clusters);			
			for($i=0; $i<$k; $i++)
			{
				$bestmatches[$i] = array();
			}

			//ищем для каждой точки ближайший центроид
			foreach($rows as $j => $row)
			{
				$bestmatch = 0;
				for($i=0; $i<$k; $i++)
				{
					$distance = $distanceCalculator->get($clusters[$i], $row);
					if($distance < $distanceCalculator->get($clusters[$bestmatch], $row))
					{
						$bestmatch = $i;
					}
		
				}

				$bestmatches[$bestmatch][] = $j;
			}
	
			if($bestmatches == $lastmatches)
			{
				break;
			}
			$lastmatches = $bestmatches;
//print_r($lastmatches);
			
			//перемещаем каждый центроид в центр приписанных к нему элементов
			for($i=0; $i<$k; $i++)
			{
//echo "cluster $i\n";				
				$avgs=array();
				if(count($bestmatches[$i]) > 0)
				{
					foreach($bestmatches[$i] as $rowid)
					{
						foreach ($rows[$rowid] as $m => $value)
						{
							if(!isset($avgs[$m]))
							{
								$avgs[$m] = 0;
							}
							$avgs[$m] += $value;
//echo "dimension $m = $avgs[$m]\n";
						}
					
					}

					$avgOut = array();
					foreach($avgs as $m => $avg)
					{
//echo "усредняем. сумма по $m = $avg, всего элементов ".count($bestmatches[$i])."\n";						
						$avgOut[$m] = $avg/count($bestmatches[$i]);
					}
					
			
//echo "центр кластера:\n";
//print_r($avgOut);						
				}
				else
				{
					//если центроид остался без точек, поместим его в произвольную
					foreach ($rows[0] as $m => $value)
					{
						//$avgOut[$m]=$ranges[$m][1]*10;
						$avgOut[$m] = $ranges[$m][0] + $this->rand()*($ranges[$m][1]-$ranges[$m][0]);
					}
				}
				$clusters[$i] = $avgOut;
			}
//echo "Координаты кластеров:\n";			
//print_r($clusters);
		}
		
		$outClusters = array();
		//выводим результат
		for($i=0; $i<$k; $i++)
		{
			if(count($bestmatches[$i]))
			{
				$clastWords = array();
				//echo $i.": ";
				
				foreach ($bestmatches[$i] as $bm)
				{
					//echo $this->_rownames[$bm].", ";
					$outClusters[$i]['rownames'][] = $this->_rownames[$bm];
					foreach($rows[$bm] as $m=>$value)
					{
						if($value)
						{
							if(!isset($clastWords[$this->_colnames[$m+1]]))
							{
								$clastWords[$this->_colnames[$m+1]] = 0;
							}
							$clastWords[$this->_colnames[$m+1]]+=$value;
						}
					}
				}
				arsort($clastWords);
				//print_r($clastWords);
				$outClusters[$i]['words'] = $clastWords;
				//echo "\n\n";
			}
		}
		return $outClusters;
	}
	
	public function weightData()
	{
		$data = $this->_data;
		$colSumms = array();
		foreach ($data[0] as $colN => $colVal)
		{
			$colSumm = 0;
			foreach($data as $row)
			{
				$colSumm+=$row[$colN];
			}
			$colSumms[$colN] = $colSumm;
		}
		$maxSumm = max($colSumms);
		
		$dataWeighted = array();
		foreach ($data[0] as $colN => $colVal)
		{
			foreach($data as $rowN => $row)
			{
				$dataWeighted[$rowN][$colN] = $data[$rowN][$colN] * round($maxSumm/$colSumms[$colN]);
			}
		}
		$this->_data = $dataWeighted;
	}
	
	public function removeDimensions($dimCount=3)
	{
	
		$data = $this->_data;
		$colSumms = array();
		foreach ($data[0] as $colN => $colVal)
		{
			$colSumm = 0;
			foreach($data as $row)
			{
				$colSumm+=$row[$colN];
			}
			$colSumms[$colN] = $colSumm;
		}
		arsort($colSumms);
		$biggestCols = array_slice(array_keys($colSumms), 0, $dimCount);
		foreach($biggestCols as $bc)
		{
			echo $this->_colnames[$bc]. ' ' .$colSumms[$bc]."\n";
		}
		$dataCleaned = array();
		foreach ($data[0] as $colN => $colVal)
		{
			foreach($data as $rowN => $row)
			{
				if(in_array($colN, $biggestCols))
				{
					$dataCleaned[$rowN][$colN] = 0;
				}
				else
				{
					$dataCleaned[$rowN][$colN] = $data[$rowN][$colN];
				}
			}
		}
		$this->_data = $dataCleaned;

	}

	public function removeEmptyRows()
	{
		$dataCleaned = array();
		$rownamesCleaned = array();
		$deleted = array();
		foreach ($this->_data as $rowN => $row)
		{
			$hasValues = false;
			foreach($row as $colN => $colVal)
			{
				if($colVal)
				{
					$hasValues = true;
					$dataCleaned[]=$row;
					$rownamesCleaned[]=$this->_rownames[$rowN];
					break;
				}
			}
			if(!$hasValues)
			{
				$deleted[]=$this->_rownames[$rowN];
			}
		}
		$this->_data = $dataCleaned;
		$this->_rownames = $rownamesCleaned;
		return $deleted;
	}	
	
	private function rand()
	{
		return mt_rand()/mt_getrandmax();
	}
	
}