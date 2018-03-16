<?php

namespace WikibaseSearchElastic\Tests\Fields;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use WikibaseSearchElastic\Tests\Fields\WikibaseNumericFieldTestCase;
use WikibaseSearchElastic\Fields\SiteLinkCountField;
use WikibaseSearchElastic\Fields\WikibaseNumericField;

/**
 * @covers Wikibase\Repo\Search\Elastic\Fields\SiteLinkCountField
 *
 * @group WikibaseElastic
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SiteLinkCountFieldTest extends WikibaseNumericFieldTestCase {

	/**
	 * @return WikibaseNumericField
	 */
	protected function getFieldObject() {
		return new SiteLinkCountField();
	}

	public function getFieldDataProvider() {
		$item = new Item();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Kitten' );
		$item->getSiteLinkList()->addNewSiteLink( 'eswiki', 'Gato' );

		return [
			[ 2, $item ],
			[ 0, Property::newFromType( 'string' ) ]
		];
	}

}
