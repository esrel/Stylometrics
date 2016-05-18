<?php
/*
 * runme.php -f $document -c chars.txt
 *
 * Meta file to extract stylometric features
 * ---------------------------------------------------------------------
 * Copyright 2016 Evgeny A. Stepanov <stepanov.evgeny.a@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
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

$str    = file_get_contents($args['f']);
// Lexical Richness metric features
$lr_arr = $LR->getDocMetrics($str);
// Character-based stylometric features
$ch_arr = $CF->getDocCharFeatures($str);
$out    = array_merge($lr_arr, $ch_arr);

echo implode(',', array_map('ppnum', $out)) . "\n";

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
