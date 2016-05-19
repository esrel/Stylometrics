<?php
/**
 * Stylometrics: Word-based Feature Class
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL License v. 3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 *
 * Measures of Lexical Richness
 *
 * N      -- token count (text length in words)
 * V      -- type count  (vocabulary size
 * C      -- character count (text length is characters, no spaces)
 * V(i,N) -- number of types of frequency i in text of length N
 * L(i,N) -- number of words of length i in text of length N
 *
 * A. Basic Counts:
 *   1. word count               = N
 *   2. dictionary size          = V
 *
 * B. Lexical Richness using transformations of N & V:
 *   3. Type-Token Ratio         = V/N
 *   4. Mean Word Frequency      = N/V
 *
 *   5. Guiraud's R              = V/sqrt(N)
 *   6. Herdan's C               = log(V)/log(N)
 *   7. Rubet's K                = log(V)/log(log(N))
 *   8. Maas' A                  = (log(N) - log(V))/log^2(N) = a^2
 *   9. Dugast's U               = log^2(N)/(log(N) - log(V))
 *  10. Lukjanenkov and Neistoj  = (1 - V^2)/(V^2 * log(N))
 *  11. Brunet's W               = N^(V^(-a)), a = 0.172
 *
 * C. Length-based features:
 *  12. Average word length      = C/N
 *
 * D. Length/Frequency-based Ratios
 *  13. Short word ratio (to N)  = sum(L(i,N))/N; i={1,2,3}
 *
 * E. Frequency-based Ratios
 *  14. Ratio of Hapax Legomena to N    = V(1,N)/N
 *  15. Ratio of Hapax Dislegomena to N = V(2,N)/N
 *
 * F. Lexical Richness using Frequency Spectrum:
 *  16. Honore's H   = b * (log(N)/a - (V(1,N)/V)); b = 100 & a = 1
 *  17. Sichel's S   = V(2,N)/V
 *  18. Michea's M   = V/V(2,N)
 *  19. Herdan's V   = sqrt(sum(V(i,N) * (V(i,N)/N)^2) - 1/V)
 *  20. Yule's K     = a * (-1/N + sum(V(i,N) * (V(i,N)/N)^2)); a = 1
 *  21. Simpson's D  = sum(V(i,N) * (V(i,N)/N) * (V(i,N) - 1)/(N - 2))
 *  22. Entropy      = V(i,N) * (-log((V(i,N)/N))^s * (V(i,N)/N)^t;
 *                     s = t = 1
 * ---------------------------------------------------------------------
 */
class LexicalRichness {


/*----------------------------------------------------------------------
    Meta Function to get all metrics
----------------------------------------------------------------------*/

	/**
	 * Process document & get all metrics
	 *
	 * @param  mixed  $doc
	 * @return array  $out
	 */
	public function getDocMetrics($doc) {

		$out = array();

		// Type/Token Ratios/Metrics
		$out = array_merge($out, $this->getTypeTokenMetrics($doc));
		// Length-based metrics
		$out = array_merge($out, $this->getLengthMetrics($doc));
		// Length/Frequency Ratio based metrics
		$out = array_merge($out, $this->getLengthRatioMetrics($doc, 1, 3));
		// Frequency-based metrics
		$out = array_merge($out, $this->getFrequencyMetrics($doc));
		// Frequency Ratio-based metrics
		$out = array_merge($out, $this->getFrequencyRatioMetrics($doc));

		return $out;
	}

/*----------------------------------------------------------------------
    Group Functions
----------------------------------------------------------------------*/

	/**
	 * Get Word & Vocabulary Counts
	 *
	 * @param  mixed $doc
	 * @return array
	 */
	public function getBasicCounts($doc) {
		$arr = $this->doc2words($doc);
		if ($arr) {
			$N = $this->compWordCount($arr);
			$V = $this->compVocabularySize($arr);
			return array($V, $N);
		}
		else {
			return array(0, 0);
		}
	}

	/**
	 * Compute Type-Token Count based metrics of Lexical Richness
	 *
	 * @param  mixed $doc
	 * @return array $o
	 */
	public function getTypeTokenMetrics($doc) {
		list($N, $V) = $this->getBasicCounts($doc);
		if ($N == 0) {
			return FALSE;
		}

		$o = array(); // output array
		// Basic Stats
		$o['WordCount']         = $N;
		$o['VocabularySize']    = $V;
		// Basic Transformations
		$o['TypeTokenRatio']    = $this->compTypeTokenRatio($N, $V);
		$o['MeanWordFrequency'] = $this->compMeanWordFrequency($N, $V);
		// Complex Transformations
		$o['GuiraudR']          = $this->compGuiraudR($N, $V);
		$o['HerdanC']           = $this->compHerdanC($N, $V);
		$o['RupetK']            = $this->compRupetK($N, $V);
		$o['MaasA']             = $this->compMaasA($N, $V);
		$o['DugastU']           = $this->compDugastU($N, $V);
		$o['LukjanenkovNeistoj']= $this->compLukjanenkovNeistoj($N, $V);
		$o['BrunetW']           = $this->compBrunetW($N, $V);

		return $o;
	}

