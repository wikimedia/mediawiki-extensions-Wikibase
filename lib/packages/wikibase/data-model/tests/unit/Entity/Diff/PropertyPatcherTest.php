<?php

namespace Wikibase\Test\Entity\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\Diff\EntityDiff;
use Wikibase\DataModel\Entity\Diff\PropertyPatcher;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;

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

	public function testStatementsArePatched() {
		$s1337 = new Statement( new Claim( new PropertyNoValueSnak( 1337 ) ) );
		$s1337->setGuid( 's1337' );

		$s23 = new Statement( new Claim( new PropertyNoValueSnak( 23 ) ) );
		$s23->setGuid( 's23' );

		$s42 = new Statement( new Claim( new PropertyNoValueSnak( 42 ) ) );
		$s42->setGuid( 's42' );

		$patch = new EntityDiff( array(
				'claim' => new Diff( array(
					's42' => new DiffOpRemove( $s42 ),
					's23' => new DiffOpAdd( $s23 ),
				) )
			)
		);

		$property = Property::newFromType( 'kittens' );
		$property->getStatements()->addStatement( $s1337 );
		$property->getStatements()->addStatement( $s42 );

		$expectedProperty = Property::newFromType( 'kittens' );
		$expectedProperty->getStatements()->addStatement( $s1337 );
		$expectedProperty->getStatements()->addStatement( $s23 );

		$this->assertEquals(
			$expectedProperty,
			$this->getPatchedProperty( $property, $patch )
		);
	}

}

