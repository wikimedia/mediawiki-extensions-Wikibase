<?php

namespace Wikibase\Repo\Parsers;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
interface MonthNameProvider {

	/**
	 * @param string $languageCode
	 *
	 * @return string[] Array mapping the month's numbers 1 to 12 to localized month names.
	 */
	public function getLocalizedMonthNames( $languageCode );

	/**
	 * @param string $languageCode
	 * @param string $canonicalLanguageCode
	 *
	 * @return string[] Array mapping localized month names (possibly including full month names,
	 * genitive names and abbreviations) to the same month names in a canonical language (usually
	 * English).
	 */
	public function getMonthNameReplacements( $languageCode, $canonicalLanguageCode = 'en' );

}
