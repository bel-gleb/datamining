<?php

include_once('./common.php');


$newsHeadersIn = './data/news_headers.201110.txt';
/*
$minNewsForWord = 10;

$stemmer = new Stemmer();


$newsHeadersOut = './data/news_headers.data.txt';

echo "\n\n\n========= Counting words =============\n\n\n";

$handle = fopen($newsHeadersIn, "r");
$fullWordsList = array();
while (!feof($handle)) {
    $string = fgets($handle, 1048576);
    $string = trim($string);
    if($string)
    {
	    echo $string."\n";
	    list($newsId, $header) = explode(":", $string, 2);
	    $stemmedHeader = $stemmer->stemText($header);
		$headerWords = array_values($stemmedHeader);
		foreach($headerWords as $hWord)
		{
			if(mb_strlen($hWord)>1)
			{
				if(!isset($fullWordsList[$hWord]))
				{
					$fullWordsList[$hWord] = 1;
				}
				else
				{
					$fullWordsList[$hWord]++;
				}
			}
		}
    }
}
fclose($handle);

$fullWordsListOut = array();
foreach ($fullWordsList as $word => $num)
{
	if($num >= $minNewsForWord)
	{
		$fullWordsListOut[] = $word;
	}
}

echo "\n\n\n========= Wrighting matrix =============\n\n\n";

$handle = fopen($newsHeadersIn, "r");
$handleOut = fopen($newsHeadersOut, "w+");

fwrite($handleOut, 'news_id'."\t".join("\t", $fullWordsListOut)."\n");

while (!feof($handle)) {
    $string = fgets($handle, 1048576);
    $string = trim($string);
    if($string)
    {
	    echo $string."\n";
	    list($newsId, $header) = explode(":", $string, 2);
	    $stemmedHeader = $stemmer->stemText($header);
		$headerWords = array_values($stemmedHeader);
		$headerWordsOut = array();
		foreach($headerWords as $hWord)
		{
			if(mb_strlen($hWord)>1)
			{
				$headerWordsOut[]=$hWord;
			}
		}
		
		fwrite($handleOut, $newsId."\t");
		$arrWC = array();
		foreach($fullWordsListOut as $fw)
		{
			$arrWC[] = (int) in_array($fw, $headerWordsOut);
			
		}
		fwrite($handleOut, join("\t", $arrWC));
		fwrite($handleOut, "\n");
    }
}
fclose($handle);
fclose($handleOut);
*/

echo "\n\n\n========= Reading headers =============\n\n\n";
$newsHeaders = array();
$handle = fopen($newsHeadersIn, "r");
while (!feof($handle)) {
    $string = fgets($handle, 1048576);
    $string = trim($string);
    if($string)
    {
	    list($newsId, $header) = explode(":", $string, 2);
	    $header = strip_tags($header);
	    $newsHeaders[$newsId] = $header;
    }
}
fclose($handle);



echo "\n\n\n========= Clustering =============\n\n\n";
$distance = new Distance_Euclidian();

$clusters = new Clusters();
echo "Loading...\n";
$clusters->loadData('./data/news_headers.data.tags.short.txt');

echo "\n\n\n========= Removing empty rows =============\n\n\n";
$removedRows = $clusters->removeEmptyRows();
foreach ($removedRows as $rowname)
{
	//echo $rowname.' '.$newsHeaders[$rowname]."\n";
}
echo "Итого: ".count($removedRows)."\n\n";

//echo "Calculating weights\n";
//$clusters->weightData();
//$d = 10;
//echo "Remove ".$d." biggest dimensions\n";
//$clusters->removeDimensions($d);

$foundClusters = $clusters->kClusters(2000, $distance);
echo "\n\n\n\n\n\n\n";
foreach($foundClusters as $clusterId => $clusterData)
{
	echo '======[ Кластер '.$clusterId.' ('.count($clusterData['rownames']).' шт.)  ]======'."\n";
	foreach($clusterData['rownames'] as $rowname)
	{
		echo $newsHeaders[$rowname]."\n";
	}
	print_r($clusterData['words']);
}