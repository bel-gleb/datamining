<?php
/**
 * Абстрактный класс способов (стратегий) выделения тегов из текста.
 * @author gbelogortcev
 *
 */
abstract class TagsExtractor_Method
{
	/**
	 * Найденные теги.
	 * @var array массив вида "тег => array('count' => количество вхождений в текст, 'type' => тип тега"
	 */
	protected $_extractedTags = array();
	
	/**
	 * Текст, из которого выделяются теги
	 * @var string
	 */
	protected $_text;
	
	
	/**
	 * Фабричный метод, создающий конкретные классы способов выделения тегов
	 * @param strimg $methodName название метода
	 * @return object
	 */
	public static function getMethod($methodName) 
	{
		$className = "TagsExtractor_Method_".$methodName;
		$method = new $className();
		return $method;
	}
	
	/**
	 * Выделение тегов из текста
	 * @param string $text текст для обработки
	 */
	abstract function extract($text);
	
	/**
	 * Получение исходного текста, обработанного методом
	 */
	function getProcessedText()
	{
		return $this->_text;
	}

	/**
	 * Инициализация объекта выделения тегов
	 * 
	 * @param string $text текст, из которого будут выбираться теги
	 */
	protected function initExtractor($text='')
	{
		$this->_extractedTags = array();
		$this->_text = $text;	
	}
	
	/**
	 * Добавление найденного тега к списку тегов
	 * 
	 * Если для тега с таким же текстом был задан ранее другой тип, он изменится на переданный
	 * (такая ситуация, скорее всего, будет очень редкой, поэтому более сложный вариант 
	 * разрешения конфликтов пока решено не придумывать)
	 * 
	 * @param string $tag
	 * @param string $type тип тега (карточка, аббревиатура и т.д.)
	 */
	protected function foundTag($tag, $type)
	{
		if(!$tag)
		{
			throw new Exception('Не указан тег для добавления');
		}
		if(!$tag)
		{
			throw new Exception('Не указан тип тега');
		}
		
		if(isset($this->_extractedTags[$tag]))
		{
			$this->_extractedTags[$tag]['count']++;
			$this->_extractedTags[$tag]['type'] = $type;
		}
		else 
		{
			$this->_extractedTags[$tag]['count'] = 1;
			$this->_extractedTags[$tag]['type'] = $type;
		}
	}

}