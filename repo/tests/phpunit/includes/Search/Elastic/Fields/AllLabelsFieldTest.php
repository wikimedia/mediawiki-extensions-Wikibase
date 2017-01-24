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

	public function getFieldDataProvider() {
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
	 * @dataProvider  getFieldDataProvider
	 * @param EntityDocument $entity
	 */
	public function testLabels( EntityDocument $entity ) {
		$labels = new AllLabelsField();
		$this->assertNull( $labels->getFieldData( $entity ) );
	}

}
