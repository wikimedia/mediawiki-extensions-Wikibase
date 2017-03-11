<?php

namespace Wikibase\Client\Tests\Hooks;

use ExtensionRegistry;
use FauxRequest;
use FormOptions;
use IDatabase;
use SpecialPageFactory;
use TestingAccessWrapper;
use User;
use Wikibase\Client\Hooks\ChangesListSpecialPageHookHandlers;
use Wikimedia\Rdbms\LoadBalancer;

/**
 * @covers Wikibase\Client\Hooks\ChangesListSpecialPageHookHandlers
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Matthew Flaschen < mflaschen@wikimedia.org >
 */
class ChangesListSpecialPageHookHandlersTest extends \PHPUnit_Framework_TestCase {
	protected $extensionRegistry;

	protected $oldExtensionRegistryLoaded;

	public function setUp() {
		parent::setUp();

		$this->extensionRegistry = TestingAccessWrapper::newFromObject(
			ExtensionRegistry::getInstance()
		);

		$this->oldExtensionRegistryLoaded = $this->extensionRegistry->loaded;

		// Forget about the other extensions.  Although ORES may be loaded,
		// since this is only unit-testing *our* listener, ORES's listener
		// hasn't run, so for all intents and purposes it's not loaded.
		$this->extensionRegistry->loaded = [ 'Wikibase' ];
	}

	public function tearDown() {
		parent::tearDown();

		$this->extensionRegistry->loaded = $this->oldExtensionRegistryLoaded;
	}

	public function testOnChangesListSpecialPageStructuredFilters() {
		$user = $this->getUser(
			array(
				array( 'usenewrc' => 0 )
			)
		);

		/** @var \ChangesListSpecialPage $specialPage */
		$specialPage = SpecialPageFactory::getPage( 'Recentchanges' );

		$wrappedSpecialPage = TestingAccessWrapper::newFromObject(
			$specialPage
		);

		$specialPage->getContext()->setUser( $user );

		// Register built-in filters, since the Wikidata one uses its group
		$wrappedSpecialPage->registerFiltersFromDefinitions(
			$wrappedSpecialPage->filterGroupDefinitions
		);

		ChangesListSpecialPageHookHandlers::onChangesListSpecialPageStructuredFilters(
			$specialPage
		);

		$changeType = $specialPage->getFilterGroup( 'changeType' );
		$filter = $changeType->getFilter( 'hideWikibase' );

		// I could do all of getJsData(), but that would make it brittle to
		// unrelated changes.
		$expectedFields = [
			'label' => 'wikibase-rcfilters-hide-wikibase-label',
			'description' => 'wikibase-rcfilters-hide-wikibase-description',
			'showHide' => 'wikibase-rc-hide-wikidata',
			'default' => true,
		];

		$actualFields = [
			'label' => $filter->getLabel(),
			'description' => $filter->getDescription(),
			'showHide' => $filter->getShowHide(),
			'default' => $filter->getDefault(),
		];

		$this->assertSame(
			$expectedFields,
			$actualFields
		);
	}

