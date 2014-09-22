<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Diff\EntityDiff;
use Wikibase\DataModel\Entity\Diff\PropertyPatcher;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;

/**
 * @covers Wikibase\DataModel\Entity\Diff\PropertyPatcher
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyPatcherTest extends \PHPUnit_Framework_TestCase {

	public function testGivenEmptyDiff_itemIsReturnedAsIs() {
		$property = Property::newFromType( 'kittens' );
		$property->getFingerprint()->setLabel( 'en', 'foo' );
		$property->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$patchedProperty = $this->getPatchedProperty( $property, new EntityDiff() );

		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\Property', $patchedProperty );
		$this->assertTrue( $property->equals( $patchedProperty ) );
	}

	private function getPatchedProperty( Property $property, EntityDiff $patch ) {
		$patchedProperty = $property->copy();

		$patcher = new PropertyPatcher();
		$patcher->patchEntity( $patchedProperty, $patch );

		return $patchedProperty;
	}

	public function testCanPatchEntityType() {
		$patcher = new PropertyPatcher();
		$this->assertTrue( $patcher->canPatchEntityType( 'property' ) );
		$this->assertFalse( $patcher->canPatchEntityType( 'item' ) );
		$this->assertFalse( $patcher->canPatchEntityType( '' ) );
		$this->assertFalse( $patcher->canPatchEntityType( null ) );
	}

	public function testGivenNonItem_exceptionIsThrown() {
		$patcher = new PropertyPatcher();

		$this->setExpectedException( 'InvalidArgumentException' );
		$patcher->patchEntity( Item::newEmpty(), new EntityDiff() );
	}

}

