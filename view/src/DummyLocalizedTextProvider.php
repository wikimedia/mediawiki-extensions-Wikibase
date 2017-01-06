<?php

namespace Wikibase\View;

/**
 * A LocalizedTextProvider implementation that returns a string containing the given key and params
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class DummyLocalizedTextProvider implements LocalizedTextProvider {

	/**
	 * @param string $key
	 * @param string[] $params Parameters that could be used for generating the text
	 *
	 * @return string The $key, followed by a colon and comma separated $params, all in brackets.
	 */
	public function get( $key, array $params = [] ) {
		return "($key" . ( $params !== [] ? ": " . implode( $params, ", " ) : "" ) . ")";
	}

	/**
	 * @param string $key
	 *
	 * @return bool Always true.
	 */
	public function has( $key ) {
		return true;
	}

	/**
	 * @param string $key
	 *
	 * @return string Always "qqx".
	 */
	public function getLanguageOf( $key ) {
		return 'qqx';
	}

}
