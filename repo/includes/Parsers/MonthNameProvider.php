<?php

namespace Wikibase\Repo\Parsers;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
interface MonthNameProvider {

	/**
	 * @param string $languageCode
	 *
	 * @return string[] Array mapping month numbers (1 to 12) to localized month names.
	 */
	public function getLocalizedMonthNames( $languageCode );

	/**
	 * @param string $languageCode
	 *
	 * @return int[] Array mapping localized month names (possibly including full month names,
	 * genitive names and abbreviations) to month numbers (1 to 12).
	 */
	public function getMonthNumbers( $languageCode );

}