	/**
	 * Compute Frequency Spectrum Metrics
	 *
	 * @param  mixed $doc
	 * @return array $o
	 */
	public function getFrequencyMetrics($doc) {
		$words = $this->doc2words($doc);

		if (!$words) {
			return FALSE;
		}

		// Basic Counts
		list($N, $V) = $this->getBasicCounts($doc);
		// Type frequency frequencies
		$freq_freq_arr = $this->compTypeFreqFrequencies($words);

		$o = array();
		// Partial Frequency Spectrum
		$o['SuchelS']  = $this->compSichelS($N, $V, $freq_freq_arr);
		$o['MicheaM']  = $this->compMicheaM($N, $V, $freq_freq_arr);
		$o['HonoreH']  = $this->compHonoreH($N, $V, $freq_freq_arr);
		// Full Frequency Spectrum
		$o['Entropy']  = $this->compEntropy($N, $V, $freq_freq_arr);
		$o['YuleK']    = $this->compYuleK($N, $V, $freq_freq_arr);
		$o['SimpsonD'] = $this->compSimpsonD($N, $V, $freq_freq_arr);
		$o['HerdanV']  = $this->compHerdanV($N, $V, $freq_freq_arr);

		return $o;
	}

	/**
	 * Compute Frequecy-ratio based Metrics
	 *
	 * @param  mixed $doc
	 * @return array $o
	 */
	public function getFrequencyRatioMetrics($doc) {
		$words = $this->doc2words($doc);
		if (!$words) {
			return FALSE;
		}

		// Basic Counts
		list($N, $V)   = $this->getBasicCounts($doc);
		// Type frequency frequencies
		$freq_freq_arr = $this->compTypeFreqFrequencies($words);
		// Type frequenct ratios
		$freq_ratios   = $this->compFrequencyRatios($N, $freq_freq_arr);

		$o = array();
		// Hapax Legomena
		$o['HapaxLegomenaRatio']    = $this->compHapaxLegomena($freq_ratios);
		// Hapax Dislegomena
		$o['HapaxDislegomenaRatio'] = $this->compHapaxDislegomena($freq_ratios);

		return $o;
	}

	/**
	 * Compute Length-based Metrics
	 *
	 * @param  mixed $doc
	 * @return array
	 */
	public function getLengthMetrics($doc) {
		$words = $this->doc2words($doc);
		if (!$words) {
			return FALSE;
		}

		$o = array();
		$o['AverageWordLength'] = $this->compAverageWordLength($words);

		return $o;
	}


	/**
	 * Compute Word-Length Ratio-based Metrics
	 *
	 * @param  mixed $doc
	 * @param  int   $min
	 * @param  int   $max
	 * @return array $o
	 */
	public function getLengthRatioMetrics($doc, $min = 1, $max = 3) {
		$words = $this->doc2words($doc);
		if (!$words) {
			return FALSE;
		}

		// Basic Counts
		list($N, $V)  = $this->getBasicCounts($doc);
		// Length frequencies
		$len_freq_arr = $this->compLengthFrequencies($words);
		// Length frequency ratios
		$len_ratios   = $this->compLengthRatios($N, $len_freq_arr);

		$o = array();
		$o['ShortWordRatio'] = $this->compLengthFrequencyRatio($len_ratios, $min, $max);

		foreach (range($min, $max) as $v) {
			$o['LengthRatio-' . $v] = (isset($len_ratios[$v]))
					? $len_ratios[$v] : 0;
		}

		return $o;
	}

/*----------------------------------------------------------------------
    Utility Functions
----------------------------------------------------------------------*/

	/**
	 * Split document into array of words w.r.t. space
	 *
	 * @param  mixed $doc document string
	 * @return array      document array
	 */
	public function doc2words($doc) {
		if (is_string($doc)) {
			return preg_split('/\s/u', $doc, -1, PREG_SPLIT_NO_EMPTY);
		}
		elseif (is_array($doc)) {
			return $doc;
		}
		elseif (is_file($doc)) {
			$str = file_get_contents($doc);
			return preg_split('/\s/u', $str, -1, PREG_SPLIT_NO_EMPTY);
		}
		else {
			return FALSE;
		}
	}

