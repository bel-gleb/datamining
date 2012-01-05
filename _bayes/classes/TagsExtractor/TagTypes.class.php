<?php
/**
 * Класс данных - типов тегов
 * @author gbelogortcev
 *
 */
class TagsExtractor_TagTypes
{
	/**
	 * Обычное слово
	 */
	static $WORD = 'WORD';
	/**
	 * Имя собственное
	 */
	static $PRNAME = 'PRNAME';
	/**
	 * Словосочетание
	 */
	static $PHRASE = 'PHRASE';
	/**
	 * Аббревиатура
	 */
	static $ABBR = 'ABBR';
	/**
	 * Карточка
	 */
	static $CARD = 'CARD';
	/**
	 * Слово на английском
	 */
	static $ENGNAME = 'ENGNAME';
	/**
	 * Заранее заданный тег
	 */
	static $PRESET = 'PRESET';
}