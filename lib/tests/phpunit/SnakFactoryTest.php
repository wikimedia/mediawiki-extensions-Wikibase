<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\SnakFactory;

/**
 * @covers Wikibase\SnakFactory
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseSnak
 *
 * @license GPL 2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnakFactoryTest extends \MediaWikiTestCase {

	public function testNoValueSnakConstruction() {
		$factory = new SnakFactory();
		$snak = $factory->newSnak( new PropertyId( 'P1' ), 'novalue', null );

		$this->assertEquals(
			new PropertyNoValueSnak( 1 ),
			$snak
		);
	}

	public function testSomeValueSnakConstruction() {
		$factory = new SnakFactory();
		$snak = $factory->newSnak( new PropertyId( 'P1' ), 'somevalue', null );

		$this->assertEquals(
			new PropertySomeValueSnak( 1 ),
			$snak
		);
	}

	public function testPropertyValueSnakConstruction() {
		$factory = new SnakFactory();
		$snak = $factory->newSnak( new PropertyId( 'P1' ), 'value', new StringValue( 'foo' ) );

		$this->assertEquals(
			new PropertyValueSnak( 1, new StringValue( 'foo' ) ),
			$snak
		);
	}

	public function testGivenInvalidSnakType_exceptionIsThrown() {
		$factory = new SnakFactory();

		$this->setExpectedException( 'InvalidArgumentException' );
		$factory->newSnak( new PropertyId( 'P1' ), 'kittens', null );
	}

}