	/**
	 * Filter word array to remove all non-alpha-only words
	 * @param  array $arr
	 * @return array $out
	 */
	private function filterWords($arr) {
		$out = array();
		foreach ($arr as $word) {
			if (preg_match('/^[[:alpha:]]+$/u', $word)) {
				$out[] = $word;
			}
		}
		return $out;
	}

/*----------------------------------------------------------------------
    Basic Support Functions
----------------------------------------------------------------------*/

	/**
	 * Create vocabulary from word array
	 * @param  array $arr
	 * @return array $vocabulary
	 */
	private function makeVocabulary($arr) {
		$cnt_arr    = $this->compTypeFrequencies($arr);
		$vocabulary = array_keys($cnt_arr);
		sort($vocabulary);

		return $vocabulary;
	}
/*--------------------------------------------------------------------*/
	/**
	 * Create frequency count array
	 * @param  array $arr
	 * @return array
	 */
	private function compTypeFrequencies($arr) {
		return array_count_values($arr);
	}

	/**
	 * Create type-frequency frequency array
	 * @param  array $arr word array
	 * @return array $freq_arr
	 */
	private function compTypeFreqFrequencies($arr) {
		$cnt_arr   = $this->compTypeFrequencies($arr);
		$freq_keys = array_values($cnt_arr);
		sort($freq_keys, SORT_NUMERIC);
		$freq_arr  = array_fill_keys($freq_keys, 0);

		foreach ($cnt_arr as $word => $cnt) {
			$freq_arr[$cnt]++;
		}
		return $freq_arr;
	}

	/**
	 * Create length-frequency array
	 * @param  array $arr
	 * @param  int   $min
	 * @param  int   $max
	 * @return array $len_arr
	 */
	private function compLengthFrequencies($arr, $min = 1, $max = 30) {
		$cnt_arr = $this->compTypeFrequencies($arr);
		$len_arr = array_fill_keys(range($min, $max), 0);
		foreach ($cnt_arr as $k => $v) {
			$len = strlen($k);
			if ($len > $max) {
				$len_arr[$max] += $v;
			}
			else {
				$len_arr[$len] += $v;
			}
		}
		return $len_arr;
	}

	/**
	 * Compute ratios given array & number
	 * @param array  $arr
	 * @param number $num
	 * @return array $ratios
	 */
	private function compRatio($arr, $num = NULL) {
		$ratios = array();

		if (!$num) {
			$num = array_sum($arr);
		}

		if ($num == 0) {
			$ratios = array_fill_keys(array_keys($arr), 0);
		}
		else {
			foreach ($arr as $k => $v) {
				$ratios[$k] = $v/$num;
			}
		}
		return $ratios;
	}

	/**
	 * Compute Length ratios to text size
	 * @param  number $N
	 * @param  array  $len_freq_arr
	 * @return array
	 */
	public function compLengthRatios($N, $len_freq_arr) {
		return $this->compRatio($len_freq_arr, $N);
	}

	/**
	 * Compute frequency ratios to text size
	 * @param  number $N
	 * @param  array  $freq_freq_arr
	 * @return array
	 */
	public function compFrequencyRatios($N, $freq_freq_arr) {
		return $this->compRatio($freq_freq_arr, $N);
	}

/*----------------------------------------------------------------------
    Basic Metrics
----------------------------------------------------------------------*/

	/**
	 * Compute Word Count
	 * @param  array $arr
	 * @return number
	 */
	public function compWordCount($arr) {
		return count($arr);
	}

	/**
	 * Compute Character Count (excluding spaces)
	 * @param  array $arr
	 * @return number
	 */
	public function compCharacterCount($arr) {
		return strlen(implode('', $arr));
	}

	/**
	 * Compute Dictionaty Size / Type Count
	 * @param  array $arr
	 * @return number
	 */
	public function compVocabularySize($arr) {
		return count($this->makeVocabulary($arr));
	}

	/**
	 * Compute Average Word Length
	 * @param int $arr
	 * @return number
	 */
	public function compAverageWordLength($arr) {
		$char_cnt = $this->compCharacterCount($arr);
		$word_cnt = $this->compWordCount($arr);

		return ($word_cnt == 0) ? 0 : $char_cnt/$word_cnt;
	}

/*----------------------------------------------------------------------
    Basic Transformations
----------------------------------------------------------------------*/

	/**
	 * Compute type-token ratio = V/N
	 * @param int $N
	 * @param int $V
	 * @return number
	 */
	public function compTypeTokenRatio($N, $V) {
		return ($N == 0) ? 0 : $V/$N;
	}

