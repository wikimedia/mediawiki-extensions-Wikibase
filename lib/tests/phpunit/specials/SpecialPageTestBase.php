<?php

namespace Wikibase\Test;

use DerivativeContext;
use Exception;
use FauxRequest;
use FauxResponse;
use HttpStatus;
use MediaWikiTestCase;
use OutputPage;
use RequestContext;
use SpecialPage;
use User;
use WebRequest;

/**
 * Base class for testing special pages.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Adam Shorland
 */
abstract class SpecialPageTestBase extends MediaWikiTestCase {

	private $obLevel;

	protected function setUp() {
		parent::setUp();

		$this->obLevel = ob_get_level();
	}

	protected function tearDown() {
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
	 * @return SpecialPage
	 */
	protected abstract function newSpecialPage();

	/**
	 * @param string $sub The subpage parameter to call the page with
	 * @param WebRequest|null $request Web request that may contain URL parameters, etc
	 * @param string|null $language The language code which should be used in the context of this special page
	 * @param User|null $user The user which should be used in the context of this special page
	 *
	 * @throws Exception
	 * @return array( string, WebResponse ) A two-elements array containing the output generated
	 *         by the special page.
	 */
	protected function executeSpecialPage(
		$sub = '',
		WebRequest $request = null,
		$language = null,
		User $user = null
	) {
		if ( $request === null ) {
			$request = new FauxRequest();
		}

		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setRequest( $request );

		if ( $language !== null ) {
			$context->setLanguage( $language );
		}

		if ( $user !== null ) {
			$context->setUser( $user );
		}

		// FIXME: Documentation missing. How do you know "POST vs. GET" means "edit vs. read"?
		if ( $request->wasPosted() && !$request->getCheck( 'wpEditToken' ) ) {
			// If we are trying to edit and no token is set, supply one.
			$request->setVal( 'wpEditToken', $context->getUser()->getEditToken() );
		}

		$out = new OutputPage( $context );
		$context->setOutput( $out );

		$page = $this->newSpecialPage();
		$page->setContext( $context );

		$out->setTitle( $page->getPageTitle() );

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
		} catch ( Exception $ex ) {
			// PHP 5.3 doesn't have `finally`
			$exception = $ex;
		}

		// poor man's `finally` block
		ob_end_clean();

		// re-throw any errors after `finally` handling.
		if ( $exception ) {
			throw $exception;
		}

		$response = $request->response();

		if ( $response instanceof FauxResponse ) {
			$code = $response->getStatusCode();

			if ( $code > 0 ) {
				$response->header( 'Status: ' . $code . ' ' . HttpStatus::getMessage( $code ) );
			}
		}

		return array( $text, $response );
	}

}
