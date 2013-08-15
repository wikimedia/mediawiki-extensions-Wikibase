<?php

namespace Wikibase\Test;

/**
 * Base class for testing special pages.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Adam Shorland
 */
abstract class SpecialPageTestBase extends \MediaWikiTestCase {

	protected $obLevel;

	public function setUp() {
		parent::setUp();

		$this->obLevel = ob_get_level();
	}

	public function tearDown() {
		$obLevel = ob_get_level();

		while ( ob_get_level() > $this->obLevel ) {
			ob_end_clean();
		}

		if ( $obLevel !== $this->obLevel ) {
			$this->fail( "Test changed output buffer level: was {$this->obLevel} before test, but $obLevel after test.");
		}

		parent::tearDown();
	}

	/**
	 * Returns a new instance of the special page under test.
	 *
	 * @return \SpecialPage
	 */
	protected abstract function newSpecialPage();

	/**
	 * @param string      $sub The subpage parameter to call the page with
	 * @param \WebRequest $request Web request that may contain URL parameters, etc
	 *
	 * @return array array( String, \WebResponse ) containing the output generated
	 *         by the special page.
	 */
	protected function executeSpecialPage( $sub = '', \WebRequest $request = null ) {
		if ( !$request ) {
			$request = new \FauxRequest();
		}

		$response = $request->response();

		$context = new \DerivativeContext( \RequestContext::getMain() );
		$context->setRequest( $request );

		$out = new \OutputPage( $context );
		$context->setOutput( $out );

		$page = $this->newSpecialPage();
		$page->setContext( $context );

		$out->setTitle( $page->getTitle() );

		ob_start();

		$exception = null;
		try {
			$page->execute( $sub );

			if ( $out->getRedirect() !== '' ) {
				$out->output();
				$text = ob_get_contents();
			} elseif ( $out->isDisabled() ) {
				$text = ob_get_contents();
			} else {
				$text = $out->getHTML();
			}
		} catch ( \Exception $ex ) {
			// PHP 5.3 doesn't have `finally`
			$exception = $ex;
		}

		// poor man's `finally` block
		ob_end_clean();

		// re-throw any errors after `finally` handling.
		if ( $exception ) {
			throw $exception;
		}

		$code = $response->getStatusCode();

		if ( $code > 0 ) {
			$response->header( "Status: " . $code . ' ' . \HttpStatus::getMessage( $code ) );
		}

		return array( $text, $response );
	}

}
