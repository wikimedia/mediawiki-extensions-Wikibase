<?php

namespace Wikibase\Repo\Tests\Hooks;

use MediaWiki\Message\Message;
use MediaWiki\Skin\Skin;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use Psr\Log\LoggerInterface;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Hooks\SidebarBeforeOutputHookHandler;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Repo\Hooks\SidebarBeforeOutputHookHandler
 *
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class SidebarBeforeOutputHookHandlerTest extends MediaWikiIntegrationTestCase {

	private DatabaseEntitySource $localEntitySource;
	private EntityNamespaceLookup $entityNamespaceLookup;
	private EntityIdLookup $entityIdLookup;
	private EntityLookup $entityLookup;
	/**
	 * @var \PHPUnit\Framework\MockObject\MockObject|Skin
	 */
	private $skin;
	/**
	 * @var \PHPUnit\Framework\MockObject\MockObject|Title
	 */
	private $mockTitle;
	/**
	 * @var \PHPUnit\Framework\MockObject\MockObject|EntityId
	 */
	private $mockEntityId;
	/**
	 * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $logger;

	private SettingsArray $wikibaseRepoSettings;
	private bool $hasEntity;

	protected function setUp(): void {
		parent::setUp();

		$this->localEntitySource = $this->createMock( DatabaseEntitySource::class );
		$this->localEntitySource->method( 'getConceptBaseUri' )->willReturn( 'http://foo' );

		$this->mockEntityId = $this->createMock( EntityId::class );

		$this->entityLookup = $this->createMock( EntityLookup::class );

		$this->entityIdLookup = $this->createMock( EntityIdLookup::class );

		$this->entityNamespaceLookup = $this->createMock( EntityNamespaceLookup::class );

		$this->skin = $this->createMock( Skin::class );
		$this->skin->method( 'msg' )->willReturn( $this->createMock( Message::class ) );

		$this->mockTitle = $this->createMock( Title::class );

		$this->logger = $this->createMock( LoggerInterface::class );

		$this->hasEntity = true;

		$this->entityLookup
			->method( 'hasEntity' )
			->willReturnCallback( function () {
				return $this->hasEntity;
			} );

		$this->entityIdLookup
			->method( 'getEntityIdForTitle' )
			->willReturn( $this->mockEntityId );

		$this->entityNamespaceLookup
			->method( 'isNamespaceWithEntities' )
			->willReturn( true );

		$this->skin->method( 'getTitle' )->willReturn( $this->mockTitle );
	}

	public function testBuildConceptUriLink() {
		$sidebar = [];
		$this->getHookHandler()->onSidebarBeforeOutput( $this->skin, $sidebar );

		$this->assertArrayHasKey( 'wb-concept-uri', $sidebar[ 'TOOLBOX' ] );
	}

	public function test_buildConceptUriLink_WithNoTitleReturnsNull() {
		$skin = $this->createMock( Skin::class );
		$skin->method( 'getTitle' )->willReturn( null );

		$sidebar = [];
		$this->getHookHandler()->onSidebarBeforeOutput( $skin, $sidebar );

		$this->assertArrayEquals( [], $sidebar );
	}

	public function test_buildConceptUriLink_WithNoEntityIdReturnsNull() {
		$this->entityIdLookup = $this->createMock( EntityIdLookup::class );
		$this->entityIdLookup->method( 'getEntityIdForTitle' )->willReturn( null );

		$sidebar = [];
		$this->getHookHandler()->onSidebarBeforeOutput( $this->skin, $sidebar );

		$this->assertArrayEquals( [], $sidebar );
	}

	public static function provideWikiProjectLinksData(): iterable {
		$item = new Item( new ItemId( 'Q1' ) );
		$item->getStatements()->addStatement( new Statement( new PropertyNoValueSnak( new NumericPropertyId( 'P2' ) ) ) );
		$item->getStatements()->addStatement( new Statement( new PropertyNoValueSnak( new NumericPropertyId( 'P222' ) ) ) );

		$itemWithoutStatements = new Item( new ItemId( 'Q2' ) );

		$matchingProject1 = [
			'propertyIds' => [ 'P1', 'P2', 'P3' ],
			'href' => 'project-url-1',
			'text' => 'WikiProject First',
		];

		$matchingProject2 = [
			'propertyIds' => [ 'P222' ],
			'href' => 'project-url-2',
			'text' => 'WikiProject Second',
		];

		$nonmatchingProject = [
			'propertyIds' => [ 'P4' ],
			'href' => 'project-url-3',
			'text' => 'WikiProject Third',
		];

		return [
			'with properties of interest' => [
				$item,
				[ $matchingProject1 ],
				true,
				[ [ 'href' => 'project-url-1', 'text' => 'WikiProject First' ] ],
			],
			'without properties of interest' => [
				$item,
				[ $nonmatchingProject ],
				false,
				[],
			],
			'with two matching projects' => [
				$item,
				[ $matchingProject2, $matchingProject1 ],
				true,
				[
					[ 'href' => 'project-url-2', 'text' => 'WikiProject Second' ],
					[ 'href' => 'project-url-1', 'text' => 'WikiProject First' ],
				],
			],
			'without projects' => [ $item, [], false, [] ],
			'item without statements' => [ $itemWithoutStatements, [ $matchingProject1 ], false, [] ],
			'no entity' => [ null, [ $matchingProject1 ], false, [] ],
		];
	}

	/**
	 * @dataProvider provideWikiProjectLinksData
	 */
	public function testBuildWikiProjectLinks( Item|null $item, array $wikiProjectConfig, bool $linksIncluded, array $expectedArray ) {
		$this->hasEntity = (bool)$item;
		$this->entityLookup
			->method( 'getEntity' )
			->willReturn( $item );

		$sidebar = [];
		$this->getHookHandler(
			[ 'tmpWikiProjectsLinking' => $wikiProjectConfig ]
		)->onSidebarBeforeOutput( $this->skin, $sidebar );

		if ( $linksIncluded ) {
			$this->assertArrayHasKey( 'wikibase-wikiprojects-sidebar-section', $sidebar );
			$this->assertArrayEquals( $expectedArray, $sidebar[ 'wikibase-wikiprojects-sidebar-section' ] );
		} else {
			$this->assertArrayNotHasKey( 'wikibase-wikiprojects-sidebar-section', $sidebar );
		}
	}

	public function testGetValidEntityId() {
		$this->assertSame(
			TestingAccessWrapper::newFromObject( $this->getHookHandler() )->getValidEntityId( $this->mockTitle ),
			$this->mockEntityId
		);
	}

	public function test_getValidEntityId_WithNoNamespaceReturnsNull() {
		$this->entityNamespaceLookup = $this->createMock( EntityNamespaceLookup::class );
		$this->entityNamespaceLookup->method( 'isNamespaceWithEntities' )->willReturn( false );

		$this->assertNull(
			TestingAccessWrapper::newFromObject( $this->getHookHandler() )->getValidEntityId( $this->mockTitle )
		);
	}

	public function test_getValidEntityId_WhenRedirectSkipEntityCheck() {
		$this->mockTitle = $this->createMock( Title::class );
		$this->mockTitle->method( 'isRedirect' )->willReturn( true );

		$this->entityLookup
			->expects( $this->never() )
			->method( 'hasEntity' );

		$this->assertSame(
			TestingAccessWrapper::newFromObject( $this->getHookHandler() )->getValidEntityId( $this->mockTitle ),
			$this->mockEntityId
		);
	}

	public function test_getValidEntityId_WhenEntityLookupThrowsReturnsNull() {
		$this->entityLookup = $this->createMock( EntityLookup::class );
		$this->entityLookup->method( 'hasEntity' )
			->willThrowException( new EntityLookupException( $this->mockEntityId ) );

		$this->assertNull(
			TestingAccessWrapper::newFromObject( $this->getHookHandler() )->getValidEntityId( $this->mockTitle )
		);
	}

	public function test_getValidEntityId_WhenEntityLookupThrowsLogsWarning() {
		$this->entityLookup = $this->createMock( EntityLookup::class );
		$this->entityLookup->method( 'hasEntity' )
			->willThrowException( new EntityLookupException( $this->mockEntityId ) );

		$this->logger
			->expects( $this->once() )
			->method( 'warning' );

		TestingAccessWrapper::newFromObject( $this->getHookHandler() )->getValidEntityId( $this->mockTitle );
	}

	public function test_getValidEntityId_WithNoEntityReturnsNull() {
		$this->entityLookup = $this->createMock( EntityLookup::class );
		$this->entityLookup->method( 'hasEntity' )->willReturn( false );

		$this->assertNull(
			TestingAccessWrapper::newFromObject( $this->getHookHandler() )->getValidEntityId( $this->mockTitle )
		);
	}

	private function getHookHandler( $settings = [] ): SidebarBeforeOutputHookHandler {
		$defaultSettings = [
			'tmpWikiProjectsLinking' => [],
		];

		return new SidebarBeforeOutputHookHandler(
			$this->entityIdLookup,
			$this->entityLookup,
			$this->entityNamespaceLookup,
			$this->localEntitySource,
			$this->logger,
			new SettingsArray( array_merge( $defaultSettings, $settings ) )
		);
	}
}
