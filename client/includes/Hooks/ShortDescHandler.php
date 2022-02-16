<?php

namespace Wikibase\Client\Hooks;

use Parser;
use Wikibase\Client\Store\DescriptionLookup;

/**
 * Handles the {{SHORTDESC:...}} parser function.
 *
 * @license GPL-2.0-or-later
 */
class ShortDescHandler {

	/**
	 * Parser function callback
	 *
	 * @param Parser $parser
	 * @param string $shortDesc Short description of the current page, as plain text.
	 * @param string $controlArg An extra argument to control behavior (such as 'noreplace').
	 *
	 * @return string
	 */
	public static function handle( Parser $parser, $shortDesc, $controlArg = '' ) {
		$handler = self::factory();
		$handler->doHandle( $parser, $shortDesc, $controlArg );
		return '';
	}

	/**
	 * @return self
	 */
	private static function factory() {
		return new self();
	}

	/**
	 * Validates a short description.
	 * Valid descriptions are not empty (contain something other than whitespace/punctuation).
	 *
	 * @param string $shortDesc Short description of the current page, as plain text.
	 *
	 * @return bool
	 */
	public function isValid( $shortDesc ) {
		return !preg_match( '/^[\s\p{P}\p{Z}]*$/u', $shortDesc );
	}

	/**
	 * Sanitizes a short description by converting it into plaintext.
	 *
	 * Note that the sanitized description can still contain HTML (that was encoded as entities in
	 * the original) as there is no reason why someone shouldn't mention HTML tags in a description.
	 * That means the sanitized value is actually less safe for HTML inclusion than the original
	 * one (can contain <script> tags)! It is clients' responsibility to handle it safely.
	 *
	 * @param string $shortDesc Short description of the current page, as HTML.
	 *
	 * @return string Plaintext of description.
	 */
	public function sanitize( $shortDesc ) {
		// Remove accidental formatting - descriptions are plaintext.
		$shortDesc = strip_tags( $shortDesc );
		// Unescape - clients are not necessarily HTML-based and using HTML tags as part of
		// the descript (i.e. with <nowiki> or such) should be possible.
		$shortDesc = html_entity_decode( $shortDesc, ENT_QUOTES, 'utf-8' );
		// Remove newlines, tabs and other weird whitespace
		$shortDesc = preg_replace( '/\s+/', ' ', $shortDesc );
		// Get rid of leading/trailing space - no valid usecase for it, easy for it to go unnoticed
		// in HTML, and clients might display the description in an environment that does not
		// ignore spaces like HTML does.
		return trim( $shortDesc );
	}

	/**
	 * Parser function
	 *
	 * @param Parser $parser
	 * @param string $shortDesc Short description of the current page, as plain text.
	 * @param string $controlArg An extra argument to control behavior (such as 'noreplace').
	 *
	 * @return void
	 */
	public function doHandle( Parser $parser, $shortDesc, $controlArg ) {
		$noReplace = $this->parseNoReplace( $parser, $controlArg );
		$out = $parser->getOutput();

		if ( $out->getPageProperty( 'wikibase-shortdesc' ) !== null && $noReplace ) {
			return;
		}

		$shortDesc = $this->sanitize( $shortDesc );
		if ( $this->isValid( $shortDesc ) ) {
			$out->setPageProperty( DescriptionLookup::LOCAL_PROPERTY_NAME, $shortDesc );
		}
	}

	/**
	 * @param Parser $parser
	 * @param string $controlArg
	 * @return bool
	 */
	private function parseNoReplace( $parser, $controlArg ) {
		static $magicWord = null;
		if ( $magicWord === null ) {
			$magicWord = $parser->getMagicWordFactory()->get( 'shortdesc_noreplace' );
		}
		return $magicWord->matchStartToEnd( $controlArg );
	}

}
