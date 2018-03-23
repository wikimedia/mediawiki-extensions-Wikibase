<?php

namespace Wikibase\Lib\Tests\Store;

use Wikibase\Lib\Store\EntityNamespaceLookup;

/**
 * @covers Wikibase\Lib\Store\EntityNamespaceLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author Thiemo Kreuz
 */
class EntityNamespaceLookupTest extends \PHPUnit\Framework\TestCase {

	private function newInstance() {
		return new EntityNamespaceLookup( [
			'item' => 120,
			'property' => 122,
		] );
	}

	public function testGetEntityNamespaces() {
		$lookup = $this->newInstance();

		$expected = [
			'item' => 120,
			'property' => 122,
		];
		$this->assertSame( $expected, $lookup->getEntityNamespaces() );
	}

	public function testGetEntityNamespace() {
		$lookup = $this->newInstance();

		$this->assertSame( 120, $lookup->getEntityNamespace( 'item' ), 'found' );
		$this->assertNull( $lookup->getEntityNamespace( 'kittens' ), 'not found' );
	}

	public function testIsEntityNamespace() {
		$lookup = $this->newInstance();

		$this->assertTrue( $lookup->isEntityNamespace( 120 ), 'found' );
		$this->assertFalse( $lookup->isEntityNamespace( 120.0 ), 'must be int' );
		$this->assertFalse( $lookup->isEntityNamespace( 4 ), 'not found' );
	}

	public function testGetEntityType() {
		$lookup = $this->newInstance();

		$this->assertSame( 'item', $lookup->getEntityType( 120 ), 'found' );
		$this->assertNull( $lookup->getEntityType( 120.0 ), 'must be int' );
		$this->assertNull( $lookup->getEntityType( 4 ), 'not found' );
	}

}
