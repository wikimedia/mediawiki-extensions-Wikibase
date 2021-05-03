<?php

declare( strict_types = 1 );

namespace Wikibase\Lib;

use Message;

/**
 * @license GPL-2.0-or-later
 */
class MediaWikiMessageInLanguageProvider implements MessageInLanguageProvider {

	public function msgInLang( $key, $language, ...$params ): Message {
		return wfMessage( $key, ...$params )->inLanguage( $language );
	}

}
