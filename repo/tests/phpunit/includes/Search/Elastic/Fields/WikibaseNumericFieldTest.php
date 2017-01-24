<?php
namespace Wikibase\Repo\Tests\Search\Elastic\Fields;

use DummySearchIndexFieldDefinition;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Repo\Search\Elastic\Fields\WikibaseNumericField;

/**
 * Base class for testing numeric fields.
 */
abstract class WikibaseNumericFieldTest extends PHPUnit_Framework_TestCase {

	public function testGetMapping() {
		$field = $this->getFieldObject();
		$searchEngine = $this->getMockBuilder( 'SearchEngine' )->getMock();

		$searchEngine->expects( $this->any() )
			->method( 'makeSearchFieldMapping' )
			->will( $this->returnCallback( function ( $name, $type ) {
				return new DummySearchIndexFieldDefinition( $name, $type );
			} ) );

		$mapping = $field->getMappingField( $searchEngine, get_class( $field ) )
			->getMapping( $searchEngine );
		$this->assertEquals( \SearchIndexField::INDEX_TYPE_INTEGER, $mapping['type'] );
		$this->assertEquals( get_class( $field ), $mapping['name'] );
	}

	/**
	 * @dataProvider getFieldDataProvider
	 */
	public function testGetFieldData( $expected, EntityDocument $entity ) {
		$labelCountField = $this->getFieldObject();

		$this->assertSame( $expected, $labelCountField->getFieldData( $entity ) );
	}

	abstract public function getFieldDataProvider();

	/**
	 * @return WikibaseNumericField
	 */
	abstract protected function getFieldObject();

}
