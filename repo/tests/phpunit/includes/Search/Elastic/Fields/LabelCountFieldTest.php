<?php

namespace Wikibase\Repo\Tests\Search\Elastic\Fields;

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

		$expected = array(
			'type' => 'integer'
		);

		$this->assertSame( $expected, $labelCountField->getMapping() );
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
		$item->setLabel( 'es', 'Gato' );

		return array(
			array( 1, $item ),
			array( 0, Property::newFromType( 'string' ) )
		);
	}

}
