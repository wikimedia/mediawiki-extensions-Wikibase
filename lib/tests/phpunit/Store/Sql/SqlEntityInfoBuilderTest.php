<?php

namespace Wikibase\Lib\Tests\Store\Sql;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\Store\Sql\SqlEntityInfoBuilder;
use Wikibase\Lib\Tests\Store\EntityInfoBuilderTest;

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
		$this->tablesUsed[] = 'wb_entity_per_page';

		$termRows = [];
		$infoRows = [];
		$eppRows = [];

		$pageId = 1000;

		foreach ( $this->getKnownEntities() as $entity ) {
			$id = $entity->getId();

			$eppRows[] = [
				$entity->getType(),
				$id->getNumericId(),
				$pageId++,
				null
			];

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

			$eppRows[] = [
				$fromId->getEntityType(),
				$fromId->getNumericId(),
				$pageId++,
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

		$eppColumns = [ 'epp_entity_type', 'epp_entity_id', 'epp_page_id', 'epp_redirect_target' ];

		$this->insertRows(
			'wb_entity_per_page',
			$eppColumns,
			$eppRows );
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
			[ $itemId ],
			false,
			'foo'
		);

		$builder->collectTerms();

		$entityInfo = $builder->getEntityInfo()->getEntityInfo( $itemId );

		$this->assertSame( $label, $entityInfo['labels'][$languageCode]['value'] );
	}

}
