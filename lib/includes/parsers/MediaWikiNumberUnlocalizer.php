<?php

namespace Wikibase\Lib;
use Language;
use ValueParsers\ParserOptions;
use ValueParsers\Unlocalizer;

/**
 * MediaWikiNumberUnlocalizer
 *
 * @since 0.5
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class MediaWikiNumberUnlocalizer implements Unlocalizer {

	/**
	 * @see Unlocalizer::unlocalize()
	 *
	 * @param string $number string to process
	 * @param string $langCode language code
	 * @param ParserOptions $options
	 *
	 * @return string unlocalized string
	 */
	public function unlocalize( $number, $langCode, ParserOptions $options ) {
		$lang = Language::factory( $langCode );

		$canonicalizedNumber = $lang->parseFormattedNumber( $number );
		return $canonicalizedNumber;
	}
}
