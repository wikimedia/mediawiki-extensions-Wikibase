<?php

namespace Wikibase\Repo\Tests\Search\Elastic\Fields;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\Search\Elastic\Fields\SiteLinkCountField;
use Wikibase\Repo\Search\Elastic\Fields\WikibaseNumericField;

/**
 * @covers Wikibase\Repo\Search\Elastic\Fields\SiteLinkCountField
 *
 * @group WikibaseElastic
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SiteLinkCountFieldTest extends WikibaseNumericFieldTest {

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
