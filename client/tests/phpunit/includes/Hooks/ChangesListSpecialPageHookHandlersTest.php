<?php

namespace Wikibase\Client\Tests\Hooks;

use ExtensionRegistry;
use FauxRequest;
use SpecialPageFactory;
use SpecialRecentChanges;
use User;
use Wikibase\Client\Hooks\ChangesListSpecialPageHookHandlers;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\LoadBalancer;
use Wikimedia\TestingAccessWrapper;

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

	public function testAddFilter() {
		$user = $this->getUser(
			[
				[ 'usenewrc' => 0 ]
			]
		);

		/** @var SpecialRecentChanges $specialPage */
		$specialPage = SpecialPageFactory::getPage( 'Recentchanges' );

		/** @var SpecialRecentChanges $wrappedSpecialPage */
		$wrappedSpecialPage = TestingAccessWrapper::newFromObject(
			$specialPage
		);

		/** @var ChangesListSpecialPageHookHandlers $hookHandler */
		$hookHandler = TestingAccessWrapper::newFromObject( new ChangesListSpecialPageHookHandlers(
			$this->getRequest( [] ),
			$this->getUser( [] ),
			$this->getLoadBalancer(),
			'Recentchanges',
			true
		) );

		$specialPage->getContext()->setUser( $user );

		// Register built-in filters, since the Wikidata one uses its group
		$wrappedSpecialPage->registerFiltersFromDefinitions(
			$wrappedSpecialPage->filterGroupDefinitions
		);

		$hookHandler->addFilter(
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

	/**
	 * @dataProvider getOptionNameProvider
	 */
	public function testGetOptionName( $expected, $pageName ) {
		/** @var ChangesListSpecialPageHookHandlers $hookHandler */
		$hookHandler = TestingAccessWrapper::newFromObject( new ChangesListSpecialPageHookHandlers(
			$this->getRequest( [] ),
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
			$this->getRequest( [] ),
			$this->getUser( [] ),
			$this->getLoadBalancer(),
			'Watchlist',
			true
		);

		$conds = [];
		$hookHandler->addWikibaseConditions(
			wfGetDB( DB_REPLICA ),
			$conds
		);

		$expected = [ "rc_source != 'wb'" ];

		$this->assertEquals( $expected, $conds );
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
