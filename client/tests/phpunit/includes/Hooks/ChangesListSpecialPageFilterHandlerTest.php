<?php

namespace Wikibase\Client\Tests\Hooks;

use FauxRequest;
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
		array $requestParams,
		array $userPreferences,
		$pageName,
		$message
	) {
		$hookHandler = new ChangesListSpecialPageFilterHandler(
			$this->getRequest( $requestParams ),
			$this->getUser( $userPreferences ),
			$pageName,
			true
		);

		$filters = array();
		$hookHandler->addFilterIfEnabled( $filters );

		$this->assertSame( array(), $filters, $message );
	}

	public function filterNotAddedWhenUsingEnhancedChangesProvider() {
		return array(
			array(
				array(),
				array( 'usenewrc' => 1 ),
				'Watchlist',
				'enhanced default pref for Watchlist'
			),
			array(
				array(),
				array( 'usenewrc' => 1 ),
				'RecentChanges',
				'enhanced default pref for RecentChanges'
			),
			array(
				array( 'enhanced' => 1 ),
				array( 'usenewrc' => 0 ),
				'Watchlist',
				'enhanced not default but has enhanced=1 req param'
			),
			array(
				array( 'enhanced' => 1 ),
				array( 'usenewrc' => 0 ),
				'RecentChanges',
				'enhanced not default but has enhanced=1 req param'
			),
			array(
				array( 'enhanced' => 1 ),
				array( 'usenewrc' => 1 ),
				'Watchlist',
				'enhanced default and has enhanced=1 req param'
			),
			array(
				array( 'enhanced' => 1 ),
				array( 'usenewrc' => 1 ),
				'RecentChangesLinked',
				'enhanced default and has enhanced=1 req param'
			),
		);
	}

	/**
	 * @dataProvider filter_withoutShowWikibaseEditsByDefaultPreference
	 */
	public function testFilter_withoutShowWikibaseEditsByDefaultPreference(
		array $requestParams,
		array $userPreferences,
		$expectedFilterName,
		$expectedToggleDefault,
		$specialPageName
	) {
		$hookHandler = new ChangesListSpecialPageFilterHandler(
			$this->getRequest( $requestParams ),
			$this->getUser( $userPreferences ),
			$specialPageName,
			true
		);

		$filters = array();
		$hookHandler->addFilterIfEnabled( $filters );

		$expected = array(
			$expectedFilterName => array(
				'msg' => 'wikibase-rc-hide-wikidata',
				'default' => $expectedToggleDefault
			)
		);

		$this->assertSame( $expected, $filters );
	}

	public function filter_withoutShowWikibaseEditsByDefaultPreference() {
		return array(
			array(
				array(),
				array( 'usenewrc' => 0 ),
				'hideWikibase',
				true,
				'Watchlist'
			),
			array(
				array( 'enhanced' => 0 ),
				array( 'usenewrc' => 1 ),
				'hidewikidata',
				true,
				'RecentChanges'
			),
			array(
				array(),
				array( 'usenewrc' => 0 ),
				'hidewikidata',
				true,
				'RecentChangesLinked'
			),
			array(
				array( 'action' => 'submit', 'hideWikibase' => 0 ),
				array( 'usenewrc' => 0 ),
				'hideWikibase',
				false,
				'Watchlist'
			),
			array(
				array( 'action' => 'submit', 'hideWikibase' => 1 ),
				array( 'usenewrc' => 0 ),
				'hideWikibase',
				true,
				'Watchlist'
			),
			array(
				array( 'action' => 'submit' ),
				array( 'usenewrc' => 0 ),
				'hideWikibase',
				false,
				'Watchlist'
			)
		);
	}

	/**
	 * @dataProvider filter_withShowWikibaseEditsByDefaultPreference
	 */
	public function testFilter_withShowWikibaseEditsByDefaultPreference(
		array $requestParams,
		array $userPreferences,
		$expectedFilterName,
		$expectedToggleDefault,
		$specialPageName
	) {
		$hookHandler = new ChangesListSpecialPageFilterHandler(
			$this->getRequest( $requestParams ),
			$this->getUser( $userPreferences ),
			$specialPageName,
			true
		);

		$filters = array();
		$hookHandler->addFilterIfEnabled( $filters );

		$expected = array(
			$expectedFilterName => array(
				'msg' => 'wikibase-rc-hide-wikidata',
				'default' => $expectedToggleDefault
			)
		);

		$this->assertSame( $expected, $filters );
	}

	public function filter_withShowWikibaseEditsByDefaultPreference() {
		return array(
			array(
				array(),
				array( 'wlshowwikibase' => 1, 'usenewrc' => 0 ),
				'hideWikibase',
				false,
				'Watchlist'
			),
			array(
				array( 'enhanced' => 0 ),
				array( 'rcshowwikidata' => 1, 'usenewrc' => 1 ),
				'hidewikidata',
				false,
				'RecentChanges'
			),
			array(
				array(),
				array( 'rcshowwikidata' => 1, 'usenewrc' => 0 ),
				'hidewikidata',
				false,
				'RecentChangesLinked'
			),
			array(
				array( 'action' => 'submit', 'hideWikibase' => 0 ),
				array( 'wlshowwikibase' => 1, 'usenewrc' => 0 ),
				'hideWikibase',
				false,
				'Watchlist'
			),
			array(
				array( 'action' => 'submit' ),
				array( 'wlshowwikibase' => 1, 'usenewrc' => 0 ),
				'hideWikibase',
				false,
				'Watchlist'
			)
		);
	}

	/**
	 * @dataProvider filterNotAddedWhenExternalRecentChangesDisabledProvider() {
	 */
	public function testFilterNotAddedWhenExternalRecentChangesDisabled( $specialPageName ) {
		$hookHandler = new ChangesListSpecialPageFilterHandler(
			$this->getRequest( array() ),
			$this->getUser( array( 'usenewrc' => 0 ) ),
			$specialPageName,
			false
		);

		$filters = array();
		$hookHandler->addFilterIfEnabled( $filters );

		$this->assertSame( array(), $filters );
	}

	public function filterNotAddedWhenExternalRecentChangesDisabledProvider() {
		return array(
			array( 'Watchlist' ),
			array( 'RecentChanges' ),
			array( 'RecentChangesLinked' )
		);
	}

	private function getRequest( array $requestParams ) {
		return new FauxRequest( $requestParams );
	}

	private function getUser( array $options ) {
		$user = $this->getMockBuilder( 'User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->any() )
			->method( 'getOption' )
			->will( $this->returnCallback( function( $optionName ) use ( $options ) {
				foreach ( $options as $key => $value ) {
					if ( $optionName === $key ) {
						return $value;
					}
				}

				return null;
			} ) );

		return $user;
	}

}
