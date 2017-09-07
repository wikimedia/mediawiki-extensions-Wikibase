<?php

namespace Wikibase\Repo\Tests\Search\Elastic\Fields;

use CirrusSearch;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Repo\Search\Elastic\Fields\StatementsField;
use Wikibase\Repo\Tests\Rdf\RdfBuilderTestData;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Search\Elastic\Fields\StatementsField
 *
 * @group WikibaseElastic
 * @group Wikibase
 *
 */
class StatementsFieldTest extends PHPUnit_Framework_TestCase {

	/**
	 * List of properties we handle.
	 * @var string[]
	 */
	private $properties = [ 'P1', 'P2', 'P4', 'P7', 'P8' ];

	public function statementsProvider() {
		$testData = new RdfBuilderTestData(
			__DIR__ . '/../../../../data/rdf/entities', ''
		);

		return [
			'empty' => [
				$testData->getEntity('Q1'),
				[]
			],
			'Q4' => [
				$testData->getEntity('Q4'),
				[ 'P2=Q42', 'P2=Q666', 'P7=simplestring' ]
			],
			'Q7' => [
				$testData->getEntity('Q7'),
				[ 'P7=string', 'P7=string2' ]
			],
			'Q8' => [
				$testData->getEntity('Q8'),
				[]
			],
		];
	}

	/**
	 * @dataProvider statementsProvider
	 * @param EntityDocument $entity
	 * @param $expected
	 */
	public function testStatements( EntityDocument $entity, $expected ) {
		if ( !class_exists( CirrusSearch::class ) ) {
			$this->markTestSkipped( 'CirrusSearch needed.' );
		}
		$repo = WikibaseRepo::getDefaultInstance();

		$field = new StatementsField( $this->properties, $repo->getDataTypeDefinitions()->getIndexDataFormatters() );
		$this->assertEquals( $expected, $field->getFieldData( $entity ) );
	}

}
