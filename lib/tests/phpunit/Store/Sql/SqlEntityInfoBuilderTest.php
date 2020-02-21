<?php

namespace Wikibase\Lib\Tests\Store\Sql;

use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;
use Title;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\ItemContent;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\Sql\SqlEntityInfoBuilder;
use Wikibase\Lib\Tests\Store\EntityInfoBuilderTestCase;
use Wikibase\PropertyContent;
use Wikibase\WikibaseSettings;
use Wikipage;

/**
 * @covers \Wikibase\Lib\Store\Sql\SqlEntityInfoBuilder
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibasePropertyInfo
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class SqlEntityInfoBuilderTest extends EntityInfoBuilderTestCase {

	const ITEM_NAMESPACE_ID = 120;
	const PROPERTY_NAMESPACE_ID = 122;

	protected function setUp() : void {
		parent::setUp();

		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( 'Entity info tables are not available locally on the client' );
		}

		$this->tablesUsed[] = 'wb_property_info';
		$this->tablesUsed[] = 'wb_terms';
		$this->tablesUsed[] = 'page';
		$this->tablesUsed[] = 'redirect';
		$this->tablesUsed[] = 'revision';
		$this->tablesUsed[] = 'ip_changes';

		$termRows = [];
		$infoRows = [];
		$redirectRows = [];

		foreach ( $this->getKnownEntities() as $entity ) {
			$this->createPage( $entity );

			$id = $entity->getId();
			$labels = $entity->getLabels()->toTextArray();
			$descriptions = $entity->getDescriptions()->toTextArray();
			$aliases = $entity->getAliasGroups()->toTextArray();

			$termRows = array_merge( $termRows, $this->getTermRows( $id, 'label', $labels ) );
			$termRows = array_merge( $termRows, $this->getTermRows( $id, 'description', $descriptions ) );
			$termRows = array_merge( $termRows, $this->getTermRows( $id, 'alias', $aliases ) );

			if ( $entity instanceof Property ) {
				$infoRows[] = [
					$id->getNumericId(),
					$entity->getDataTypeId(),
					'{"type":"' . $entity->getDataTypeId() . '"}'
				];
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
			'wb_terms',
			[
				'term_entity_type',
				'term_entity_id',
				'term_full_entity_id',
				'term_type',
				'term_language',
				'term_text',
				'term_search_key'
			],
			$termRows );

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

	private function getTermRows( EntityId $id, $termType, $terms ) {
		$rows = [];

		foreach ( $terms as $lang => $langTerms ) {
			$langTerms = (array)$langTerms;

			foreach ( $langTerms as $term ) {
				$rows[] = [
					$id->getEntityType(),
					0,
					$id->getSerialization(),
					$termType,
					$lang,
					$term,
					$term
				];
			}
		}

		return $rows;
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

	/**
	 * @return SqlEntityInfoBuilder
	 */
	protected function newEntityInfoBuilder() {
		return new SqlEntityInfoBuilder(
			new BasicEntityIdParser(),
			new EntityIdComposer( [
				'item' => function( $repositoryName, $uniquePart ) {
					return new ItemId( 'Q' . $uniquePart );
				},
				'property' => function( $repositoryName, $uniquePart ) {
					return new PropertyId( 'P' . $uniquePart );
				},
			] ),
			$this->getEntityNamespaceLookup(),
			new NullLogger(),
			new EntitySource(
				'testsource',
				false,
				[
					'item' => [ 'namespaceId' => self::ITEM_NAMESPACE_ID, 'slot' => 'main' ],
					'property' => [ 'namespaceId' => self::PROPERTY_NAMESPACE_ID, 'slot' => 'main' ],
				],
				'',
				'',
				'',
				''
			),
			$this->getCache()
		);
	}

	/**
	 * @return EntityIdComposer
	 */
	private function getIdComposer() {
		return $this->getMockBuilder( EntityIdComposer::class )
			->disableOriginalConstructor()
			->getMock();
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

		$builder = new SqlEntityInfoBuilder(
			new BasicEntityIdParser(),
			$this->getIdComposer(),
			$this->getEntityNamespaceLookup(),
			new NullLogger(),
			new EntitySource( 'source', false, [ 'item' => [ 'namespaceId' => self::ITEM_NAMESPACE_ID, 'slot' => 'main' ] ], '', '', '', '' ),
			$this->getCache()
		);

		$entityInfo = $builder->collectEntityInfo( [ $itemId, $propertyId ], [] );

		$this->assertTrue( $entityInfo->hasEntityInfo( $itemId ) );
		$this->assertFalse( $entityInfo->hasEntityInfo( $propertyId ) );
	}

}
