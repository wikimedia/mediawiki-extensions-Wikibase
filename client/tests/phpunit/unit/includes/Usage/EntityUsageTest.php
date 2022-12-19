<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\Usage;

use Wikibase\Client\Usage\EntityUsage;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers \Wikibase\Client\Usage\EntityUsage
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityUsageTest extends \PHPUnit\Framework\TestCase {

	public function testGetEntityId() {
		$id = new ItemId( 'Q7' );
		$aspect = EntityUsage::ALL_USAGE;

		$usage = new EntityUsage( $id, $aspect );

		$this->assertEquals( $id, $usage->getEntityId() );
	}

	public function testGetAspect() {
		$id = new ItemId( 'Q7' );
		$aspect = EntityUsage::ALL_USAGE;

		$usage = new EntityUsage( $id, $aspect );
		$this->assertSame( $aspect, $usage->getAspect() );
	}

	public function testGetIdentityString() {
		$id = new ItemId( 'Q7' );
		$aspect = EntityUsage::ALL_USAGE;

		$usage = new EntityUsage( $id, $aspect );
		$this->assertIsString( $usage->getIdentityString() );
	}

	public function testGetAspectKey() {
		$id = new ItemId( 'Q7' );
		$aspect = EntityUsage::LABEL_USAGE;
		$modifier = 'ru';

		$usage = new EntityUsage( $id, $aspect );
		$this->assertEquals( $aspect, $usage->getAspectKey() );

		$usage = new EntityUsage( $id, $aspect, $modifier );
		$this->assertSame( "$aspect.$modifier", $usage->getAspectKey() );
	}

	public function testAsArray() {
		$id = new ItemId( 'Q7' );
		$aspect = EntityUsage::LABEL_USAGE;
		$modifier = 'ru';

		$expected = [
			'entityId' => $id->getSerialization(),
			'aspect' => $aspect,
			'modifier' => null,
		];

		$usage = new EntityUsage( $id, $aspect );
		$this->assertSame( $expected, $usage->asArray() );

		$expected['modifier'] = $modifier;
		$usage = new EntityUsage( $id, $aspect, $modifier );
		$this->assertSame( $expected, $usage->asArray() );
	}

	public function aspectKeyProvider() {
		return [
			[ 'L', [ 'L', null ] ],
			[ 'L.x', [ 'L', 'x' ] ],
			[ 'L.x.y', [ 'L', 'x.y' ] ],
		];
	}

	/**
	 * @dataProvider aspectKeyProvider
	 */
	public function testStripModifier( string $aspectKey, array $expectedParts ) {
		$aspect = EntityUsage::stripModifier( $aspectKey );
		$this->assertSame( $expectedParts[0], $aspect );
	}

	/**
	 * @dataProvider aspectKeyProvider
	 */
	public function testSplitAspectKey( string $aspectKey, array $expectedParts ) {
		$parts = EntityUsage::splitAspectKey( $aspectKey );
		$this->assertSame( $expectedParts, $parts );
	}

	public function provideMakeAspectKey() {
		return [
			[ 'L', null, 'L' ],
			[ 'L', 'x', 'L.x' ],
		];
	}

	/**
	 * @dataProvider provideMakeAspectKey
	 */
	public function testMakeAspectKey( string $aspect, ?string $modifier, string $expectedKey ) {
		$key = EntityUsage::makeAspectKey( $aspect, $modifier );
		$this->assertSame( $expectedKey, $key );
	}

}
