<?php

namespace Wikibase\Lib\Tests\Store;

use MediaWiki\Revision\SlotRecord;
use Wikibase\Lib\Store\EntityNamespaceLookup;

/**
 * @covers \Wikibase\Lib\Store\EntityNamespaceLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityNamespaceLookupTest extends \PHPUnit\Framework\TestCase {

	private function newInstance() {
		return new EntityNamespaceLookup( [
			'item' => 120,
			'property' => 122,
			'mediainfo' => NS_FILE,
		], [
			'mediainfo' => 'mediainfo',
		] );
	}

	public function testGetEntityNamespaces() {
		$lookup = $this->newInstance();

		$expected = [
			'item' => 120,
			'property' => 122,
			'mediainfo' => NS_FILE,
		];
		$this->assertSame( $expected, $lookup->getEntityNamespaces() );
	}

	public function testGetEntitySlots() {
		$lookup = $this->newInstance();

		$expected = [
			'mediainfo' => 'mediainfo',
		];

		$this->assertSame( $expected, $lookup->getEntitySlots() );
	}

	public function testGetEntityNamespace() {
		$lookup = $this->newInstance();

		$this->assertSame( 120, $lookup->getEntityNamespace( 'item' ), 'found' );
		$this->assertSame( NS_FILE, $lookup->getEntityNamespace( 'mediainfo' ), 'found' );
		$this->assertNull( $lookup->getEntityNamespace( 'kittens' ), 'not found' );
	}

	public function testGetEntitySlotRole() {
		$lookup = $this->newInstance();

		$this->assertSame( SlotRecord::MAIN, $lookup->getEntitySlotRole( 'item' ), 'found' );
		$this->assertSame( 'mediainfo', $lookup->getEntitySlotRole( 'mediainfo' ), 'found' );
	}

	public function testIsEntityNamespace() {
		$lookup = $this->newInstance();

		$this->assertTrue( $lookup->isEntityNamespace( 120 ), 'found' );
		$this->assertFalse( $lookup->isEntityNamespace( 120.0 ), 'must be int' );
		$this->assertFalse( $lookup->isEntityNamespace( 5 ), 'not found' );
		$this->assertFalse( $lookup->isEntityNamespace( NS_FILE ), 'not in main slot' );
	}

	public function testIsNamespaceWithEntities() {
		$lookup = $this->newInstance();

		$this->assertTrue( $lookup->isNamespaceWithEntities( 120 ), 'found' );
		$this->assertTrue( $lookup->isNamespaceWithEntities( NS_FILE ), 'not in main slot' );
	}

	public function testGetEntityType() {
		$lookup = $this->newInstance();

		$this->assertSame( 'item', $lookup->getEntityType( 120 ), 'found' );
		$this->assertNull( $lookup->getEntityType( 120.0 ), 'must be int' );
		$this->assertNull( $lookup->getEntityType( 4 ), 'not found' );
	}

	public function testMerge() {
		$lookup = $this->newInstance();

		$expectedNamespaces = [
			'item' => 0,
			'property' => 122,
			'mediainfo' => NS_FILE,
			'someentity' => 130,
		];

		$expectedSlots = [
			'mediainfo' => 'mediainfo',
			'someentity' => 'fakeslot',
		];

		$result = $lookup->merge( new EntityNamespaceLookup( [
			'item' => 0,
			'someentity' => 130,
		], [
			'someentity' => 'fakeslot',
		] ) );

		$this->assertSame( $expectedNamespaces, $result->getEntityNamespaces() );
		$this->assertSame( $expectedSlots, $result->getEntitySlots() );
	}
}
