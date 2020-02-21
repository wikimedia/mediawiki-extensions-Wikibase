<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use MediaWiki\MediaWikiServices;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;
use Title;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\ItemContent;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\Sql\Terms\DatabaseEntityInfoBuilder;
use Wikibase\Lib\Store\Sql\Terms\DatabaseItemTermStoreWriter;
use Wikibase\Lib\Store\Sql\Terms\DatabasePropertyTermStoreWriter;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsAcquirer;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermStoreCleaner;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsResolver;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Lib\Tests\Store\EntityInfoBuilderTestCase;
use Wikibase\PropertyContent;
use Wikibase\StringNormalizer;
use Wikibase\WikibaseSettings;
use Wikipage;

/**
 * @covers \Wikibase\Lib\Store\Sql\Terms\DatabaseEntityInfoBuilder
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibasePropertyInfo
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class DatabaseEntityInfoBuilderTest extends EntityInfoBuilderTestCase {

	const REPOSITORY_PREFIX_BASED_FEDERATION = false;
	const ENTITY_SOURCE_BASED_FEDERATION = true;

	const ITEM_NAMESPACE_ID = 120;
	const PROPERTY_NAMESPACE_ID = 122;

	protected function setUp() : void {
		parent::setUp();
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( 'Entity info tables are not available locally on the client' );
		}

		$tables = [
			'wb_property_info',
			'page',
			'redirect',
			'revision',
			'ip_changes',
			'comment',
			'slots',
			'actor',
			'wbt_item_terms',
			'wbt_property_terms',
			'wbt_term_in_lang',
			'wbt_text_in_lang',
			'wbt_text',
			'wbt_type'
		];

		$this->tablesUsed = array_merge( $this->tablesUsed, $tables );

		$infoRows = [];
		$redirectRows = [];

		$loadBalancerFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		$loadBalancer = $loadBalancerFactory->getMainLB();
		$typeIdsStore = new DatabaseTypeIdsStore(
			$loadBalancer,
			MediaWikiServices::getInstance()->getMainWANObjectCache()
		);

		$itemTermStoreWriter = new DatabaseItemTermStoreWriter(
			$loadBalancer,
			new DatabaseTermInLangIdsAcquirer(
				$loadBalancerFactory,
				$typeIdsStore
			),
			new DatabaseTermStoreCleaner(
				$loadBalancer
			),
			new StringNormalizer(),
			$this->getItemSource()
		);

		$propertyTermStoreWriter = new DatabasePropertyTermStoreWriter(
			$loadBalancer,
			new DatabaseTermInLangIdsAcquirer(
				$loadBalancerFactory,
				$typeIdsStore
			),
			new DatabaseTermStoreCleaner(
				$loadBalancer
			),
			new StringNormalizer(),
			$this->getPropertySource()
		);

		foreach ( $this->getKnownEntities() as $entity ) {
			$this->createPage( $entity );

			if ( $entity instanceof Property ) {
				$propertyTermStoreWriter->storeTerms( $entity->getId(), $entity->getFingerprint() );
				$infoRows[] = [
					$entity->getId()->getNumericId(),
					$entity->getDataTypeId(),
					'{"type":"' . $entity->getDataTypeId() . '"}'
				];
			} elseif ( $entity instanceof Item ) {
				$itemTermStoreWriter->storeTerms( $entity->getId(), $entity->getFingerprint() );
			}
		}

		foreach ( $this->getKnownRedirects() as $from => $toId ) {
			$fromId = new ItemId( $from );

			$page = $this->createPage( new Item( $fromId ) );
			$redirectRows[] = [
				$page->getId(),
				$this->getEntityNamespaceLookup()->getEntityNamespace( $fromId->getEntityType() ),
				$toId->getSerialization()
			];
		}
		$this->insertRows(
			'wb_property_info',
			[ 'pi_property_id', 'pi_type', 'pi_info' ],
			$infoRows );

		$redirectColumns = [ 'rd_from', 'rd_namespace', 'rd_title' ];

		$this->insertRows(
			'redirect',
			$redirectColumns,
			$redirectRows );
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return Wikipage|null
	 */
	private function createPage( EntityDocument $entity ) {

		if ( $entity->getType() == Item::ENTITY_TYPE ) {
			$empty = new Item( $entity->getId() );
			$content = ItemContent::newFromItem( $empty );
		} elseif ( $entity->getType() == Property::ENTITY_TYPE ) {
			$empty = new Property( $entity->getId(), null, $entity->getDataTypeId() );
			$content = PropertyContent::newFromProperty( $empty );
		} else {
			return null;
		}
		$page = WikiPage::factory( Title::newFromText(
			$entity->getId()->getSerialization(),
			$this->getEntityNamespaceLookup()->getEntityNamespace( $entity->getType() )
		) );
		$page->doEditContent( $content, 'testing', EDIT_NEW );

		return $page;
	}

	private function insertRows( $table, array $fields, array $rows ) {
		$dbw = wfGetDB( DB_MASTER );

		foreach ( $rows as $row ) {
			$row = array_slice( $row, 0, count( $fields ) );

			$dbw->insert(
				$table,
				array_combine( $fields, $row ),
				__METHOD__,
				// Just ignore insertation errors... if similar data already is in the DB
				// it's probably good enough for the tests (as this is only testing for UNIQUE
				// fields anyway).
				[ 'IGNORE' ]
			);
		}
	}

	private function getItemSource() {
		return new EntitySource( 'test', false, [ 'item' => [ 'namespaceId' => 10, 'slot' => 'main' ] ], '', '', '', '' );
	}

	private function getPropertySource() {
		return new EntitySource( 'test', false, [ 'property' => [ 'namespaceId' => 123, 'slot' => 'main' ] ], '', '', '', '' );
	}

	/**
	 * @return DatabaseEntityInfoBuilder
	 */
	protected function newEntityInfoBuilder() {
		$loadBalancer = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$typeIdsStore = new DatabaseTypeIdsStore(
			$loadBalancer,
			MediaWikiServices::getInstance()->getMainWANObjectCache()
		);
		return new DatabaseEntityInfoBuilder(
			new BasicEntityIdParser(),
			$this->getIdComposer(),
			$this->getEntityNamespaceLookup(),
			new NullLogger(),
			new EntitySource( 'source', false, [ 'item' => [ 'namespaceId' => self::ITEM_NAMESPACE_ID, 'slot' => 'main' ] ], '', '', '', '' ),
			$this->getCache(),
			$loadBalancer,
			new DatabaseTermInLangIdsResolver(
				$typeIdsStore,
				$typeIdsStore,
				$loadBalancer
			)
		);
	}

	/**
	 * @return EntityIdComposer
	 */
	private function getIdComposer() {
		return new EntityIdComposer( [
			'item' => function( $repositoryName, $uniquePart ) {
				return new ItemId( 'Q' . $uniquePart );
			},
			'property' => function( $repositoryName, $uniquePart ) {
				return new PropertyId( 'P' . $uniquePart );
			},
		] );
	}

	/**
	 * @return \PHPUnit\Framework\MockObject\MockObject|CacheInterface
	 */
	private function getCache() {
		$mock = $this->createMock( CacheInterface::class );
		$mock->method( 'get' )
			->willReturn( false );

		return $mock;
	}

	/**
	 * @return EntityNamespaceLookup
	 */
	private function getEntityNamespaceLookup() {
		return new EntityNamespaceLookup( [ 'item' => self::ITEM_NAMESPACE_ID, 'property' => self::PROPERTY_NAMESPACE_ID ] );
	}

	public function testIgnoresEntityIdsFromOtherEntitySources() {
		$itemId = new ItemId( 'Q1' );
		$propertyId = new PropertyId( 'P2' );
		$loadBalancer = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$typeIdsStore = new DatabaseTypeIdsStore(
			$loadBalancer,
			MediaWikiServices::getInstance()->getMainWANObjectCache()
		);
		$builder = new DatabaseEntityInfoBuilder(
			new BasicEntityIdParser(),
			$this->getIdComposer(),
			$this->getEntityNamespaceLookup(),
			new NullLogger(),
			new EntitySource( 'source', false, [ 'item' => [ 'namespaceId' => self::ITEM_NAMESPACE_ID, 'slot' => 'main' ] ], '', '', '', '' ),
			$this->getCache(),
			$loadBalancer,
			new DatabaseTermInLangIdsResolver(
				$typeIdsStore,
				$typeIdsStore,
				$loadBalancer
			)
		);
		$entityInfo = $builder->collectEntityInfo( [ $itemId, $propertyId ], [] );

		$this->assertTrue( $entityInfo->hasEntityInfo( $itemId ) );
		$this->assertFalse( $entityInfo->hasEntityInfo( $propertyId ) );
	}

}
