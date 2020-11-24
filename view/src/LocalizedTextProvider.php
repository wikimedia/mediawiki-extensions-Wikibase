<?php

namespace Wikibase\View;

/**
 * A service providing localized texts for keys
 *
 * These are meant to be plain text results, i.e. no markup.
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
interface LocalizedTextProvider {

	/**
	 * @param string $key
	 * @param string[] $params Parameters that could be used for generating the text
	 *
	 * @return string The unescaped localized text
	 */
	public function get( $key, array $params = [] );

	/**
	 * @param string $key
	 * @param array<string|RawMessageParameter> $params Parameters that could be used for generating the text
	 *
	 * @return string The HTML-escaped localized text
	 */
	public function getEscaped( $key, array $params = [] );

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function has( $key );

	/**
	 * @param string $key
	 *
	 * @return string The language code of the text returned for a specific key
	 */
	public function getLanguageOf( $key );

}
