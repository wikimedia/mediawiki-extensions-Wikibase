<?php

namespace Wikibase\Repo\Tests\Content;

use InvalidArgumentException;
use Wikibase\Content\EntityHolder;
use Wikibase\Content\EntityInstanceHolder;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\PropertyContent;

/**
 * @covers Wikibase\PropertyContent
 * @covers Wikibase\EntityContent
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseProperty
 * @group WikibaseContent
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyContentTest extends EntityContentTest {

	public function provideValidConstructorArguments() {
		return [
			'empty' => [ null ],
			'empty property' => [ new EntityInstanceHolder( Property::newFromType( 'string' ) ) ],
		];
	}

	/**
	 * @dataProvider provideValidConstructorArguments
	 */
	public function testConstructor( EntityHolder $holder = null ) {
		$content = new PropertyContent( $holder );
		$this->assertInstanceOf( PropertyContent::class, $content );
	}

	public function testConstructorExceptions() {
		$holder = new EntityInstanceHolder( new Item() );
		$this->setExpectedException( InvalidArgumentException::class );
		new PropertyContent( $holder );
	}

	/**
	 * @return PropertyId
	 */
	protected function getDummyId() {
		return new PropertyId( 'P100' );
	}

	/**
	 * @return string
	 */
	protected function getEntityType() {
		return Property::ENTITY_TYPE;
	}

	/**
	 * @return PropertyContent
	 */
	protected function newEmpty() {
		return new PropertyContent();
	}

	/**
	 * @param PropertyId|null $propertyId
	 *
	 * @throws InvalidArgumentException
	 * @return PropertyContent
	 */
	protected function newBlank( EntityId $propertyId = null ) {
		$property = Property::newFromType( 'string' );
		$property->setId( $propertyId );
		return new PropertyContent( new EntityInstanceHolder( $property ) );
	}

	public function provideGetEntityId() {
		$p11 = new PropertyId( 'P11' );

		return [
			'property id' => [ $this->newBlank( $p11 ), $p11 ],
		];
	}

	public function provideContentObjectsWithoutId() {
		return [
			'no holder' => [ new PropertyContent() ],
			'no ID' => [ new PropertyContent( new EntityInstanceHolder( Property::newFromType( 'string' ) ) ) ],
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

}
