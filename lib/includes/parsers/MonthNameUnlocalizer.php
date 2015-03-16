<?php

namespace Wikibase\Lib\Parsers;

/**
 * Base class to unlocalize a month name in a date string.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Thiemo Mättig
 *
 * @todo move me to DataValues-time
 */
class MonthNameUnlocalizer {

	/**
	 * @var string[] An array mapping localized to canonical month names.
	 */
	private $replacements = array();

	/**
	 * @param string[] $replacements An array mapping localized month names (possibly including full
	 * month names, genitive names and abbreviations) to canonical month names.
	 */
	public function __construct( array $replacements ) {
		$this->replacements = $replacements;

		// Order search strings from longest to shortest
		uksort( $this->replacements, function( $a, $b ) {
			return strlen( $b ) - strlen( $a );
		} );
	}

	/**
	 * Unlocalizes the longest month name in a date string that could be found first.
	 * Tries to avoid doing multiple replacements and returns the localized original if in doubt.
	 *
	 * @see NumberUnlocalizer::unlocalizeNumber
	 *
	 * @param string $date Localized date string.
	 *
	 * @return string Unlocalized date string.
	 */
	public function unlocalize( $date ) {
		foreach ( $this->replacements as $search => $replace ) {
			$unlocalized = str_replace( $search, $replace, $date, $count );

			// Nothing happened, try the next.
			if ( $count <= 0 ) {
				continue;
			}

			// Do not mess with strings that are clearly not a valid date.
			if ( $count > 1 ) {
				break;
			}

			// Do not mess with already unlocalized month names, e.g. "July" should not become
			// "Julyy" when replacing "Jul" with "July". But shortening "Julyus" to "July" is ok.
			if ( strpos( $date, $replace ) !== false && strlen( $replace ) >= strlen( $search ) ) {
				break;
			}

			return $unlocalized;
		}

		return $date;
	}

}
