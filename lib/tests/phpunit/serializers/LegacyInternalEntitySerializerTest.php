<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\Serializers\LegacyInternalEntitySerializer;

/**
 * @covers Wikibase\Lib\Serializers\LegacyInternalEntitySerializer
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class LegacyInternalEntitySerializerTest extends \PHPUnit_Framework_TestCase {

	public function entityProvider() {
		$empty = Item::newEmpty();

		$withLabels = Item::newEmpty();
		$withLabels->setLabel( 'en', 'Hello' );
		$withLabels->setLabel( 'es', 'Holla' );

		return array(
			array( $empty ),
			array( $withLabels ),
		);
	}

	/**
	 * @dataProvider entityProvider
	 * @param Entity $entity
	 */
	public function testSerialize( Entity $entity ) {
		$serializer = new LegacyInternalEntitySerializer();
		$data = $serializer->serialize( $entity );

		$this->assertEquals( $entity->toArray(), $data );
	}

}
