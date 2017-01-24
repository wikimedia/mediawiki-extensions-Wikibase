<?php

namespace Wikibase\Repo\Tests\Search\Elastic\Fields;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\Search\Elastic\Fields\AllLabelsField;

/**
 * @covers Wikibase\Repo\Search\Elastic\Fields\AllLabelsField
 *
 * @group WikibaseElastic
 * @group Wikibase
 *
 */
class AllLabelsFieldTest extends PHPUnit_Framework_TestCase {

	public function provideFieldData() {
		$item = new Item();
		$item->getFingerprint()->setLabel( 'es', 'Gato' );

		$prop = Property::newFromType( 'string' );
		$prop->getFingerprint()->setLabel( 'en', 'astrological sign' );

		$mock = $this->getMock( EntityDocument::class );

		return [
			[ $item ],
			[ $prop ],
			[ $mock ]
		];
	}

	/**
	 * @dataProvider provideFieldData
	 * @param EntityDocument $entity
	 */
	public function testGetFieldData( EntityDocument $entity ) {
		$labels = new AllLabelsField();
		$this->assertNull( $labels->getFieldData( $entity ) );
	}

	public function testGetMapping() {
		$labels = new AllLabelsField();

		$searchEngine = $this->getMockBuilder( 'CirrusSearch' )->getMock();
		$searchEngine->expects( $this->never() )->method( 'makeSearchFieldMapping' );

		$mapping = $labels->getMapping( $searchEngine );
		$this->assertArrayHasKey( 'fields', $mapping );
		$this->assertCount( 2, $mapping['fields'] );
		$this->assertEquals( 'text', $mapping['type'] );
		$this->assertEquals( 'false', $mapping['index'] );
	}

}
