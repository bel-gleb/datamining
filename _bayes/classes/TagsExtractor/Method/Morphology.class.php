<?php
/**
 * Класс для выделения тегов из текста на основе морфологии
 * @author gbelogortcev
 *
 */
class TagsExtractor_Method_Morphology extends TagsExtractor_Method
{
	/**
	 * Объект морфологического анализатора
	 * @var phpMorphy
	 */
	private $_morphy;
	
	/**
	 * Код прилагательного согласно phpMorphy
	 */
	private $_CODE_ADJECTIVE = 'П';
	
	/**
	 * Код существительного согласно phpMorphy
	 */
	private $_CODE_NOUN = 'С'; //это русская (!) "С"
	
	public function extract($text)
	{
		$text = trim($text);
		$this->initExtractor($text);
		if(!mb_strlen($text))
		{
			return true;
		}
		
		$words = explode(' ', $text);
		
		$wordsOrig = $words;
		
		// Переведем слова в верхний регистр, чтобы их мог есть _morphy.
		// Если сделать с помощью &$word, массив в некоторых случаях будет портиться:
		// весто последнего элемента дублируется предпоследний
		$wordsUpper = array();
		foreach ($words as $word)  
		{
			$wordsUpper[] = mb_strtoupper($word);
		}
		$words = $wordsUpper;

		//сначала соберем грамматическую информацию о словах
		// lemm - начальная форма
		// pos - part_of_speech, часть речи
		// orig - исходное написание слова, как было в тексте
		$wordsInfo = array();

		foreach ($words as $i=>$word)
		{
			$wordOriginal=''; //для первого слова оригинал не сохраняем: он нужен для вычисления имен собственных, а это могло быть начало преложения
			if($i!=0)
			{
				$wordOriginal = $wordsOrig[$i];
			}	

			$wordLemm = $this->_morphy->lemmatize($word, phpMorphy::NORMAL);
			if($wordLemm && count($wordLemm) == 1) 
			{
				$pos = $this->_morphy->getPartOfSpeech($word, phpMorphy::NORMAL);

				if($pos[0])
				{
					$wordsInfo[$word] = array('lemm'=>$wordLemm[0], 'pos'=>$pos[0], 'orig'=> $wordOriginal);
				}
				else 
				{
					//не удалось определить часть речи (даже не знаю, возможно ли это, но на всякий случай)
					$wordsInfo[$word] = array('lemm'=>false, 'pos'=>false, 'orig'=> $wordOriginal);
				}
			}
			else
			{
				//не удалось однозначно определить начальную форму слова
				$wordsInfo[$word] = array('lemm'=>false, 'pos'=>false, 'orig'=> $wordOriginal);
			}
		
		}

		//теперь выберем все существительные: они станут тегами
		$this->extractNouns($words, $wordsInfo);

		//и выберем все пары прилагательное-существительное. Они тоже будут тегами.
		$this->extractPhrases($words, $wordsInfo);
		
		return $this->_extractedTags;
	}
	
