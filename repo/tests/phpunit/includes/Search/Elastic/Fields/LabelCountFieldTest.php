<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\Search\Elastic\Fields\LabelCountField;

/**
 * @covers Wikibase\Repo\Search\Elastic\Fields\LabelCountField
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LabelCountFieldTest extends \PHPUnit_Framework_TestCase {

	public function testGetMapping() {
		$labelCountField = new LabelCountField();

		$expected = array(
			'type' => 'integer'
		);

		$this->assertSame( $expected, $labelCountField->getMapping() );
	}

	public function testGetFieldData() {
		$labelCountField = new LabelCountField();

		$item = new Item();
		$item->getFingerprint()->setLabel( 'es', 'Gato' );

		$this->assertSame( 1, $labelCountField->getFieldData( $item ) );
	}

}
