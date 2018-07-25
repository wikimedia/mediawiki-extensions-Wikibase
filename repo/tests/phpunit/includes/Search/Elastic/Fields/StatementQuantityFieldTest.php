<?php

namespace Wikibase\Repo\Tests\Search\Elastic\Fields;

use CirrusSearch;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Repo\Search\Elastic\Fields\StatementQuantityField;
use Wikibase\Repo\Tests\Rdf\RdfBuilderTestData;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Search\Elastic\Fields\StatementQuantityField
 *
 * @group WikibaseElastic
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementQuantityFieldTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/**
	 * List of properties we handle.
	 * @var string[]
	 */
	private $properties = [ 'P1', 'P2', 'P4', 'P7', 'P8' ];
	private $propertiesForQuantity = [ 'P6' ];

	public function statementsProvider() {
		$testData = new RdfBuilderTestData(
			__DIR__ . '/../../../../data/rdf/entities', ''
		);

		return [
			'not a StatementListProvider' => [
				$this->getMockBuilder( EntityDocument::class )->getMock(),
				[]
			],
			'entity with no statements' => [
				$testData->getEntity( 'Q1' ),
				[]
			],
			'entity with statements but no qualifiers' => [
				$testData->getEntity( 'Q4' ),
				[]
			],
			'entity with statements, one with a quantity qualifier' => [
				$testData->getEntity( 'Q6' ),
				[
					'P7=string|20',
				]
			],
		];
	}

	private function getPropertyTypeLookup() {
		$lookup = $this->getMockBuilder( PropertyDataTypeLookup::class )->getMock();

		$lookup->method( 'getDataTypeIdForProperty' )
			->willReturn( 'DOES_NOT_MATTER' );

		return $lookup;
	}

	private function createStatementQuantityField() {
		return new StatementQuantityField(
			$this->getPropertyTypeLookup(),
			$this->properties,
			[],
			[],
			WikibaseRepo::getDefaultInstance()
				->getDataTypeDefinitions()
				->getSearchIndexDataFormatterCallbacks(),
			$this->propertiesForQuantity
		);
	}

	/**
	 * @dataProvider statementsProvider
	 */
	public function testGetFieldData( $entity, array $expected ) {
		if ( !class_exists( CirrusSearch::class ) ) {
			$this->markTestSkipped( 'CirrusSearch needed.' );
		}

		$field = $this->createStatementQuantityField();
		$this->assertEquals( $expected, $field->getFieldData( $entity ) );
	}

	public function testGetMapping() {
		if ( !class_exists( CirrusSearch::class ) ) {
			$this->markTestSkipped( 'CirrusSearch needed.' );
		}

		$field = $this->createStatementQuantityField();
		$searchEngine = $this->getMockBuilder( CirrusSearch::class )->getMock();
		$this->assertInternalType( 'array', $field->getMapping( $searchEngine ) );
	}

	public function testGetMappingNotCirrus() {
		$field = $this->createStatementQuantityField();
		$searchEngine = $this->getMockBuilder( \SearchEngine::class )->getMock();
		$this->assertEmpty( $field->getMapping( $searchEngine ) );
	}

}