	/**
	 * Выделение в качестве тегов словосочетаний "прилагательное сщуествительное"
	 * @param array $words массив слов анализируемого блока в верхнем регистре
	 * @param array $wordsInfo массив грамматической информации о словах из $words
	 */
	private function extractPhrases($words, $wordsInfo)
	{
		for ($i=0; $i<count($words); $i++)
		{

			$word = $words[$i];
			if(!isset($words[$i+1]))
			{
				continue;
			}
			$nextWord = $words[$i+1];
			if(
				$wordsInfo[$word]['pos']==$this->_CODE_ADJECTIVE
				&& $wordsInfo[$nextWord]['pos']==$this->_CODE_NOUN
				)
			{
				/*
				 * На этом этапе мы получаем словосочетания типа "климатический условие"
				 * Нужно привести прилагательное к тому же роду, что у существительного. 
				 * 
				 */
				
				//находим форму именительного падежа для существительного (nominative case form)
				$nGramForms = $this->_morphy->getGramInfo($wordsInfo[$nextWord]['lemm']);

				$ncForm = null;
				$nounFormIndex = 0;
				foreach($nGramForms as $gramFormI=>$gramFormData)
				{
					if($gramFormData[0]['pos']==$this->_CODE_NOUN)
					{
						$nounFormIndex = $gramFormI;
					}
					
				}
				
				if(count($nGramForms[$nounFormIndex])>0)
				{
					foreach($nGramForms[$nounFormIndex] as $gramForm)
					{
						if(in_array('ИМ', $gramForm['grammems']) //именительный падеж
							|| in_array('0', $gramForm['grammems']) //или слово неизменяемое
						)
						{
							$ncForm = $gramForm;
						}
					}
				}
				else 
				{
					$ncForm = $nGramForms[$nounFormIndex][0];
				}
				
				//почему-то не нашли начальную форму существительного
				if(!is_array($ncForm))
				{
					continue;
				}
				
				//находим род
				$gender = '';
				$number = '';
				foreach($ncForm['grammems'] as $grammem)
				{
					if(in_array($grammem, array('МР','ЖР','СР')))
					{
						$gender = $grammem;
					}
				}

				//находим форму именительного падежа для прилагательного (nominative case form)
				$aGramForms = $this->_morphy->getGramInfo($wordsInfo[$word]['lemm']);
				if(count($aGramForms[0])>0)
				{
					foreach($aGramForms[0] as $gramForm)
					{
						if(in_array('ИМ', $gramForm['grammems']))
						{
							$ncForm = $gramForm;
						}
					}
				}
				else 
				{
					$ncForm = $aGramForms[0][0];
				}
				
				//подменяем род
				$aGrammems = $ncForm['grammems'];
				foreach($aGrammems as &$grammem)
				{
					if(in_array($grammem, array('МР','ЖР','СР')))
					{
						$grammem = $gender;
					}
				}
				
				//генерим формы прилагательного из исходного, но с нужным родом
				$adjForms = $this->_morphy->castFormByGramInfo($wordsInfo[$word]['lemm'], 'П', $aGrammems, false);
				
				//выбираем из получившихся форму прилагательного. Она не должна быть превосходной
				$adjForm = '';
				foreach($adjForms as $form)
				{
					if(!in_array('ПРЕВ', $form['grammems']))
					{
						$adjForm = $form['form'];
					}
				}
				
				if($adjForm) //для некоторых форму найти не удается ("новогодние каникулы")
				{
					$this->foundTag(mb_strtolower($adjForm.' '.$wordsInfo[$nextWord]['lemm']), TagsExtractor_TagTypes::$PHRASE);
				}
			}
		}
	} 

	
	/**
	 * Выделение в качестве тегов существительных
	 * @param array $words массив слов анализируемого блока в верхнем регистре
	 * @param array $wordsInfo массив грамматической информации о словах из $words
	 */
	private function extractNouns($words, $wordsInfo)
	{
		foreach ($words as $word)
		{
			if($wordsInfo[$word]['pos']==$this->_CODE_NOUN)
			{
				$tagType = TagsExtractor_TagTypes::$WORD;
				$outTag = mb_strtolower($wordsInfo[$word]['lemm']);
				// если слово было не первым в блоке и начиналось с заглавной буквы,
				// скорее всего, это имя собственное, и в теги должно пойти с заглавной буквы
				if(preg_match("/^[[:upper:]][[:lower:]]+$/", $wordsInfo[$word]['orig']))
				{
					$outTag = ucfirst($outTag);
					$tagType = TagsExtractor_TagTypes::$PRNAME;
				}
				//Плюс нужно обработать варианты типа "М.Горбачев"
				$matches = array();
				if(
					preg_match("/^([[:upper:]]\.)([[:upper:]][[:lower:]]+)$/", $wordsInfo[$word]['orig'])
					&& preg_match("/^([[:lower:]]\.)([[:lower:]][[:lower:]]+)$/", $outTag, $matches)
					)
				{
					$outTag = ucfirst($matches[1]).ucfirst($matches[2]);
					$tagType = TagsExtractor_TagTypes::$PRNAME;
				}
				$this->foundTag($outTag, $tagType);
			}
		}
	}
	
	protected function initExtractor($text)
	{
		
		parent::initExtractor($text);
		
		if (!$this->_morphy)
		{
			$phpMorphyFile = PROJECT_ROOT.'/classes/phpmorphy/src/common.php';
			if(!file_exists($phpMorphyFile))
			{
				throw new Exception("Для поиска с учетом морфологии необходим phpMorphy: ".$phpMorphyFile);
			}
			
			require_once($phpMorphyFile);
			$dir = PROJECT_ROOT.'/classes/phpmorphy/dicts';
			$lang = 'ru_RU';
			$opts = array(
			    'storage' => PHPMORPHY_STORAGE_FILE,
				'graminfo_as_text' => true
			);
			$this->_morphy = new phpMorphy($dir, $lang, $opts);
		}
	}
	
}