	/**
	 * Compute mean word frequency = N/V
	 * @param int $N
	 * @param int $V
	 * @return number
	 */
	public function compMeanWordFrequency($N, $V) {
		return ($V == 0) ? 0 : $N/$V;
	}

/*----------------------------------------------------------------------
    Complex Transformations
----------------------------------------------------------------------*/

	/**
	 * Compute Guiraud's R: R = V/sqrt(N)
	 * @param int $N
	 * @param int $V
	 * @return number
	 */
	public function compGuiraudR($N, $V) {
		return ($N == 0) ? 0 : $V/sqrt($N);
	}

	/**
	 * Compute Herdan's C: C = log(V)/log(N)
	 * @param int $N
	 * @param int $V
	 * @return number
	 */
	public function compHerdanC($N, $V) {
		return ($N == 1 || $N == 0) ? 0 : log($V, 10)/log($N, 10);
	}

	/**
	 * Compute Rupet's K: K = log(V)/log(log(N))
	 * @param int $N
	 * @param int $V
	 * @return number
	 */
	public function compRupetK($N, $V) {
		return ($N > 1 && $V > 1 && $N != 10)
				? log($V, 10)/log(log($N, 10), 10)
				: 0;
	}

	/**
	 * Compute Maas' A: A = (log(N) - log(V))/log^2(N)
	 * @param int $N
	 * @param int $V
	 * @return number
	 */
	public function compMaasA($N, $V) {
		return ($N > 1) ? (log($N,10)-log($V,10))/pow(log($N,10),2) : 0;
	}

	/**
	 * Compute Dugast's U: U = log^2(N)/(log(N) - log(V))
	 * @param int $N
	 * @param int $V
	 * @return number
	 */
	public function compDugastU($N, $V) {
		return ($N > 1 && $V > 1 && $N != $V)
				? pow(log($N, 10), 2)/(log($N, 10) - log($V, 10))
				: 0;
	}

	/**
	 * Compute Lukjanenkov and Neistoj's metric = (1-V^2)/(V^2 * log(N))
	 * @param int $N
	 * @param int $V
	 * @return number
	 */
	public function compLukjanenkovNeistoj($N, $V) {
		return ($N > 1) ? (1 - pow($V,2))/(pow($V,2) * log($N,10)) : 0;
	}

	/**
	 * Compute Brunet's W: W = N^(V^(-a)), a = 0.172
	 * @param int $N
	 * @param int $V
	 * @return number
	 */
	public function compBrunetW($N, $V, $a = 0.172) {
		return pow($N, pow($V, -$a));
	}

/*----------------------------------------------------------------------
    Frequency Spectrum Metrics: Partial Spectrum
----------------------------------------------------------------------*/

	/**
	 * Compute Sichel's S: S = V(2,N)/V
	 * @param int $N
	 * @param int $V
	 * @param array $freq_freq_arr
	 * @return number
	 */
	public function compSichelS($N, $V, $freq_freq_arr) {
		$freq2 = (isset($freq_freq_arr[2])) ? $freq_freq_arr[2] : 0;

		return ($V == 0) ? 0 : $freq2/$V;
	}

	/**
	 * Compute Michea's M: M = V/V(2,N)
	 * @param int $N
	 * @param int $V
	 * @param array $freq_freq_arr
	 * @return number
	 */
	public function compMicheaM($N, $V, $freq_freq_arr) {
		$freq2 = (isset($freq_freq_arr[2])) ? $freq_freq_arr[2] : 0;

		return ($freq2 == 0) ? 0 : $V/$freq2;
	}

	/**
	 * Compute Honore's H: H = b * (log(N)/(a - (V(1,N)/V)))
	 *                     b = 100 & a = 1
	 * @param int $N
	 * @param int $V
	 * @param array $freq_freq_arr
	 * @return number
	 */
	public function compHonoreH($N, $V, $freq_freq_arr,
								$a = 1, $b = 100) {
		$freq1 = (isset($freq_freq_arr[1])) ? $freq_freq_arr[1] : 0;

		return ($N > 1 && $V > 1)
				? $b * (log($N, 10)/($a - $freq1/$V))
				: 0;
	}

/*----------------------------------------------------------------------
    Frequency Spectrum Metrics: Full Spectrum
----------------------------------------------------------------------*/

