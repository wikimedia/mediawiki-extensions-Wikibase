<?php

namespace Wikibase\Test;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Entity\Diff\ItemDiff;
use Wikibase\DataModel\Entity\Diff\ItemPatcher;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Entity\Diff\ItemDiffer;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;

/**
 * @covers Wikibase\DataModel\Entity\Diff\ItemPatcher
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemPatcherTest extends \PHPUnit_Framework_TestCase {

	public function testGivenEmptyDiff_itemIsReturnedAsIs() {
		$patcher = new ItemPatcher();

		$item = Item::newEmpty();
		$item->getFingerprint()->setLabel( 'en', 'foo' );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'bar' );

		$patchedItem = $patcher->patchEntity( $item, new ItemDiff() );

		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\Item', $patchedItem );
		$this->assertTrue( $item->equals( $patchedItem ) );
	}

	public function testCanPatchEntityType() {
		$patcher = new ItemPatcher();
		$this->assertTrue( $patcher->canPatchEntityType( 'item' ) );
		$this->assertFalse( $patcher->canPatchEntityType( 'property' ) );
		$this->assertFalse( $patcher->canPatchEntityType( '' ) );
		$this->assertFalse( $patcher->canPatchEntityType( null ) );
	}

	public function testGivenNonItem_exceptionIsThrown() {
		$patcher = new ItemPatcher();

		$this->setExpectedException( 'InvalidArgumentException' );
		$patcher->patchEntity( Property::newFromType( 'kittens' ), new ItemDiff() );
	}

	public function testPatchesLabels() {
		$item = Item::newEmpty();
		$item->getFingerprint()->setLabel( 'en', 'foo' );
		$item->getFingerprint()->setLabel( 'de', 'bar' );

		$patch = new ItemDiff( array(
			'label' => new Diff( array(
				'en' => new DiffOpChange( 'foo', 'spam' ),
				'nl' => new DiffOpAdd( 'baz' ),
			) )
		) );

		$patcher = new ItemPatcher();
		$patchedItem = $patcher->patchEntity( $item, $patch );

		$this->assertSame(
			array(
				'en' => 'spam',
				'de' => 'bar',
				'nl' => 'baz',
			),
			$patchedItem->getFingerprint()->getLabels()->toTextArray()
		);
	}

}

