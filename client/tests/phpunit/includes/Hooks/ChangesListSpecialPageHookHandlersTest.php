<?php

namespace Wikibase\Client\Tests\Hooks;

use FauxRequest;
use FormOptions;
use IDatabase;
use LoadBalancer;
use SpecialPageFactory;
use User;
use Wikibase\Client\Hooks\ChangesListSpecialPageHookHandlers;

/**
 * @covers Wikibase\Client\Hooks\ChangesListSpecialPageHookHandlers
 *
 * @group WikibaseClientHooks
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ChangesListSpecialPageHookHandlersTest extends \PHPUnit_Framework_TestCase {

	public function testOnChangesListSpecialPageFilters() {
		$user = $this->getUser(
			[
				[ 'usernewrc' => 0 ]
			]
		);

		$specialPage = SpecialPageFactory::getPage( 'Recentchanges' );
		$specialPage->getContext()->setUser( $user );

		$filters = [];

		ChangesListSpecialPageHookHandlers::onChangesListSpecialPageFilters(
			$specialPage,
			$filters
		);

		$expected = [
			'hideWikibase' => [
				'msg' => 'wikibase-rc-hide-wikidata',
				'default' => true
			]
		];

		$this->assertSame( $expected, $filters );
	}

	public function testOnChangesListSpecialPageQuery() {
		$tables = [ 'recentchanges' ];
		$fields = [ 'rc_id' ];
		$conds = [];
		$query_options = [];
		$join_conds = [];

		$opts = new FormOptions();

		ChangesListSpecialPageHookHandlers::onChangesListSpecialPageQuery(
			'RecentChanges',
			$tables,
			$fields,
			$conds,
			$query_options,
			$join_conds,
			$opts
		);

		// this is just a sanity check
		$this->assertInternalType( 'array', $conds );
	}

	/**
	 * @dataProvider addWikibaseConditionsProvider
	 */
	public function testAddWikibaseConditions(
		array $expected,
		array $userOptions,
		$optionDefault
	) {
		$hookHandler = new ChangesListSpecialPageHookHandlers(
			$this->getRequest( [] ),
			$this->getUser( $userOptions ),
			$this->getLoadBalancer(),
			'Watchlist',
			true
		);

		$opts = new FormOptions();
		$opts->add( 'hideWikibase', $optionDefault );

		$conds = [];
		$hookHandler->addWikibaseConditions( $conds, $opts );

		$this->assertEquals( $expected, $conds );
	}

	public function addWikibaseConditionsProvider() {
		return [
			[
				[],
				[ 'usenewrc' => 0, 'wlshowwikibase' => 1 ],
				false
			],
			[
				[ "rc_source != 'wb'" ],
				[ 'usenewrc' => 0 ],
				true
			]
		];
	}

	public function testAddWikibaseConditions_wikibaseChangesDisabled() {
		$hookHandler = new ChangesListSpecialPageHookHandlers(
			$this->getRequest( [] ),
			$this->getUser( [ 'usenewrc' => 1 ] ),
			$this->getLoadBalancer(),
			'Watchlist',
			true
		);

		$opts = new FormOptions();

		$conds = [];
		$hookHandler->addWikibaseConditions( $conds, $opts );

		$this->assertEquals( [ "rc_source != 'wb'" ], $conds );
	}

	/**
	 * @dataProvider filterNotAddedWhenUsingEnhancedChangesProvider
	 */
	public function testFilterNotAddedWhenUsingEnhancedChanges(
		array $requestParams,
		array $userOptions,
		$pageName,
		$message
	) {
		$hookHandler = new ChangesListSpecialPageHookHandlers(
			$this->getRequest( $requestParams ),
			$this->getUser( $userOptions ),
			$this->getLoadBalancer(),
			$pageName,
			true
		);

		$filters = [];
		$hookHandler->addFilterIfEnabled( $filters );

		$this->assertSame( [], $filters, $message );
	}

	public function filterNotAddedWhenUsingEnhancedChangesProvider() {
		return [
			[
				[],
				[ 'usenewrc' => 1 ],
				'Watchlist',
				'enhanced default pref for Watchlist'
			],
			[
				[],
				[ 'usenewrc' => 1 ],
				'RecentChanges',
				'enhanced default pref for RecentChanges'
			],
			[
				[ 'enhanced' => 1 ],
				[ 'usenewrc' => 0 ],
				'Watchlist',
				'enhanced not default but has enhanced=1 req param'
			],
			[
				[ 'enhanced' => 1 ],
				[ 'usenewrc' => 0 ],
				'RecentChanges',
				'enhanced not default but has enhanced=1 req param'
			],
			[
				[ 'enhanced' => 1 ],
				[ 'usenewrc' => 1 ],
				'Watchlist',
				'enhanced default and has enhanced=1 req param'
			],
			[
				[ 'enhanced' => 1 ],
				[ 'usenewrc' => 1 ],
				'RecentChangesLinked',
				'enhanced default and has enhanced=1 req param'
			],
		];
	}

	/**
	 * @dataProvider filter_withoutShowWikibaseEditsByDefaultPreference
	 */
	public function testFilter_withoutShowWikibaseEditsByDefaultPreference(
		array $requestParams,
		array $userOptions,
		$expectedFilterName,
		$expectedToggleDefault,
		$specialPageName
	) {
		$hookHandler = new ChangesListSpecialPageHookHandlers(
			$this->getRequest( $requestParams ),
			$this->getUser( $userOptions ),
			$this->getLoadBalancer(),
			$specialPageName,
			true
		);

		$filters = [];
		$hookHandler->addFilterIfEnabled( $filters );

		$expected = [
			$expectedFilterName => [
				'msg' => 'wikibase-rc-hide-wikidata',
				'default' => $expectedToggleDefault
			]
		];

		$this->assertSame( $expected, $filters );
	}

	public function filter_withoutShowWikibaseEditsByDefaultPreference() {
		return [
			[
				[],
				[ 'usenewrc' => 0 ],
				'hideWikibase',
				true,
				'Watchlist'
			],
			[
				[ 'enhanced' => 0 ],
				[ 'usenewrc' => 1 ],
				'hideWikibase',
				true,
				'RecentChanges'
			],
			[
				[],
				[ 'usenewrc' => 0 ],
				'hideWikibase',
				true,
				'RecentChangesLinked'
			],
			[
				[ 'action' => 'submit', 'hideWikibase' => 0 ],
				[ 'usenewrc' => 0 ],
				'hideWikibase',
				false,
				'Watchlist'
			],
			[
				[ 'action' => 'submit', 'hideWikibase' => 1 ],
				[ 'usenewrc' => 0 ],
				'hideWikibase',
				true,
				'Watchlist'
			],
			[
				[ 'action' => 'submit' ],
				[ 'usenewrc' => 0 ],
				'hideWikibase',
				false,
				'Watchlist'
			]
		];
	}

	/**
	 * @dataProvider filter_withShowWikibaseEditsByDefaultPreference
	 */
	public function testFilter_withShowWikibaseEditsByDefaultPreference(
		array $requestParams,
		array $userOptions,
		$expectedFilterName,
		$expectedToggleDefault,
		$specialPageName
	) {
		$hookHandler = new ChangesListSpecialPageHookHandlers(
			$this->getRequest( $requestParams ),
			$this->getUser( $userOptions ),
			$this->getLoadBalancer(),
			$specialPageName,
			true
		);

		$filters = [];
		$hookHandler->addFilterIfEnabled( $filters );

		$expected = [
			$expectedFilterName => [
				'msg' => 'wikibase-rc-hide-wikidata',
				'default' => $expectedToggleDefault
			]
		];

		$this->assertSame( $expected, $filters );
	}

	public function filter_withShowWikibaseEditsByDefaultPreference() {
		return [
			[
				[],
				[ 'wlshowwikibase' => 1, 'usenewrc' => 0 ],
				'hideWikibase',
				false,
				'Watchlist'
			],
			[
				[ 'enhanced' => 0 ],
				[ 'rcshowwikidata' => 1, 'usenewrc' => 1 ],
				'hideWikibase',
				false,
				'RecentChanges'
			],
			[
				[],
				[ 'rcshowwikidata' => 1, 'usenewrc' => 0 ],
				'hideWikibase',
				false,
				'RecentChangesLinked'
			],
			[
				[ 'action' => 'submit', 'hideWikibase' => 0 ],
				[ 'wlshowwikibase' => 1, 'usenewrc' => 0 ],
				'hideWikibase',
				false,
				'Watchlist'
			],
			[
				[ 'action' => 'submit' ],
				[ 'wlshowwikibase' => 1, 'usenewrc' => 0 ],
				'hideWikibase',
				false,
				'Watchlist'
			]
		];
	}

	/**
	 * @dataProvider filterNotAddedWhenExternalRecentChangesDisabledProvider() {
	 */
	public function testFilterNotAddedWhenExternalRecentChangesDisabled( $specialPageName ) {
		$hookHandler = new ChangesListSpecialPageHookHandlers(
			$this->getRequest( [] ),
			$this->getUser( [ 'usenewrc' => 0 ] ),
			$this->getLoadBalancer(),
			$specialPageName,
			false
		);

		$filters = [];
		$hookHandler->addFilterIfEnabled( $filters );

		$this->assertSame( [], $filters );
	}

	public function filterNotAddedWhenExternalRecentChangesDisabledProvider() {
		return [
			[ 'Watchlist' ],
			[ 'RecentChanges' ],
			[ 'RecentChangesLinked' ]
		];
	}

	private function getRequest( array $requestParams ) {
		return new FauxRequest( $requestParams );
	}

	/**
	 * @param array $userOptions
	 *
	 * @return User
	 */
	private function getUser( array $userOptions ) {
		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->any() )
			->method( 'getOption' )
			->will( $this->returnCallback( function( $optionName ) use ( $userOptions ) {
				foreach ( $userOptions as $key => $value ) {
					if ( $optionName === $key ) {
						return $value;
					}
				}

				return null;
			} ) );

		return $user;
	}

	/**
	 * @return LoadBalancer
	 */
	private function getLoadBalancer() {
		$databaseBase = $this->getMockBuilder( IDatabase::class )
			->disableOriginalConstructor()
			->getMock();

		$databaseBase->expects( $this->any() )
			->method( 'addQuotes' )
			->will( $this->returnCallback( function( $input ) {
				return "'$input'";
			} ) );

		$loadBalancer = $this->getMockBuilder( LoadBalancer::class )
			->disableOriginalConstructor()
			->getMock();

		$loadBalancer->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $databaseBase ) );

		return $loadBalancer;
	}

}
