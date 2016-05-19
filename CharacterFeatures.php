<?php
/*
 * CharacterFeatures.php
 *
 * Class to extract character features from a document
 *
 * Optionally takes character class definition file
 *
 * Features:
 * 	- punctuation ratios
 *  - space ratios
 *  - alphabetical character ratios
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL v3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 */
class CharacterFeatures {

	private $encoding;    // character encoding

	// space types
	private $spaces = array(
		'space'   => ' ',
		'tab'     => "\t",
		'newline' => "\n"
	);

	// character classes
	private $classes;

	/**
	 * Constructor
	 * Optionally sets character encoding & character classes
	 *
	 * @param file   $classes_file
	 * @param string $encoding
	 */
	public function __construct($classes_file = NULL,
								$encoding     = 'UTF-8') {

		$this->encoding  = $encoding;
		// parse character definition file
		if ($classes_file) {
			$this->parseCharDefinitions($classes_file);
		}
		else {
			$this->classes = FALSE;
		}
	}

	/**
	 * Parse character class definition file & set $this->classes
	 *
	 * @param file $file
	 */
	private function parseCharDefinitions($file) {
		$lines = array_map('trim', file($file));
		foreach ($lines as $line) {
			if ($line != '') {
				list($class, $chars) = explode("\t", $line);
				$arr = preg_split('/\s/u', $chars, -1,
									PREG_SPLIT_NO_EMPTY);
				$this->classes[$class] = $arr;
			}
		}
	}


/*----------------------------------------------------------------------
    Meta Functions
----------------------------------------------------------------------*/
	/**
	 * Meta function to generate all features
	 *
	 * @param  string $str
	 * @return array  $out
	 */
	public function getDocCharFeatures($str) {

		$out = array();

		if ($this->classes) {
			// character class ratios
			$out = array_merge($out, $this->getCharClassRatios($str));
			// space-based features
			$out = array_merge($out, $this->getSpaceFeatures($str));
			// puncturation-based features
			$out = array_merge($out, $this->getPunctFeatures($str));
			// alphabet-based features
			$out = array_merge($out, $this->getAlphabetFeatures($str));
		}
		else {
			// printable character count
			$gr_cnt = $this->nonSpaceCharCount($str);
			// white space count
			$ws_cnt = $this->spaceCharCount($str);
			// character frequency array
			$chf_arr = $this->str2charCountArray($str);
			ksort($chf_arr);

			$out['ws'] = ($gr_cnt != 0) ? $ws_cnt/$gr_cnt : 0;
			foreach ($chf_arr as $ch => $num) {
				$out['R:' . $ch] = ($gr_cnt != 0) ? $num/$gr_cnt : 0;
			}
		}

		return $out;
	}

/*----------------------------------------------------------------------
    Group Functions
----------------------------------------------------------------------*/

	/**
	 * Compute character-class ratios
	 *
	 * @param  string $str
	 * @return array  $out
	 */
	public function getCharClassRatios($str) {

		$out = array();

		// character counts
		$gr_cnt  = $this->nonSpaceCharCount($str); // character count: M
		// character frequencies array
		$chf_arr = $this->str2charCountArray($str);

		foreach ($this->classes as $class => $chars) {
			$subset = array_intersect_key($chf_arr, array_flip($chars));
			$out[$class] = ($gr_cnt != 0)
						 ? array_sum($subset)/$gr_cnt
						 : 0;
		}

		return $out;
	}

	/**
	 * Compute space-based features
	 *
	 *  - white space ratio to M (graphical character count)
	 *  - space to white space ratio
	 *  - space, tab, & newline ratios to M
	 *
	 * @param  string $str
	 * @return array  $out
	 */
	public function getSpaceFeatures($str) {

		$out = array();

		// character counts
		$gr_cnt  = $this->nonSpaceCharCount($str); // character count: M
		$ws_cnt  = $this->spaceCharCount($str);    // white space count
		// character frequencies array
		$chf_arr = $this->str2charCountArray($str);

		// white space ratio to M
		$out['ws']  = ($gr_cnt != 0) ? $ws_cnt/$gr_cnt: 0;

		// space to white space ratio
		$out['s2w'] = (isset($chf_arr[' ']) && $ws_cnt != 0)
					? $chf_arr[' ']/$ws_cnt
					: 0;

		// Space ratios to M
		foreach ($this->spaces as $type => $ch) {
			$out[$type] = (isset($chf_arr[$ch]) && $gr_cnt != 0)
						? $chf_arr[$ch]/$gr_cnt
						: 0;
		}

		return $out;
	}

