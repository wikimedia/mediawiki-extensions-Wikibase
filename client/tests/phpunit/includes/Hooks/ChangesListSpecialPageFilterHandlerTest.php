<?php

namespace Wikibase\Client\Tests\Hooks;

use DerivativeContext;
use FauxRequest;
use RequestContext;
use Wikibase\Client\Hooks\ChangesListSpecialPageFilterHandler;

/**
 * @covers Wikibase\Client\Hooks\ChangesListSpecialPageFilterHandler
 *
 * @group WikibaseClientHooks
 * @group WikibaseClient
 * @group Wikibase
 */
class ChangesListSpecialPageFilterHandlerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider filterNotAddedWhenUsingEnhancedChangesProvider
	 */
	public function testFilterNotAddedWhenUsingEnhancedChanges(
		$enhancedChangesDefault,
		$enhancedMode,
		$pageName,
		$message
	) {
		$hookHandler = new ChangesListSpecialPageFilterHandler(
			$this->getRequest( $enhancedMode ),
			$this->getUser( $enhancedChangesDefault, false ),
			$pageName,
			true
		);

		$filters = array();
		$hookHandler->addFilterIfEnabled( $filters );

		$this->assertEquals( array(), $filters, $message );
	}

	public function filterNotAddedWhenUsingEnhancedChangesProvider() {
		return array(
			array( true, true, 'Watchlist', 'enhanced default pref and using in Wathclist' ),
			array( true, true, 'RecentChanges', 'enhanced default pref and using in RC' ),
			array( false, true, 'Watchlist', 'enhanced not default but using in Watchlist' ),
			array( false, true, 'RecentChanges', 'enhanced not default but using in RC' )
		);
	}

	/**
	 * @dataProvider changesPageProvider
	 */
	public function testFilterAddedWhenNotUsingEnhancedChanges(
		$expectedFilterName,
		$expectedFilterMessageKey,
		$specialPageName
	) {
		$hookHandler = new ChangesListSpecialPageFilterHandler(
			$this->getRequest( false ),
			$this->getUser( false, true ),
			$specialPageName,
			true
		);

		$filters = array();
		$hookHandler->addFilterIfEnabled( $filters );

		$expected = array(
			$expectedFilterName => array(
				'msg' => $expectedFilterMessageKey,
				'default' => false
			)
		);

		$this->assertEquals( $expected, $filters );
	}

	/**
	 * @dataProvider changesPageProvider
	 */
	public function testFilterAddedAndEnabledByDefault_WhenNotUsingEnhancedChanges(
		$expectedFilterName,
		$expectedFilterMessageKey,
		$specialPageName
	) {
		$hookHandler = new ChangesListSpecialPageFilterHandler(
			$this->getRequest( false ),
			$this->getUser( false, false ),
			$specialPageName,
			true
		);

		$filters = array();
		$hookHandler->addFilterIfEnabled( $filters );

		$expected = array(
			$expectedFilterName => array(
				'msg' => $expectedFilterMessageKey,
				'default' => true
			)
		);

		$this->assertEquals( $expected, $filters );
	}

	/**
	 * @dataProvider changesPageProvider
	 */
	public function testFilterNotAddedWhenExternalRecentChangesDisabled(
		$expectedFilterName,
		$expectedFilterMessageKey,
		$specialPageName
	) {
		$hookHandler = new ChangesListSpecialPageFilterHandler(
			$this->getRequest( false ),
			$this->getUser( false, false ),
			$specialPageName,
			false
		);

		$filters = array();
		$hookHandler->addFilterIfEnabled( $filters );

		$this->assertEquals( array(), $filters );
	}

	public function changesPageProvider() {
		return array(
			array( 'hideWikibase', 'wikibase-rc-hide-wikidata', 'Watchlist' ),
			array( 'hidewikidata', 'wikibase-rc-hide-wikidata', 'RecentChanges' ),
			array( 'hidewikidata', 'wikibase-rc-hide-wikidata', 'RecentChangesLinked' )
		);
	}

	private function getRequest( $enhancedMode ) {
		return new FauxRequest( array( 'enhanced' => $enhancedMode ) );
	}

	private function getUser( $enhancedChangesPref, $hideWikibaseEditsByDefault ) {
		$user = $this->getMockBuilder( 'User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->any() )
			->method( 'getOption' )
			->will( $this->returnCallback( function( $optionName ) use (
				$enhancedChangesPref,
				$hideWikibaseEditsByDefault
			) {
				if ( $optionName === 'usenewrc' ) {
					return $enhancedChangesPref;
				} else {
					return $hideWikibaseEditsByDefault;
				}
			} ) );

		return $user;
	}

}
