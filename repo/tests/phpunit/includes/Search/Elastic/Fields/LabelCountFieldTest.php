<?php

namespace Wikibase\Repo\Tests\Search\Elastic\Fields;

use DummySearchIndexFieldDefinition;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\Search\Elastic\Fields\LabelCountField;

/**
 * @covers Wikibase\Repo\Search\Elastic\Fields\LabelCountField
 *
 * @group WikibaseElastic
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LabelCountFieldTest extends PHPUnit_Framework_TestCase {

	public function testGetMapping() {
		$labelCountField = new LabelCountField();

		$searchEngine = $this->getMockBuilder( 'SearchEngine' )->getMock();

		$searchEngine->expects( $this->any() )
			->method( 'makeSearchFieldMapping' )
			->will( $this->returnCallback( function ( $name, $type ) {
				return new DummySearchIndexFieldDefinition( $name, $type );
			} ) );

		$mapping =
			$labelCountField->getMapping( $searchEngine, "label_field" )
				->getMapping( $searchEngine );
		$this->assertEquals( \SearchIndexField::INDEX_TYPE_INTEGER, $mapping['type'] );
		$this->assertEquals( "label_field", $mapping['name'] );
	}

	/**
	 * @dataProvider getFieldDataProvider
	 */
	public function testGetFieldData( $expected, EntityDocument $entity ) {
		$labelCountField = new LabelCountField();

		$this->assertSame( $expected, $labelCountField->getFieldData( $entity ) );
	}

	public function getFieldDataProvider() {
		$item = new Item();
		$item->getFingerprint()->setLabel( 'es', 'Gato' );

		return array(
			array( 1, $item ),
			array( 0, Property::newFromType( 'string' ) )
		);
	}

}
