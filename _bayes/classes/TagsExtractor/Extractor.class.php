<?php
/**
 * Класс для выделения тегов из текстов.
 * Использует поочередно различные методы выделения, реализуя паттерн "Стратегия"
 * @author gbelogortcev
 *
 */
class TagsExtractor_Extractor
{
	/**
	 * Массив используемых методов выделения. Применяются в том порядке, в котором записаны в массив.
	 * Порядок имеет значение, потому что некоторые методы изменяют исходный текст.
	 * Самый неустойчивый метод - Мрофология, поэтому текст до него должен дойти максимально очищенным.
	 * @var array
	 */
	private $_methods = array('Abbr', 'Morphology', 'EngNames');

	/**
	 * Хэш найденных тегов в виде "тег - количество вхождений" 
	 * @var array
	 */
	private $_tags;
	
	/**
	 * Массив блоков, на которые разбивается текст знаками препинания
	 * @var array
	 */
	private $_textBlocks;
	
	/**
	 * Конструктор класса.
	 * @param Uniora_Logger $logger
	 */
	function __construct()
	{

	}
	
	/**
	 * Выледение тегов из текста
	 * @param string $text
	 */
	function extract($text)
	{
		$this->_tags = array();
		$this->_textBlocks = array();
		
		$text = $this->_cleanupText($text);
		$this->_generateTextBlocks($text);

		//применяем по очереди все методы
		foreach ($this->_methods as $method)
		{
			$extractorMethod = TagsExtractor_Method::getMethod($method);
		
			$textBlocks = $this->_textBlocks;
			for($i=0; $i<count($textBlocks); $i++)
			{
				$textBlock = $textBlocks[$i];
				//получаем теги
				$newTags = $extractorMethod->extract($textBlock);
				//и обработанный методом исходный текст
				$this->_textBlocks[$i] = $extractorMethod->getProcessedText();
				$this->_addTags($newTags);
			}
		}
		
		$this->_rankTags();
		
		return $this->_tags;
	}
	
	/**
	 * Добавление к уже собранным тегам новых
	 * 
	 * Если для тега с таким же текстом был задан ранее другой тип, он изменится на переданный
	 * (такая ситуация, скорее всего, будет очень редкой, поэтому более сложный вариант 
	 * разрешения конфликтов пока решено не придумывать)
	 * 
	 * @param array $newTags массив тегов вида "тег => array('count' => количество вхождений в текст, 'type' => тип тега"
	 */
	private function _addTags($newTags)
	{

		if(is_array($newTags))
		{
			foreach ($newTags as $tagName => $tagInfo)
			{
				
				if(!$this->_isTagValid($tagName))
				{
					continue;
				}
				
				$this->_tags[$tagName]['type'] = $tagInfo['type'];
				if(isset($this->_tags[$tagName]['count']))
				{
					$this->_tags[$tagName]['count'] += $tagInfo['count'];
				}
				else 
				{
					$this->_tags[$tagName]['count'] = $tagInfo['count'];
				}
			}
		}
	}
	
	/**
	 * Очистка текста от мусора (например, HTML)
	 * @param string $text
	 */
	private function _cleanupText($text)
	{
		$text = strip_tags($text);
		$text = preg_replace("/$/", ' ', $text);
		return $text; 
	}


	/**
	 * Разбиение текста на смысловые блоки
	 * @param string $text
	 */
	private function _generateTextBlocks($text)
	{
		
		$text = iconv('utf-8', 'windows-1251', $text); // потому что mb_preg_match_all нам разработчики PHP еще не сделали 
		preg_match_all('/(.*?)([.,?!;":()]|(\s-\s)|(\s-))/', $text, $matches);
		$textBlocks = $matches[1];
		foreach ($textBlocks as $block)
		{
			$block = iconv('windows-1251', 'utf-8', $block);
			$block = trim($block);
			$block = preg_replace('/\s+/', ' ', $block);
			$this->_textBlocks[] = $block;
		}
	}


	/**
	 * Проверка тега на валидность.
	 * Сюда можно помещать проверки для случаев, которые не получается отсеять
	 * в рамках штатных методов выделения тегов.
	 * Но делать это стоит только в самых крайних случаях.
	 * @param string $string
	 */
	private function _isTagValid($string)
	{
		//Защита от "Газпром"-ТНК-ТНК-ВР
		//Газпром вырезается при выделении его как карточки, -ТНК-ТНК-ВР остается как аббревиатура 
		if(preg_match('/^-/', $string))
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Фильтрация и ранжирование тегов
	 * Потом можно будет перенести в клиент, если разным клиентам понадобятся разные алгоритмы, но пока пускай будет тут
	 */
	private function _rankTags()
	{
		//Убираем все обычные слова, которые встречаются в тексте менее 2 раз
		$allTags = $this->_tags;
		foreach($allTags as $tagName=>$tag)
		{
			if($tag['type']==TagsExtractor_TagTypes::$WORD)
			{
				if($tag['count']<2)
				{
//					unset($this->_tags[$tagName]);
				}
			}
		}
		uasort($this->_tags, array($this, '_tagSort'));
	}
	
	/**
	 * Callback-функция сортировки тегов. Сортирует сначала по приоритету типа тега (карточки важнее простых слов и т.д.), потом - по количеству вхождений 
	 * @param array $a тег
	 * @param array $b тег
	 * @return number
	 */
	private function _tagSort($a, $b)
	{
		$typesPriority = array(
				TagsExtractor_TagTypes::$CARD,
				TagsExtractor_TagTypes::$ABBR,
				TagsExtractor_TagTypes::$PRESET,
				TagsExtractor_TagTypes::$ENGNAME,
				TagsExtractor_TagTypes::$PRNAME,
				TagsExtractor_TagTypes::$PHRASE,
				TagsExtractor_TagTypes::$WORD
			);
		
		$aPriority = 0;
		if(in_array($a['type'], $typesPriority))
		{
			$aPriority = count($typesPriority) - array_search($a['type'], $typesPriority);
		}
		
		$bPriority = 0;
		if(in_array($b['type'], $typesPriority))
		{
			$bPriority = count($typesPriority) - array_search($b['type'], $typesPriority);
		}
		
		if($aPriority > $bPriority)
		{
			return -1;
		}
		elseif($aPriority < $bPriority)
		{
			return 1;
		}
		
	    if ($a['count'] == $b['count'])
	    {
	        return 0;
	    }
	    return ($a['count'] > $b['count']) ? -1 : 1;
	}
}