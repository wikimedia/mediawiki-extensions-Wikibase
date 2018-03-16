<?php

namespace WikibaseSearchElastic\Tests\Fields;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use WikibaseSearchElastic\Tests\Fields\WikibaseNumericFieldTestCase;
use WikibaseSearchElastic\Fields\LabelCountField;
use WikibaseSearchElastic\Fields\WikibaseNumericField;

/**
 * @covers \WikibaseSearchElastic\Fields\LabelCountField
 *
 * @group WikibaseElastic
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LabelCountFieldTest extends WikibaseNumericFieldTestCase {

	public function getFieldDataProvider() {
		$item = new Item();
		$item->setLabel( 'es', 'Gato' );

		return [
			[ 1, $item ],
			[ 0, Property::newFromType( 'string' ) ]
		];
	}

	/**
	 * @return WikibaseNumericField
	 */
	protected function getFieldObject() {
		return new \WikibaseSearchElastic\Fields\LabelCountField();
	}

}
