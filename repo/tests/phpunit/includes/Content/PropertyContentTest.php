<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\PropertyContent;

/**
 * @covers Wikibase\PropertyContent
 * @covers Wikibase\EntityContent
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseProperty
 * @group WikibaseRepo
 * @group WikibaseContent
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyContentTest extends EntityContentTest {

	/**
	 * @return PropertyId
	 */
	protected function getDummyId() {
		return new PropertyId( 'P100' );
	}

	/**
	 * @param EntityId|null $propertyId
	 *
	 * @return PropertyContent
	 */
	protected function newEmpty( EntityId $propertyId = null ) {
		$empty = PropertyContent::newEmpty();

		if ( $propertyId !== null ) {
			$empty->getProperty()->setId( $propertyId );
		}

		return $empty;
	}

	public function provideGetEntityId() {
		$p11 = new PropertyId( 'P11' );

		return [
			'property id' => [ $this->newEmpty( $p11 ), $p11 ],
		];
	}

	public function testIsEmpty_emptyProperty() {
		$content = PropertyContent::newFromProperty( Property::newFromType( 'foo' ) );
		$this->assertTrue( $content->isEmpty() );
	}

	public function testIsEmpty_nonEmptyProperty() {
		$Property = Property::newFromType( 'foo' );
		$Property->setLabel( 'en', '~=[,,_,,]:3' );
		$content = PropertyContent::newFromProperty( $Property );
		$this->assertFalse( $content->isEmpty() );
	}

	public function testIsStub_stubProperty() {
		$Property = Property::newFromType( 'foo' );
		$Property->setLabel( 'en', '~=[,,_,,]:3' );
		$content = PropertyContent::newFromProperty( $Property );
		$this->assertTrue( $content->isStub() );
	}

	public function testIsStub_emptyProperty() {
		$content = PropertyContent::newFromProperty( Property::newFromType( 'foo' ) );
		$this->assertFalse( $content->isStub() );
	}

	public function testIsStub_nonStubProperty() {
		$Property = Property::newFromType( 'foo' );
		$Property->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );
		$content = PropertyContent::newFromProperty( $Property );
		$this->assertFalse( $content->isStub() );
	}

}
