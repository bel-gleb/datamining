<?php
/**
 * Класс для выделения тегов-аббревиатур из текста
 * @author gbelogortcev
 *
 */
class TagsExtractor_Method_Abbr extends TagsExtractor_Method
{
	
	public function extract($text)
	{
		$text = trim($text);
		$this->initExtractor($text);
		if(!mb_strlen($text))
		{
			return true;
		}
		
		$words = explode(' ', $text);
		//находим аббревиатуры и места в тексте, на которых они стоят
		$abbrIndexes = array();
		for ($i=0; $i<count($words); $i++)
		{
			$word = $words[$i];
			if (
				mb_strlen($word)>1 //исключаем предлоги, стоящие в начале предложения
				&& intval($word)==0 //исключаем числа и вещи типа "30%"
				&& !(preg_match('/[[:upper:]]\./', $word)) //исключаем заглавные буквы с точками - инициалы (Ю. Хабермас)
				&& mb_strtoupper($word) == $word
				&& preg_match("/[[:upper:]]/", $word) //есть хотя бы одна заглавная _буква_ (защита от попадания вещей типа "+12")
				)
			{
				$this->foundTag($word, TagsExtractor_TagTypes::$ABBR);
				$abbrIndexes[] = $i;
			}
		}
		
		//вырезаем из текста найденные слова
		foreach($abbrIndexes as $index)
		{
			unset($words[$index]);
		}
		$this->_text = join(' ', $words);
		
		return $this->_extractedTags;
	}

}