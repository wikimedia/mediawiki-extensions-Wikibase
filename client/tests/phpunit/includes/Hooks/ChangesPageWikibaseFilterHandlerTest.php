<?php

namespace Wikibase\Client\Tests\Hooks;

use DerivativeContext;
use FauxRequest;
use RequestContext;
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
		$context = $this->getContext( $enhancedChangesDefault, $useEnhancedChanges, false );
		$hookHandler = new ChangesPageWikibaseFilterHandler( $context, true, 'foo', 'bar', 'foobar' );

		$filters = array();
		$filters = $hookHandler->addFilterIfEnabled( $filters );

		$this->assertEquals( array(), $filters );
	}

	public function filterNotAddedWhenUsingEnhancedChangesProvider() {
		return array(
			array( true, true, 'enhanced changes default preference and using' ),
			array( false, true, 'enhanced changes not default but using' )
		);
	}

	public function testFilterAddedWhenNotUsingEnhancedChanges() {
		$context = $this->getContext( false, false, true );
		$hookHandler = new ChangesPageWikibaseFilterHandler( $context, true, 'foo', 'bar', 'foobar' );

		$filters = array();
		$filters = $hookHandler->addFilterIfEnabled( $filters );

		$expected = array(
			'foo' => array(
				'msg' => 'foobar',
				'default' => false
			)
		);

		$this->assertEquals( $expected, $filters );
	}

	public function testFilterAddedAndEnabledByDefault_WhenNotUsingEnhancedChanges() {
		$context = $this->getContext( false, false, false );
		$hookHandler = new ChangesPageWikibaseFilterHandler( $context, true, 'foo', 'bar', 'foobar' );

		$filters = array();
		$filters = $hookHandler->addFilterIfEnabled( $filters );

		$expected = array(
			'foo' => array(
				'msg' => 'foobar',
				'default' => true
			)
		);

		$this->assertEquals( $expected, $filters );
	}

	public function testFilterNotAddedWhenExternalRecentChangesDisabled() {
		$context = $this->getContext( false, false, false );
		$hookHandler = new ChangesPageWikibaseFilterHandler( $context, false, 'foo', 'bar', 'foobar' );

		$filters = array();
		$filters = $hookHandler->addFilterIfEnabled( $filters );

		$this->assertEquals( array(), $filters );
	}

	private function getContext( $enhancedChangesPref, $useEnhanced, $hideWikibaseEditsByDefault ) {
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setUser( $this->getUser( $enhancedChangesPref, $hideWikibaseEditsByDefault ) );

		$request = new FauxRequest( array( 'enhanced' => $useEnhanced ) );
		$context->setRequest( $request );

		return $context;
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
