<?php

namespace Wikibase\View;

/**
 * A service providing localized texts for keys
 *
 * These are meant to be unescaped, plain text results.
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
interface LocalizedTextProvider {

	/**
	 * @param string $key
	 * @param string[] $params Parameters that could be used for generating the text
	 *
	 * @return string The localized text
	 */
	public function get( $key, array $params = [] );

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function has( $key );

	/**
	 * @param string $key
	 *
	 * @return string The language of the text returned for a specific key
	 */
	public function getLanguageOf( $key );

}
