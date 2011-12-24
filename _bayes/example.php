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

echo " ==[ NaiveBayes ]== \n";
$c = new Classifier_NaiveBayes(); //создаем
sampleTrain($c); //тренируем на тестовом наборе документов

// классифицируем новые документы
print $c->classify(explode(' ','quick rabbit'))."\n"; 
//$ good
print $c->classify(explode(' ','quick money'))."\n";
//$ bad

// устанавливаем порог: чтобы документ попал в категорию,
//вероятность для неё должна быть более чем в 3 раза выше, чем для остальных
$c->setThreshold('bad', 3);
print $c->classify(explode(' ','quick money'))."\n";
//$ unknown

//дополнительно тренируем классификатор
for($i=0; $i<10; $i++)
{
	sampleTrain($c);
}
print $c->classify(explode(' ','quick money'))."\n"; //теперь он сможет определить документ как "плохой"
//$ bad


/**
 * Пример использования классификатора, работающего по методу Фишера
 */

echo " ==[ Fisher ]== \n";
$c = new Classifier_Fisher(); //создаем
sampleTrain($c); //тренируем на тестовом наборе документов

// классифицируем новые документы
print $c->classify(explode(' ','quick rabbit'))."\n"; 
//$ good
print $c->classify(explode(' ','quick money'))."\n";
//$ bad

//Устанавливаем нижние границы вероятностей категорий.
//Если вероятность ниже границы, документ в категорию не попадет
$c->setMinimun('bad',0.8);
print $c->classify(explode(' ','quick money'))."\n";
//$ good
$c->setMinimun('good',0.5);
print $c->classify(explode(' ','quick money'))."\n";
//$ unknown