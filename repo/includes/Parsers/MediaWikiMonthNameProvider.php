<?php

namespace Wikibase\Repo\Parsers;

use MediaWiki\MediaWikiServices;
use ValueParsers\MonthNameProvider;

/**
 * A MonthNameProvider using MediaWiki's localization infrastructure.
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class MediaWikiMonthNameProvider implements MonthNameProvider {

	/**
	 * @see MonthNameProvider::getLocalizedMonthNames
	 *
	 * @param string $languageCode
	 *
	 * @return string[] Array mapping month numbers (1 to 12) to localized month names.
	 */
	public function getLocalizedMonthNames( $languageCode ) {
		$language = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( $languageCode );
		$monthNames = [];

		for ( $i = 1; $i <= 12; $i++ ) {
			$monthNames[$i] = $language->getMonthName( $i );
		}

		return $monthNames;
	}

	/**
	 * Creates a replacements array using information retrieved via MediaWiki's Language object.
	 * Takes full month names, genitive names and abbreviations into account.
	 *
	 * @see MonthNameProvider::getMonthNumbers
	 *
	 * @param string $languageCode
	 *
	 * @return int[] Array mapping localized month names (including full month names, genitive names
	 * and abbreviations) to month numbers (1 to 12).
	 */
	public function getMonthNumbers( $languageCode ) {
		$language = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( $languageCode );
		$numbers = [];

		for ( $i = 1; $i <= 12; $i++ ) {
			$numbers[$language->getMonthName( $i )] = $i;
			$numbers[$language->getMonthNameGen( $i )] = $i;
			$numbers[$language->getMonthAbbreviation( $i )] = $i;
		}

		return $numbers;
	}

}
