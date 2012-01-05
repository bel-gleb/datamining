<?php
/**
 * Класс для выделения из текста английских названий
 * @author gbelogortcev
 *
 */
class TagsExtractor_Method_EngNames extends TagsExtractor_Method
{
	public function extract($text)
	{
		$text = trim($text);
		$this->initExtractor($text);
		if(!mb_strlen($text))
		{
			return true;
		}
		
	    $text = preg_replace_callback('/([A-Z]+[a-z._\/\\-\s]+)+/', array(&$this, 'foundName'), $text);
		$text = preg_replace('/\s+/', ' ', $text);

		$this->_text = $text;
		
		return $this->_extractedTags;
	}
	
	private function foundName($matches)
	{
		$tag = trim($matches[0]);
		if(mb_strlen($tag)>1)
		{
			$this->foundTag($tag, TagsExtractor_TagTypes::$ENGNAME);
		}
		return '';
	}

}