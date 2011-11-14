<?php
/**
 * Класс вычисления расстояния по Пирсону
 * Вычисляет корреляцию по Пирсону и преобразует её в расстояние - число от 0 до 1
 *
 */
class Distance_Pearson extends Distance
{
	public function get($x, $y)
	{
		$similarity = Similarity::pearson($x, $y);
				
		return (-1*$similarity+1)/2;
	}
}