<?php

namespace Wikibase\Lib\Tests;

use MediaWikiCoversValidator;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\EntityFactory;

/**
 * @covers \Wikibase\Lib\EntityFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class EntityFactoryTest extends \PHPUnit\Framework\TestCase {
	use MediaWikiCoversValidator;

	private function getEntityFactory() {
		$instantiators = [
			'item' => function() {
				return new Item();
			},
			'property' => function() {
				return Property::newFromType( 'string' );
			},
		];

		return new EntityFactory( $instantiators );
	}

	public function provideNewEmpty() {
		return [
			[ 'item', Item::class ],
			[ 'property', Property::class ],
		];
	}

	/**
	 * @dataProvider provideNewEmpty
	 */
	public function testNewEmpty( $type, $class ) {
		$entity = $this->getEntityFactory()->newEmpty( $type );

		$this->assertInstanceOf( $class, $entity );
		$this->assertTrue( $entity->isEmpty(), 'should be empty' );
	}

}
