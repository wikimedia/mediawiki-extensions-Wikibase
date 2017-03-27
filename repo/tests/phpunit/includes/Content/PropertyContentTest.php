<?php

namespace Wikibase\Repo\Tests\Content;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
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

	/**
	 * @return PropertyId
	 */
	protected function getDummyId() {
		return new PropertyId( 'P100' );
	}

	/**
	 * @param EntityId $entityId
	 * @return Property
	 */
	protected function newEntity( EntityId $entityId ) {
		return new Property( $entityId, null, 'string' );
	}

	/**
	 * @param PropertyId|null $propertyId
	 *
	 * @throws InvalidArgumentException
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

		return array(
			'property id' => array( $this->newEmpty( $p11 ), $p11 ),
		);
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
