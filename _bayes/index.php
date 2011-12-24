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

$c = new Classifier_NaiveBayes();
sampleTrain($c);

//print $c->сProb('quick', 'good')."\n";
print $c->classify(explode(' ','quick rabbit'))."\n";
print $c->classify(explode(' ','quick money'))."\n";
$c->setThreshold('bad',3);
print $c->classify(explode(' ','quick money'))."\n";