	/**
	 * Compute Yule's K
	 * @param  int   $N
	 * @param  int   $V
	 * @param  array $freq_freq_arr
	 * @return number
	 */
	public function compYuleK($N, $V, $freq_freq_arr, $scale = 1) {

		if ($N == 0) {
			return 0;
		}
		else {
			$sum = 0;
			foreach ($freq_freq_arr as $k => $v) {
				$sum += $v * pow($v/$N, 2);
			}
			return $scale * (-1/$N + $sum);
		}
	}

	/**
	 * Compute Simpson's D
	 * @param  int   $N
	 * @param  int   $V
	 * @param  array $freq_freq_arr
	 * @return number
	 */
	public function compSimpsonD($N, $V, $freq_freq_arr) {

		if ($N <= 1) {
			return 0;
		}
		else {
			$sum = 0;
			foreach ($freq_freq_arr as $k => $v) {
				$sum += $v * ($v/$N) * (($v - 1)/($N - 1));
			}
			return $sum;
		}
	}

	/**
	 * Compute Herdan's V
	 * @param int $N
	 * @param int $V
	 * @param array $freq_freq_arr
	 * @return number
	 */
	public function compHerdanV($N, $V, $freq_freq_arr) {
		if ($N == 0 || $V == 0) {
			return 0;
		}
		else {
			$sum = 0;
			foreach ($freq_freq_arr as $k => $v) {
				$sum += $v * pow($v/$N, 2);
			}
			return sqrt($sum - 1/$V);
		}
	}



/*----------------------------------------------------------------------
    Frequency Spectrum Metrics: Entropy-based
----------------------------------------------------------------------*/

	/**
	 * Compute Good's c (varies)
	 * @param int $N
	 * @param int $V
	 * @param array $freq_freq_arr
	 * @param int $s
	 * @param int $t
	 * @return number
	 */
	public function compGoodC($N, $V, $freq_freq_arr, $s, $t) {
		$C = 0;
		foreach ($freq_freq_arr as $k => $v) {
			$C += $v * pow(-log(($v/$N), 10), $s) * pow(($v/$N), $t);
		}
		return $C;
	}

	/**
	 * Compute Entropy
	 * @param int $N
	 * @param int $V
	 * @param array $freq_freq_arr
	 * @return number
	 */
	public function compEntropy($N, $V, $freq_freq_arr) {
		return $this->compGoodC($N, $V, $freq_freq_arr, 1, 1);
	}

/*----------------------------------------------------------------------
    Frequency Ratio-based Metrics
----------------------------------------------------------------------*/

	/**
	 * Compute Frequecy-based ratio
	 *
	 * @param  array $freq_ratios
	 * @param  int   $freq
	 * @return number
	 */
	public function compFrequencyRatio($freq_ratios, $freq) {
		return (isset($freq_ratios[$freq])) ? $freq_ratios[$freq] : 0;
	}

	/**
	 * Compute Hapax Legomena Ratio
	 * @param  array $freq_freq_arr
	 * @return number
	 */
	public function compHapaxLegomena($freq_ratios) {
		return $this->compFrequencyRatio($freq_ratios, 1);
	}

	/**
	 * Compute Hapax Dislegomena Ratio
	 * @param  array $freq_ratios
	 * @return number
	 */
	public function compHapaxDislegomena($freq_ratios) {
		return $this->compFrequencyRatio($freq_ratios, 2);
	}

/*----------------------------------------------------------------------
    Length/Frequency Ratio-based Metrics
----------------------------------------------------------------------*/

	/**
	 * Compute Length/Frequency-based Metrics
	 *
	 * @param  array $len_ratios
	 * @param  int   $min        minimum length
	 * @param  int   $max        maximum length
	 * @retunr num   $sum
	 */
	public function compLengthFrequencyRatio($len_ratios,
											 $min = 1, $max = 3) {
		if (!$len_ratios) {
			return 0;
		}

		$sum = 0;
		foreach (range($min, $max) as $len) {
			if (isset($len_ratios[$len])) {
				$sum += $len_ratios[$len];
			}
		}
		return $sum;
	}
}
/*======================================================================
    Example Usage
======================================================================*/
/*
error_reporting(E_ALL);
ini_set('memory_limit', -1);
ini_set('display_errors', 1);

$args = getopt('f:'); // document as a file

// example document
$doc  = 'Pierre Vinken , 61 years old , ';
$doc .= 'will join the board as a nonexecutive director Nov. 29 .';
$doc .= "\n";
$doc .= 'Mr . Vinken is chairman of Elsevier N.V. , ';
$doc .= 'the Dutch publishing group .';

$doc  = (isset($args['f'])) ? file_get_contents($args['f']) : $doc;

$LRF = new LexicalRichness();
print_r($LRF->getDocMetrics($doc));
*/
