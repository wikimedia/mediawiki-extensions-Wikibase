<?php

namespace Wikibase\DataModel\Services\Tests\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Diff\PropertyPatcher;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers \Wikibase\DataModel\Services\Diff\PropertyPatcher
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyPatcherTest extends TestCase {

	public function testGivenEmptyDiff_itemIsReturnedAsIs() {
		$property = Property::newFromType( 'kittens' );
		$property->getFingerprint()->setLabel( 'en', 'foo' );
		$property->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$patchedProperty = $this->getPatchedProperty( $property, new EntityDiff() );

		$this->assertInstanceOf( Property::class, $patchedProperty );
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

		$this->expectException( InvalidArgumentException::class );
		$patcher->patchEntity( new Item(), new EntityDiff() );
	}

	public function testLabelsArePatched() {
		$property = Property::newFromType( 'string' );
		$property->setLabel( 'en', 'foo' );
		$property->setLabel( 'de', 'bar' );

		$patch = new EntityDiff( [
			'label' => new Diff( [
				'en' => new DiffOpChange( 'foo', 'spam' ),
				'nl' => new DiffOpAdd( 'baz' ),
			] ),
		] );

		$patcher = new PropertyPatcher();
		$patcher->patchEntity( $property, $patch );

		$this->assertSame( [
			'en' => 'spam',
			'de' => 'bar',
			'nl' => 'baz',
		], $property->getFingerprint()->getLabels()->toTextArray() );
	}

	public function testDescriptionsArePatched() {
		$property = Property::newFromType( 'string' );
		$property->setDescription( 'en', 'foo' );
		$property->setDescription( 'de', 'bar' );

		$patch = new EntityDiff( [
			'description' => new Diff( [
				'en' => new DiffOpChange( 'foo', 'spam' ),
				'nl' => new DiffOpAdd( 'baz' ),
			] ),
		] );

		$patcher = new PropertyPatcher();
		$patcher->patchEntity( $property, $patch );

		$this->assertSame( [
			'en' => 'spam',
			'de' => 'bar',
			'nl' => 'baz',
		], $property->getFingerprint()->getDescriptions()->toTextArray() );
	}

	public function testStatementsArePatched() {
		$s1337 = new Statement( new PropertyNoValueSnak( 1337 ) );
		$s1337->setGuid( 's1337' );

		$s23 = new Statement( new PropertyNoValueSnak( 23 ) );
		$s23->setGuid( 's23' );

		$s42 = new Statement( new PropertyNoValueSnak( 42 ) );
		$s42->setGuid( 's42' );

		$patch = new EntityDiff( [
				'claim' => new Diff( [
					's42' => new DiffOpRemove( $s42 ),
					's23' => new DiffOpAdd( $s23 ),
				] ),
		] );

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
