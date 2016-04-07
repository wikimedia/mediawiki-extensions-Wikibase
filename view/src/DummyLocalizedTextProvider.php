<?php

namespace Wikibase\View;

/**
 * A LocalizedTextProvider implementation that returns a string containing the given key and params
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class DummyLocalizedTextProvider implements LocalizedTextProvider {

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @param string $languageCode
	 */
	public function __construct( $languageCode ) {
		$this->languageCode = $languageCode;
	}

	/**
	 * @param string $key
	 * @param string[] $params Parameters that could be used for generating the text
	 *
	 * @return string The localized text
	 */
	public function get( $key, $params = [] ) {
		return "($key" . ( $params !== [] ? ": " . implode( $params, ", " ) : "" ) . ")";
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function has( $key ) {
		return true;
	}

	/**
	 * @param string $key
	 *
	 * @return string The language of the text returned for a specific key
	 */
	public function getLanguageOf( $key ) {
		return $this->languageCode;
	}

}
