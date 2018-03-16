<?php

namespace WikibaseSearchElastic\Tests\Fields;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use WikibaseSearchElastic\Tests\Fields\WikibaseNumericFieldTestCase;
use WikibaseSearchElastic\Fields\StatementCountField;
use WikibaseSearchElastic\Fields\WikibaseNumericField;

/**
 * @covers \WikibaseSearchElastic\Fields\StatementCountField
 *
 * @group WikibaseElastic
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class StatementCountFieldTest extends WikibaseNumericFieldTestCase {

	/**
	 * @return \WikibaseSearchElastic\Fields\WikibaseNumericField
	 */
	protected function getFieldObject() {
		return new \WikibaseSearchElastic\Fields\StatementCountField();
	}

	public function getFieldDataProvider() {
		$item = new Item();
		$item->getStatements()->addNewStatement( new PropertyNoValueSnak( 1 ) );

		return [
			[ 1, $item ],
			[ 0, Property::newFromType( 'string' ) ]
		];
	}

}
