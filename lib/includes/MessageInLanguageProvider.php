<?php

declare( strict_types = 1 );

namespace Wikibase\Lib;

use Language;
use Message;
use MessageLocalizer;
use MessageSpecifier;
use StubUserLang;

/**
 * A provider for messages in a particular language.
 *
 * The language is not optional – this service distinguished from {@see MessageLocalizer}
 * by being agnostic of the user language.
 *
 * @license GPL-2.0-or-later
 */
interface MessageInLanguageProvider {

	/**
	 * Get a translated interface message in the specified language.
	 *
	 * @see MessageLocalizer::msg()
	 * @see Message::inLanguage()
	 *
	 * @param string|string[]|MessageSpecifier $key
	 * @param Language|StubUserLang|string $language
	 * @param mixed ...$params
	 * @return Message
	 */
	public function msgInLang( $key, $language, ...$params ): Message;

}
