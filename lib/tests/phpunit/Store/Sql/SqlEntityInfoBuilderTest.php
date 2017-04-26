<?php

namespace Wikibase\Lib\Tests\Store\Sql;

use InvalidArgumentException;
use Title;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\ItemContent;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\Sql\SqlEntityInfoBuilder;
use Wikibase\Lib\Tests\Store\EntityInfoBuilderTest;
use Wikibase\PropertyContent;
use Wikipage;

/**
 * @covers Wikibase\Lib\Store\Sql\SqlEntityInfoBuilder
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibasePropertyInfo
 * @group Database
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class SqlEntityInfoBuilderTest extends EntityInfoBuilderTest {

	protected function setUp() {
		parent::setUp();

		if ( !defined( 'WB_VERSION' ) ) {
			$this->markTestSkipped( 'Entity info tables are not available locally on the client' );
		}

		$this->tablesUsed[] = 'wb_property_info';
		$this->tablesUsed[] = 'wb_terms';
		$this->tablesUsed[] = 'page';
		$this->tablesUsed[] = 'redirect';
		$this->tablesUsed[] = 'revision';

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
					$id->getNumericId(),
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
		$page->doEditContent( $content, "testing", EDIT_NEW );

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
	 * @param EntityId[] $ids
	 *
	 * @return SqlEntityInfoBuilder
	 */
	protected function newEntityInfoBuilder( array $ids ) {
		return new SqlEntityInfoBuilder(
			new ItemIdParser(),
			new EntityIdComposer( [
				'item' => function( $repositoryName, $uniquePart ) {
					return new ItemId( 'Q' . $uniquePart );
				},
				'property' => function( $repositoryName, $uniquePart ) {
					return new PropertyId( 'P' . $uniquePart );
				},
			] ),
			$this->getEntityNamespaceLookup(),
			$ids
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
	 * @return EntityNamespaceLookup
	 */
	private function getEntityNamespaceLookup() {
		return new EntityNamespaceLookup( [ 'item' => 0, 'property' => 122 ] );
	}

	public function provideInvalidConstructorArguments() {
		return [
			'neither string nor false as database name (int)' => [ 100, '' ],
			'neither string nor false as database name (null)' => [ null, '' ],
			'neither string nor false as database name (true)' => [ true, '' ],
			'not a string as a repository name' => [ false, 1000 ],
			'string containing colon as a repository name' => [ false, 'foo:oo' ],
		];
	}

	/**
	 * @dataProvider provideInvalidConstructorArguments
	 */
	public function testGivenInvalidArguments_constructorThrowsException( $databaseName, $repositoryName ) {
		$this->setExpectedException( InvalidArgumentException::class );

		new SqlEntityInfoBuilder(
			new ItemIdParser(),
			$this->getIdComposer(),
			$this->getEntityNamespaceLookup(),
			[],
			$databaseName,
			$repositoryName
		);
	}

	public function testConstructorIgnoresEntityIdsFromOtherRepositories() {
		$itemId = new ItemId( 'Q1' );
		$propertyId = new PropertyId( 'foo:P1' );

		$builder = new SqlEntityInfoBuilder(
			new ItemIdParser(),
			$this->getIdComposer(),
			$this->getEntityNamespaceLookup(),
			[ $itemId, $propertyId ],
			false,
			''
		);

		$this->assertTrue( $builder->getEntityInfo()->hasEntityInfo( $itemId ) );
		$this->assertFalse( $builder->getEntityInfo()->hasEntityInfo( $propertyId ) );
	}

	public function testEntityIdsArePrefixedWithRepositoryName() {
		$itemId = new ItemId( 'foo:Q1' );

		// Inserting a dummy label for item with numeric ID part equal to 1.
		// In this test local database is used to pretend to be a databse of
		// repository "foo". Terms fetched from the database should be
		// matched to entity IDs using correct repository prefixes (this
		// builder's responsibility as this information is not stored in wb_terms table).

		$label = 'dummy label';
		$languageCode = 'en';

		$this->insertRows(
			'wb_terms',
			[
				'term_entity_type',
				'term_entity_id',
				'term_type',
				'term_language',
				'term_text',
				'term_search_key'
			],
			[
				[
					$itemId->getEntityType(),
					$itemId->getNumericId(),
					'label',
					$languageCode,
					$label,
					$label
				]
			]
		);

		$builder = new SqlEntityInfoBuilder(
			new ItemIdParser(),
			new EntityIdComposer( [
				'item' => function( $repositoryName, $uniquePart ) {
					return ItemId::newFromRepositoryAndNumber( $repositoryName, $uniquePart );
				},
			] ),
			$this->getEntityNamespaceLookup(),
			[ $itemId ],
			false,
			'foo'
		);

		$builder->collectTerms();

		$entityInfo = $builder->getEntityInfo()->getEntityInfo( $itemId );

		$this->assertSame( $label, $entityInfo['labels'][$languageCode]['value'] );
	}

}
