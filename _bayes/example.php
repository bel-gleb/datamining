<?php
require_once __DIR__."/common.php";

function sampleTrain($classifier)
{
	$str = 'nobody owns the water';
	$classifier->train(explode(' ', $str), 'good');	
	
	$str = 'the quick rabbit jumps fences';
	$classifier->train(explode(' ', $str), 'good');
	
	$str = 'buy farmceuticals now';
	$classifier->train(explode(' ', $str), 'bad');	
	
	$str = 'make quick money in the online casino';
	$classifier->train(explode(' ', $str), 'bad');	

	$str = 'the quick brown fox jumps';
	$classifier->train(explode(' ', $str), 'good');	
}


/**
 * Пример использования наивного байесовского классификатора
 */

echo "-==[ NaiveBayes ]==-\n";

// Создаем классификатор
$c = new Classifier_NaiveBayes('example');

// Тренируем на тестовом наборе документов
sampleTrain($c);

// Классифицируем новые документы

$features = explode(' ','quick rabbit');
print $c->classify($features)."\n"; 
//$ good

$features = explode(' ','quick money');
print $c->classify($features)."\n";
//$ bad

// Займемся тонкой настройкой.
// Устанавливаем порог: чтобы документ попал в категорию,
// вероятность для неё должна быть более чем в 3 раза выше, чем для остальных
$c->setThreshold('bad', 3);
$features = explode(' ','quick money');
print $c->classify($features)."\n";
//$ unknown

// Опс. Данных оказалось недостаточно для классификации.

//дополнительно тренируем классификатор
for($i=0; $i<10; $i++)
{
	sampleTrain($c);
}
// Теперь он сможет определить документ как "плохой"
$features = explode(' ','quick money');
print $c->classify($features)."\n"; 
//$ bad

/**
 * Пример использования классификатора, работающего по методу Фишера
 */

echo "-==[ Fisher ]==-\n";

// Создаем классификатор
// Он подключится к той же БД и будет использовать уже накопленные данные
$c = new Classifier_Fisher('example');

// Классифицируем новые документы
print $c->classify(explode(' ','quick rabbit'))."\n"; 
//$ good
print $c->classify(explode(' ','quick money'))."\n";
//$ bad

// Займемся тонкой настройкой.
// Устанавливаем нижние границы вероятностей категорий.
// Если вероятность ниже границы, документ в категорию не попадет
$c->setMinimun('bad',0.8);
print $c->classify(explode(' ','quick money'))."\n";
//$ good
$c->setMinimun('good',0.5);
print $c->classify(explode(' ','quick money'))."\n";
//$ unknown

//Сбрасываем данные, чтобы при следующем запуске скрипта-примера классификатор учился заново.
$c->resetDb();