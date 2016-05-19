<?php
/*
 * runme.php -f $document -c chars.txt
 *
 * Meta file to extract stylometric features
 *
 * ---------------------------------------------------------------------
 * Copyright (c) 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 * Copyright (c) 2016 University of Trento - SIS Lab <sislab@unitn.it>
 *
 * For non-commercial and research purposes the code is released under
 * the LGPL License v. 3.0. For commercial use, please contact us.
 * ---------------------------------------------------------------------
 *
 * Arguments:
 *  -f text document
 *  -c character class definition
 */
// required or includes
require 'LexicalRichness.php';
require 'CharacterFeatures.php';

// Error & Memory Settings
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', -1);

// Arguments
$args = getopt('f:c:');

// Class Initializations
$LR = new LexicalRichness();
if (isset($args['c'])) {
	$CF = new CharacterFeatures($args['c']);
}
else {
	$CF = new CharacterFeatures();
}

//----------------------------------------------------------------------
$str    = file_get_contents($args['f']);
// Lexical Richness metric features
$lr_arr = $LR->getDocMetrics($str);
// Character-based stylometric features
$ch_arr = $CF->getDocCharFeatures($str);
$out    = array_merge($lr_arr, $ch_arr);

echo implode(',', array_map('ppnum', $out)) . "\n";

//----------------------------------------------------------------------
// Functions
//----------------------------------------------------------------------

/**
 * Pretty-Print numbers w.r.t. precision
 *
 * @param  number $num
 * @return number $num
 */
function ppnum($num) {
	$precision = 3;
	if (is_float($num) || $num == 0) {
		return number_format(round($num, $precision), $precision);
	}
	else {
		return $num;
	}
}
