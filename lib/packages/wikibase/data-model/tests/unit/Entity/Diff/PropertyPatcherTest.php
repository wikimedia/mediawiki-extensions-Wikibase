<?php

namespace Wikibase\Test;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Entity\Diff\EntityDiff;
use Wikibase\DataModel\Entity\Diff\ItemDiff;
use Wikibase\DataModel\Entity\Diff\ItemPatcher;
use Wikibase\DataModel\Entity\Diff\PropertyPatcher;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Entity\Diff\ItemDiffer;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;

/**
 * @covers Wikibase\DataModel\Entity\Diff\PropertyPatcher
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyPatcherTest extends \PHPUnit_Framework_TestCase {

	public function testGivenEmptyDiff_itemIsReturnedAsIs() {
		$patcher = new PropertyPatcher();

		$property = Property::newFromType( 'kittens' );
		$property->getFingerprint()->setLabel( 'en', 'foo' );
		$property->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$patchedProperty = $patcher->patchEntity( $property, new EntityDiff() );

		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\Property', $patchedProperty );
		$this->assertTrue( $property->equals( $patchedProperty ) );
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

