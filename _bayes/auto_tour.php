<?php
/*
 * Пример с реальными данными.
 * Берем RSS-ленты: часть с новостями про автомобили, часть - с новостями туризма.
 * Из каждой RSS выбираем первые несколько новостей в качестве тестовых,
 * остальные - в качестве обучающих. 
 * На обучающих обучаем классификатор, а на тестовых проверяем,
 * сможет ли он правильно определить тему новости.
 *  */

require_once __DIR__."/common.php";

define('NUM_DOCS_TO_CLASSIFY', 2);
define('PROJECT_NAME', 'auto_tour');

$extractor = new TagsExtractor_Extractor();

$c = new Classifier_NaiveBayes(PROJECT_NAME);
$c->resetDb();

$testDocs = array(); // тут будет массив тестовых документов
$trainDocs = array(); // а тут - массив документов для обучения

// Источники
$sources = array(
	array('category' => 'tourism', 'file' => 'http://static.feed.rbc.ru/rbc/internal/rss.rbc.ru/turist.ru/news.rss'),
	array('category' => 'tourism', 'file' => 'http://news.turizm.ru/news.rss'),
//	array('category' => 'tourism', 'file' => 'http://www.votpusk.ru/news.xml'),	
	array('category' => 'tourism', 'file' => 'http://news.yandex.ru/travels.rss'),	
	array('category' => 'tourism', 'file' => 'http://atworld.ru/rss.xml'),	
	array('category' => 'tourism', 'file' => 'http://maxtour.com.ua/index.php?format=feed&type=rss'),	

	array('category' => 'auto', 'file' => 'http://static.feed.rbc.ru/rbc/internal/rss.rbc.ru/autonews.ru/mainnews.rss'),
	array('category' => 'auto', 'file' => 'http://static.feed.rbc.ru/rbc/internal/rss.rbc.ru/autonews.ru/comments.rss'),
	array('category' => 'auto', 'file' => 'http://static.feed.rbc.ru/rbc/internal/rss.rbc.ru/autonews.ru/luxury_cars.rss'),
	array('category' => 'auto', 'file' => 'http://news.auto.ru/rss/category_rusnews.rss'),
	array('category' => 'auto', 'file' => 'http://news.yandex.ru/auto.rss'),	
	array('category' => 'auto', 'file' => 'http://autorambler.ru/journalrss/'),		
	array('category' => 'auto', 'file' => 'http://www.newsboy.ru/main/avtodelo/rss.xml'),
	array('category' => 'auto', 'file' => 'http://avtoobzor.info/autonewsrss.xml'),
	array('category' => 'auto', 'file' => 'http://steer.ru/export/index.rdf'),
	
		
	
);

// Сформируем массивы документов
echo "Получаем новости...\n";
$fCount = array();
foreach ($sources as $source)
{
	echo $source['file']."\n";
	$xml = simplexml_load_file($source['file']);
	$i=0;
	foreach ($xml->channel->item as $item)
	{
		$text = strip_tags($item->title.". \n".$item->description);

		$features = array_keys($extractor->extract($text));
		
		if(!isset($fCount[$source['category']]))
		{
			$fCount[$source['category']] = 0;
		}
		$fCount[$source['category']] += count($features);
		
		if($i < NUM_DOCS_TO_CLASSIFY) // N первых документов будут тестовыми
		{
			$testDocs[] = array('features' => $features, 'answer' => $source['category'], 'orig_text' => $text);
		}
		else // остальные - обучающими
		{
			$trainDocs[$source['category']][] = $features;
		}
		$i++;
	}
	
}

echo "Количество свойств в категориях:\n"; // чем ближе числа по разным категориям, тем лучше
foreach ($fCount as $category => $count)
{
	 echo $category.': '.$count."\n";
}

// Тренируем на тестовом наборе документов
echo "Идёт процесс обучения...";
foreach($trainDocs as $category	=> $docs)
{
	echo "\n".'Новостей в категории '.$category.': '.count($docs)."\n";
	foreach($docs as $features)
	{
		echo '.';
		$c->train($features, $category);
	}
}
echo "\n\n";

// Классифицируем новые документы
echo "Классифицируем новые документы\n";

// Можно поиграть с порогами
foreach($c->categories() as $category)
{
	//$c->setThreshold($category, 1.2);
}

$errors = 0;
$success = 0;
$unknown = 0;
foreach($testDocs as $doc)
{
	echo 'Текст: ' . $doc['orig_text'] . "\n"; 
	$classifierAnswer = $c->classify($doc['features']);
	echo 'Ответ классификатора: ' . $classifierAnswer . "\n"; 
	echo 'Правильный ответ: ' . $doc['answer'] . "\n";
	if( $doc['answer'] == $classifierAnswer )
	{
		echo ':)';
		$success++;
	}
	else if($classifierAnswer == 'unknown')
	{
		echo ':|';
		$unknown++;
	}
	else
	{
		echo ':(';
		$errors++;
	}
	echo "\n\n";
}
echo "Итого \nПравильно: ".$success."\nНеправильно: ".$errors."\nНе определил: ".$unknown;
echo "\n\n";