	public function testOnChangesListSpecialPageQuery() {
		$tables = array( 'recentchanges' );
		$fields = array( 'rc_id' );
		$conds = array();
		$query_options = array();
		$join_conds = array();

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
	 * @dataProvider addWikibaseConditionsIfFilterUnavailableProvider
	 */
	public function testAddWikibaseConditionsIfFilterUnavailable( $expectedAddConditionsCalls, $hasWikibaseChangesEnabled ) {
		$hookHandlerMock = $this->getMockBuilder( ChangesListSpecialPageHookHandlers::class )
			->setConstructorArgs(
				[
					$this->getRequest( array() ),
					$this->getUser( [] ),
					$this->getLoadBalancer(),
					'Recentchanges',
					true
				]
			)
			->setMethods( [
					'hasWikibaseChangesEnabled',
					'addWikibaseConditions'
				] )
			->getMock();

		$hookHandlerMock->method( 'hasWikibaseChangesEnabled' )->will(
			$this->returnValue( $hasWikibaseChangesEnabled )
		);

		$hookHandlerMock->expects( $this->exactly( $expectedAddConditionsCalls ) )
			->method( 'addWikibaseConditions' )
			->with(
				$this->isInstanceOf( IDatabase::class ),
				$this->equalTo( [] )
			);

		$conds = [];

		$hookHandlerMock = TestingAccessWrapper::newFromObject( $hookHandlerMock );
		$hookHandlerMock->__call( 'addWikibaseConditionsIfFilterUnavailable', [ &$conds ] );
	}

	public function addWikibaseConditionsIfFilterUnavailableProvider() {
		return [
			[
				0,
				true
			],

			[
				1,
				false
			],
		];
	}

	/**
	 * @dataProvider getOptionNameProvider
	 */
	public function testGetOptionName( $expected, $pageName ) {
		$hookHandler = TestingAccessWrapper::newFromObject( new ChangesListSpecialPageHookHandlers(
			$this->getRequest( array() ),
			$this->getUser( [] ),
			$this->getLoadBalancer(),
			$pageName,
			true
		) );

		$this->assertSame(
			$expected,
			$hookHandler->getOptionName()
		);
	}

	public function getOptionNameProvider() {
		return [
			[
				'wlshowwikibase',
				'Watchlist'
			],

			[
				'rcshowwikidata',
				'Recentchanges',
			],

			[
				'rcshowwikidata',
				'Recentchangeslinked'
			],
		];
	}

	public function testAddWikibaseConditions() {
		$hookHandler = new ChangesListSpecialPageHookHandlers(
			$this->getRequest( array() ),
			$this->getUser( [] ),
			$this->getLoadBalancer(),
			'Watchlist',
			true
		);

		$conds = array();
		$hookHandler->addWikibaseConditions(
			wfGetDB( DB_REPLICA ),
			$conds
		);

		$expected = [ "rc_source != 'wb'" ];

		$this->assertEquals( $expected, $conds );
	}

	/**
	 * @dataProvider hasWikibaseChangesEnabledWhenUsingEnhancedChangesProvider
	 */
	public function testHasWikibaseChangesEnabledWhenUsingEnhancedChanges(
		array $requestParams,
		array $userOptions,
		$pageName,
		$message
	) {
		$hookHandler = TestingAccessWrapper::newFromObject( new ChangesListSpecialPageHookHandlers(
			$this->getRequest( $requestParams ),
			$this->getUser( $userOptions ),
			$this->getLoadBalancer(),
			$pageName,
			true
		) );

		$this->assertSame(
			false,
			$hookHandler->hasWikibaseChangesEnabled(),
			$message
		);
	}

	public function hasWikibaseChangesEnabledWhenUsingEnhancedChangesProvider() {
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
	 * @dataProvider hasWikibaseChangesEnabled_withoutShowWikibaseEditsByDefaultPreferenceProvider
	 */
	public function testHasWikibaseChangesEnabled_withoutShowWikibaseEditsByDefaultPreference(
		array $requestParams,
		array $userOptions,
		$expectedFilterName,
		$expectedToggleDefault,
		$specialPageName
	) {
		$hookHandler = TestingAccessWrapper::newFromObject( new ChangesListSpecialPageHookHandlers(
			$this->getRequest( $requestParams ),
			$this->getUser( $userOptions ),
			$this->getLoadBalancer(),
			$specialPageName,
			true
		) );

		$this->assertSame(
			true,
			$hookHandler->hasWikibaseChangesEnabled()
		);
	}

	public function hasWikibaseChangesEnabled_withoutShowWikibaseEditsByDefaultPreferenceProvider() {
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
				'hideWikibase',
				true,
				'RecentChanges'
			),
			array(
				array(),
				array( 'usenewrc' => 0 ),
				'hideWikibase',
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
	 * @dataProvider hasWikibaseChangesEnabled_withShowWikibaseEditsByDefaultPreferenceProvider
	 */
	public function testHasWikibaseChangesEnabled_withShowWikibaseEditsByDefaultPreference(
		array $requestParams,
		array $userOptions,
		$expectedFilterName,
		$expectedToggleDefault,
		$specialPageName
	) {
		$hookHandler = TestingAccessWrapper::newFromObject( new ChangesListSpecialPageHookHandlers(
			$this->getRequest( $requestParams ),
			$this->getUser( $userOptions ),
			$this->getLoadBalancer(),
			$specialPageName,
			true
		) );

		$this->assertSame(
			true,
			$hookHandler->hasWikibaseChangesEnabled()
		);
	}

	public function hasWikibaseChangesEnabled_withShowWikibaseEditsByDefaultPreferenceProvider() {
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
				'hideWikibase',
				false,
				'RecentChanges'
			),
			array(
				array(),
				array( 'rcshowwikidata' => 1, 'usenewrc' => 0 ),
				'hideWikibase',
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
	 * @dataProvider hasWikibaseChangesEnabledWhenExternalRecentChangesDisabledProvider() {
	 */
	public function testHasWikibaseChangesEnabledWhenExternalRecentChangesDisabled( $specialPageName ) {
		$hookHandler = TestingAccessWrapper::newFromObject( new ChangesListSpecialPageHookHandlers(
			$this->getRequest( array() ),
			$this->getUser( array( 'usenewrc' => 0 ) ),
			$this->getLoadBalancer(),
			$specialPageName,
			/* $showExternalChanges= */ false
		) );

		$this->assertSame(
			false,
			$hookHandler->hasWikibaseChangesEnabled()
		);
	}

	public function hasWikibaseChangesEnabledWhenExternalRecentChangesDisabledProvider() {
		return array(
			array( 'Watchlist' ),
			array( 'Recentchanges' ),
			array( 'Recentchangeslinked' )
		);
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
