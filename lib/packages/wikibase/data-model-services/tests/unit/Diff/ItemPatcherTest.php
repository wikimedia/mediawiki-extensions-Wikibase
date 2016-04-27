<?php

namespace Wikibase\DataModel\Services\Tests\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Services\Diff\ItemDiff;
use Wikibase\DataModel\Services\Diff\ItemPatcher;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers Wikibase\DataModel\Services\Diff\ItemPatcher
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemPatcherTest extends PHPUnit_Framework_TestCase {

	public function testGivenEmptyDiff_itemIsReturnedAsIs() {
		$item = new Item();
		$item->getFingerprint()->setLabel( 'en', 'foo' );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'bar' );

		$patchedItem = $this->getPatchedItem( $item, new ItemDiff() );

		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\Item', $patchedItem );
		$this->assertTrue( $item->equals( $patchedItem ) );
	}

	private function getPatchedItem( Item $item, ItemDiff $patch ) {
		$patchedItem = $item->copy();

		$patcher = new ItemPatcher();
		$patcher->patchEntity( $patchedItem, $patch );

		return $patchedItem;
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
		$item = new Item();
		$item->getFingerprint()->setLabel( 'en', 'foo' );
		$item->getFingerprint()->setLabel( 'de', 'bar' );

		$patch = new ItemDiff( array(
			'label' => new Diff( array(
				'en' => new DiffOpChange( 'foo', 'spam' ),
				'nl' => new DiffOpAdd( 'baz' ),
			) )
		) );

		$patchedItem = $this->getPatchedItem( $item, $patch );

		$this->assertSame(
			array(
				'en' => 'spam',
				'de' => 'bar',
				'nl' => 'baz',
			),
			$patchedItem->getFingerprint()->getLabels()->toTextArray()
		);
	}

	public function testDescriptionsArePatched() {
		$property = new Item();
		$property->setDescription( 'en', 'foo' );
		$property->setDescription( 'de', 'bar' );

		$patch = new ItemDiff( array(
			'description' => new Diff( array(
				'en' => new DiffOpChange( 'foo', 'spam' ),
				'nl' => new DiffOpAdd( 'baz' ),
			) ),
		) );

		$patcher = new ItemPatcher();
		$patcher->patchEntity( $property, $patch );

		$this->assertSame( array(
			'en' => 'spam',
			'de' => 'bar',
			'nl' => 'baz',
		), $property->getFingerprint()->getDescriptions()->toTextArray() );
	}

	public function testStatementsArePatched() {
		$removedStatement = new Statement( new PropertyNoValueSnak( 1 ), null, null, 's1' );
		$addedStatement = new Statement( new PropertyNoValueSnak( 2 ), null, null, 's2' );

		$item = new Item();
		$item->getStatements()->addStatement( $removedStatement );

		$patch = new ItemDiff( array(
			'claim' => new Diff( array(
				's1' => new DiffOpRemove( $removedStatement ),
				's2' => new DiffOpAdd( $addedStatement ),
			) ),
		) );

		$expected = new Item();
		$expected->getStatements()->addStatement( $addedStatement );

		$patcher = new ItemPatcher();
		$patcher->patchEntity( $item, $patch );
		$this->assertTrue( $expected->equals( $item ) );
	}

	public function testSiteLinksArePatched() {
		$removedSiteLink = new SiteLink( 'rewiki', 'Removed' );
		$addedSiteLink = new SiteLink( 'adwiki', 'Added' );

		$item = new Item();
		$item->getSiteLinkList()->addSiteLink( $removedSiteLink );

		$patch = new ItemDiff( array(
			'links' => new Diff( array(
				'rewiki' => new Diff( array(
					'name' => new DiffOpRemove( 'Removed' ),
				) ),
				'adwiki' => new Diff( array(
					'name' => new DiffOpAdd( 'Added' ),
					'badges' => new Diff(),
				) ),
			) ),
		) );

		$expected = new Item();
		$expected->getSiteLinkList()->addSiteLink( $addedSiteLink );

		$patcher = new ItemPatcher();
		$patcher->patchEntity( $item, $patch );
		$this->assertTrue( $expected->equals( $item ) );
	}

}