	/**
	 * Compute punctuation-based features
	 *
	 * @param  string $str
	 * @return array  $out
	 */
	public function getPunctFeatures($str) {

		$out = array();

		// character counts
		$gr_cnt  = $this->nonSpaceCharCount($str); // character count: M
		// character frequencies array
		$chf_arr = $this->str2charCountArray($str);

		foreach ($this->classes['punctuation'] as $ch) {
			$out['R:' . $ch] = (isset($chf_arr[$ch]) && $gr_cnt != 0)
							 ? $chf_arr[$ch]/$gr_cnt
							 : 0;
		}

		return $out;
	}

	/**
	 * Compute alphabet-based features
	 *
	 * @param  string $str
	 * @return array  $out
	 */
	public function getAlphabetFeatures($str) {

		$out = array();

		// character counts
		$gr_cnt  = $this->nonSpaceCharCount($str); // character count: M
		// letter frequencies array
		$alf_arr = $this->alphabetFrequency($str,
											$this->classes['lower']);

		// alphabet ratio
		$al_cnt = array_sum($alf_arr);
		$out['alpha'] = ($gr_cnt != 0) ? $al_cnt/$gr_cnt : 0;

		// alphabet frequencies
		foreach ($alf_arr as $ch => $num) {
			$out['R:' . $ch] = ($gr_cnt != 0) ? $num/$gr_cnt : 0;
		}

		return $out;
	}

/*----------------------------------------------------------------------
    Subset Frequency Functions
----------------------------------------------------------------------*/

	/**
	 * Compute alphabet frequency from string
	 *
	 * @param  string $str
	 * @param  array  $alphabet
	 * @return array  $alph_freq_arr
	 */
	public function alphabetFrequency($str, $alphabet = NULL) {
		$alph_freq_arr = array();
		$char_freq_arr = $this->str2charCountArray(strtolower($str));

		if (!$alphabet) {
			$alphabet = range('a' , 'z');
		}

		foreach ($alphabet as $letter) {
			$alph_freq_arr[$letter] = (isset($char_freq_arr[$letter]))
									  ? $char_freq_arr[$letter]
									  : 0;
		}

		return $alph_freq_arr;
	}

/*----------------------------------------------------------------------
    Utility Functions
----------------------------------------------------------------------*/

	/**
	 * Count non-space characters
	 *
	 * @param  string $str
	 * @return number
	 */
	public function nonSpaceCharCount($str) {
		$str = preg_replace('/\s/u', '', $str);
		return $this->len($str);
	}

	/**
	 * Count white space characters
	 *
	 * @param  string $str
	 * @return number
	 */
	public function spaceCharCount($str) {
		return $this->len($str) - $this->nonSpaceCharCount($str);
	}

	/**
	 * Create character frequency array from a string
	 *
	 * @param  string $str document string
	 * @return array       character frequency array
	 */
	private function str2charCountArray($str) {
		return array_count_values($this->str2charArray($str));
	}

	/**
	 * Create character frequency array from character array
	 *
	 * @param  array $arr  character array
	 * @return array       character frequency array
	 */
	private function arr2charCountArray($arr) {
		return array_count_values($arr);
	}

	/**
	 * Split string into character array
	 *
	 * @param  string $str document string
	 * @return array       character array
	 */
	private function str2charArray($str) {
		return preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
	}

	/**
	 * String length: for short
	 *
	 * @param  string $str
	 * @return int
	 */
	private function len($str) {
		return mb_strlen($str, $this->encoding);
	}
}
/*======================================================================
    Example Usage
======================================================================*/
/*
error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

$args = getopt('f:c:'); // document as a file

// example document
$doc  = 'Pierre Vinken , 61 years old , ';
$doc .= 'will join the board as a nonexecutive director Nov. 29 .';
$doc .= "\n";
$doc .= 'Mr . Vinken is chairman of Elsevier N.V. , ';
$doc .= 'the Dutch publishing group .';

$doc  = (isset($args['f'])) ? file_get_contents($args['f']) : $doc;
$chs  = $args['c'];

$CF = new CharacterFeatures($chs);
//$CF = new CharacterFeatures();
print_r($CF->getDocCharFeatures($doc));
*/
