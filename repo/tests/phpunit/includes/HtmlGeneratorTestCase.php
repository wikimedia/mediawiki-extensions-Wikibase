<?php

namespace Wikibase\Test;

/**
 * Base class for all tests checking HTML output.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
abstract class HtmlGeneratorTestCase extends \MediaWikiLangTestCase {

	/**
	 * Asserts that the given string is valid HTML.
	 *
	 * @since 0.5
	 *
	 * @param string $html
	 * @param string $message
	 */
	protected function assertIsValidHtml( $html, $message = '' ) {
		// Using a DOM document to parse HTML output:
		$doc = new \DOMDocument();

		// Disable default error handling in order to catch warnings caused by malformed markup:
		libxml_use_internal_errors( true );

		// Try loading the HTML:
		$this->assertTrue( $doc->loadHTML( $html ), $message );

		// Check if no warnings have been thrown:
		$errorString = '';
		foreach( libxml_get_errors() as $error ) {
			$errorString .= "\r\n" . $error->message;
		}

		$this->assertEmpty( $errorString, 'Malformed markup:' . $errorString . ' ' . $message );

		// Clear error cache and re-enable default error handling:
		libxml_clear_errors();
		libxml_use_internal_errors();
	}

}
