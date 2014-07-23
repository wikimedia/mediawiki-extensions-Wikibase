<?php

namespace Wikibase\Client\Tests\Hooks;

use DerivativeContext;
use FauxRequest;
use FormOptions;
use RequestContext;
use SpecialRecentChanges;
use Wikibase\Client\Hooks\ChangesPageWikibaseFilterHandler;

/**
 * @covers Wikibase\Client\Hooks\ChangesPageWikibaseFilterHandler
 *
 * @group WikibaseClientHooks
 * @group WikibaseClient
 * @group Wikibase
 */
class ChangesPageWikibaseFilterHandlerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider filterNotAddedWhenUsingEnhancedChangesProvider
	 */
	public function testFilterNotAddedWhenUsingEnhancedChanges(
		$enhancedChangesDefault,
		$useEnhancedChanges
	) {
		$special = $this->getSpecialPage( $enhancedChangesDefault, $useEnhancedChanges, false );

		$hookHandler = new ChangesPageWikibaseFilterHandler( $special, true );

		$filters = array();
		$filters = $hookHandler->handleHook( $filters, 'foo', 'bar', 'foobar' );

		$this->assertEquals( array(), $filters );
	}

	public function filterNotAddedWhenUsingEnhancedChangesProvider() {
		return array(
			array( true, true, 'enhanced changes default preference and using' ),
			array( false, true, 'enhanced changes not default but using' )
		);
	}

	public function testFilterAddedWhenNotUsingEnhancedChanges() {
		$special = $this->getSpecialPage( false, false, true );

		$hookHandler = new ChangesPageWikibaseFilterHandler( $special, true );

		$filters = array();
		$filters = $hookHandler->handleHook( $filters, 'foo', 'bar', 'foobar' );

		$expected = array(
			'foo' => array(
				'msg' => 'foobar',
				'default' => false
			)
		);

		$this->assertEquals( $expected, $filters );
	}

	public function testFilterAddedAndEnabledByDefault_WhenNotUsingEnhancedChanges() {
		$special = $this->getSpecialPage( false, false, false );

		$hookHandler = new ChangesPageWikibaseFilterHandler( $special, true );

		$filters = array();
		$filters = $hookHandler->handleHook( $filters, 'foo', 'bar', 'foobar' );

		$expected = array(
			'foo' => array(
				'msg' => 'foobar',
				'default' => true
			)
		);

		$this->assertEquals( $expected, $filters );
	}

	public function testFilterNotAddedWhenExternalRecentChangesDisabled() {
		$special = $this->getSpecialPage( false, false, false );

		$hookHandler = new ChangesPageWikibaseFilterHandler( $special,false );

		$filters = array();
		$filters = $hookHandler->handleHook( $filters, 'foo', 'bar', 'foobar' );

		$this->assertEquals( array(), $filters );
	}

	private function getSpecialPage( $enhancedChangesPref, $useEnhanced, $hideWikibaseEditsByDefault ) {
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setUser( $this->getUser( $enhancedChangesPref, $hideWikibaseEditsByDefault ) );

		$request = new FauxRequest( array( 'enhanced' => $useEnhanced ) );
		$context->setRequest( $request );

		$special = new SpecialRecentChanges();
		$special->setContext( $context );

		return $special;
	}

	private function getUser( $enhancedChangesPref, $hideWikibaseEditsByDefault ) {
		$user = $this->getMockBuilder( 'User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->any() )
			->method( 'getOption' )
			->will( $this->returnCallback( function( $optionName ) use(
					$enhancedChangesPref,
					$hideWikibaseEditsByDefault
				) {
					if ( $optionName === 'usenewrc' ) {
						return $enhancedChangesPref;
					} else {
						return $hideWikibaseEditsByDefault;
					}
				}
			) );

		return $user;
	}

}
