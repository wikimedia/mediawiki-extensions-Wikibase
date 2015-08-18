<?php

namespace Wikibase\Client;

use Language;
use ParserOptions;
use ParserOutput;
use Title;

/**
 * Helper, utility class for determining language used for parsing,
 * based on how this is determined in Parser::getTargetLanguage.
 *
 * See T109705 for improving and making this logic more accessible
 * in core, and thus removing the necessity of having this helper class.
 *
 * @license GPL 2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ParserLanguageOptions {

	public function getParserLanguageCode(
		ParserOutput $pout,
		ParserOptions $popts,
		Title $title,
	) {
		$targetLanguage = $parserOptions->getTargetLanguage();

		if ( $targetLanguage instanceof Language ) {
			return $targetLanguage->getCode();
		} elseif (
			in_array( 'userlang', $parserOutput->getUsedOptions() ) ||
			$parserOptions->getInterfaceMessage()
		) {
			return $parserOptions->getUserLang();
		}

		return $title->getPageLanguage()->getCode();
	}

}
