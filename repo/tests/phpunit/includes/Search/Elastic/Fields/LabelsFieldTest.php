<?php

namespace Wikibase\Repo\Tests\Search\Elastic\Fields;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\Search\Elastic\Fields\LabelsField;

/**
 * @covers Wikibase\Repo\Search\Elastic\Fields\LabelsField
 *
 * @group WikibaseElastic
 * @group Wikibase
 *
 */
class LabelsFieldTest extends PHPUnit_Framework_TestCase {

	public function getFieldDataProvider() {
		$item = new Item();
		$item->getFingerprint()->setLabel( 'es', 'Gato' );
		$item->getFingerprint()->setLabel( 'ru', 'Кошка' );
		$item->getFingerprint()->setLabel( 'de', 'Katze' );
		$item->getFingerprint()->setLabel( 'fr', 'Chat' );

		$prop = Property::newFromType( 'string' );
		$prop->getFingerprint()->setLabel( 'en', 'astrological sign' );
		$prop->getFingerprint()->setLabel( 'ru', 'знак зодиака' );
		$prop->getFingerprint()->setAliasGroup( 'en', [ 'zodiac sign' ] );
		$prop->getFingerprint()->setAliasGroup( 'es', [ 'signo zodiacal' ] );

		$mock = $this->getMock( EntityDocument::class );

		return [
			[
				[
					'es' => [ 'Gato' ],
					'ru' => [ 'Кошка' ],
					'de' => [ 'Katze' ],
					'fr' => [ 'Chat' ]
				],
				$item
			],
			[
				[
					'en' => [ 'astrological sign', 'zodiac sign' ],
					'ru' => [ 'знак зодиака' ],
					'es' => [ '', 'signo zodiacal' ],
				],
				$prop
			],
			[ [], $mock ]
		];
	}

	/**
	 * @dataProvider  getFieldDataProvider
	 * @param $expected
	 * @param EntityDocument $entity
	 */
	public function testLabels( $expected, EntityDocument $entity ) {
		$labels = new LabelsField( [ 'en', 'es', 'ru', 'de' ] );
		$this->assertEquals( $expected, $labels->getFieldData( $entity ) );
	}

}
