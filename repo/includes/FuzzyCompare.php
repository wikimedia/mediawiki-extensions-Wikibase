<?php

namespace Wikibase;
/**
 * Comparison of language strings by a fuzzy similarity measure
 *
 * @since 0.3
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad
 */
final class FuzzyCompare {

	/**
	 * @var array of strings
	 */
	protected $strings = array();

	/**
	 * @var unknown_type
	 */
	protected $compiled = false;

	/**
	 * Compile the set of strings into a form that can be compared
	 * 
	 * Note that after compile it is not possible to add new strings with
	 * the current implementation.
	 *
	 * @since 0.3
	 */
	public function compile() {
		$this->compiled = null;
		$signatures = array();
		$strings = array_flip( $this->strings );

		foreach ( $strings as $string ) {
			$signatures[$string] = self::signature( $string );
		}
		$this->strings = $signatures;

		$this->compiled = true;
	}

	/**
	 * Calulate the signature
	 *
	 * This is an implementation of a space folding/subspace reduction
	 * algorithm based upon the Pearson hash
	 *
	 * @todo Move this out as a common shared utility
	 * 
	 * @since 0.3
	 *
	 * @param string $string the string to check, must already be compiled
	 */
	public static function signature( $string ) {
		// permutations for the algorithm
		static $permutations = array(
			1, 14, 110, 25, 97, 174, 132, 119, 138, 170, 125, 118, 27, 233, 140, 51,
			87, 197, 177, 107, 234, 169, 56, 68, 30, 7, 173, 73, 188, 40, 36, 65,
			49, 213, 104, 190, 57, 211, 148, 223, 48, 115, 15, 2, 67, 186, 210, 28,
			12, 181, 103, 70, 22, 58, 75, 78, 183, 167, 238, 157, 124, 147, 172, 144,
			176, 161, 141, 86, 60, 66, 128, 83, 156, 241, 79, 46, 168, 198, 41, 254,
			178, 85, 253, 237, 250, 154, 133, 88, 35, 206, 95, 116, 252, 192, 54, 221,
			102, 218, 255, 240, 82, 106, 158, 201, 61, 3, 89, 9, 42, 155, 159, 93,
			166, 80, 50, 34, 175, 195, 100, 99, 26, 150, 16, 145, 4, 33, 8, 189,
			121, 64, 77, 72, 208, 245, 130, 122, 143, 55, 105, 134, 29, 164, 185, 194,
			193, 239, 101, 242, 5, 171, 126, 11, 74, 59, 137, 228, 108, 191, 232, 139,
			6, 24, 81, 20, 127, 17, 91, 92, 251, 151, 225, 207, 21, 98, 113, 112,
			84, 226, 18, 214, 199, 187, 13, 32, 94, 220, 224, 212, 247, 204, 196, 43,
			249, 236, 45, 244, 111, 182, 153, 136, 129, 90, 217, 202, 19, 165, 231, 71,
			230, 142, 96, 227, 62, 179, 246, 114, 162, 53, 160, 215, 205, 180, 47, 109,
			44, 38, 31, 149, 135, 0, 216, 52, 63, 23, 37, 69, 39, 117, 146, 184,
			163, 200, 222, 235, 248, 243, 219, 10, 152, 131, 123, 229, 203, 76, 120, 209 );
		// initialize the array
		$signature = array();
		for ( $i = 0; $i <= 0x0ff; $i++) {
			$signature[$i] = 0;
		}
		// do a Pearson hash of trigram from the text
		$length = strlen( $string ) - 2;
		for ( $i = 0; $i < $length; $i++) {
			$hash = 0;
			$hash = $permutations[( $hash ^ ord( substr( $string, $i, 1 ) ) ) & 0x0ff];
			$hash = $permutations[( $hash ^ ord( substr( $string, $i + 1, 1 ) ) ) & 0x0ff];
			$hash = $permutations[( $hash ^ ord( substr( $string, $i + 2, 1 ) ) ) & 0x0ff];
			$signature[$hash]++;
		}
		return $signature;
	}

	/**
	 * Add a single string to the comparable set
	 *
	 * @since 0.3
	 *
	 * @param string $string the string to add to the set
	 */
	public function addString( $string ) {
		// TODO: check how this will be without array_flip
		$this->strings = array_merge( $this->strings, array_flip( array( $string ) ) );
	}

	/**
	 * Add a set of strings to the comparable set
	 *
	 * @since 0.3
	 *
	 * @param array $strings the strings to add to the set
	 */
	public function addStrings( array $strings ) {
		if ( $strings !== array() ) {
			$this->strings = array_merge( $this->strings, array_flip( $strings ) );
		}
	}

	/**
	 * The overall highscore for a given string
	 *
	 * Note that the string must be in the previously defined and compiled set
	 * to compare and give a valid score
	 *
	 * @since 0.3
	 *
	 * @param string $testString the string to compare against all the other strings
	 * 
	 * @return integer the highest score found
	 */
	public function overallScore( $testString ) {
		$signature = $this->strings[$testString];
		if ( !isset( $signature ) ) {
			return false;
		}
		$highScore = 0;
		foreach ( $this->strings as $string => $signature ) {
			if ( $string !== $testString ) {
				$score = $this->singleScore( $testString, $string );
				if ( $score > $highScore ) {
					$highScore = $score;
				}
			}
		}
		return $highScore;
	}

	/**
	 * The score for a comparison between two strings
	 *
	 * Note that both strings must be in the previously defined and compiled set
	 * to compare and give a valid score
	 *
	 * @since 0.3
	 *
	 * @param string $testString the string to compare against all the other strings
	 * 
	 * @return integer the score
	 */
	public function singleScore( $testString, $cmpString ) {
		$diff = 0;
		for ( $i = 0; $i <= 0x0ff; $i++) {
			$diff += abs( $this->strings[$testString][$i] - $this->strings[$cmpString][$i] );
		}
		$sum = 0;
		for ( $i = 0; $i <= 0x0ff; $i++) {
			$sum += abs( $this->strings[$testString][$i] + $this->strings[$cmpString][$i] );
		}
		$sum *= 0.5;
		return ($sum-$diff)/$sum;
	}
}