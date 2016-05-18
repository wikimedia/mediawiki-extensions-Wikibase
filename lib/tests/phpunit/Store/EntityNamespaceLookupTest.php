<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit_Framework_TestCase;
use Wikibase\Lib\Store\EntityNamespaceLookup;

/**
 * @covers Wikibase\Lib\Store\EntityNamespaceLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class EntityNamespaceLookupTest extends PHPUnit_Framework_TestCase {

	public function testGetEntityNamespaces() {
		$entityNamespaces = $this->getNamespaces();
		$entityNamespaceLookup = new EntityNamespaceLookup( $entityNamespaces );

		$this->assertEquals( $entityNamespaces, $entityNamespaceLookup->getEntityNamespaces() );
	}

	/**
	 * @dataProvider getEntityNamespaceProvider
	 */
	public function testGetEntityNamespace( $namespace, $expected ) {
		$entityNamespaces = $this->getNamespaces();

		$entityNamespaceLookup = new EntityNamespaceLookup( $entityNamespaces );
		$this->assertEquals( $expected, $entityNamespaceLookup->getEntityNamespace( $namespace ) );
	}

	public function getEntityNamespaceProvider() {
		return [
			[ 'item', 120 ],
			[ 'kittens', false ]
		];
	}

	public function testIsEntityNamespace() {
		$entityNamespaces = $this->getNamespaces();
		$entityNamespaceLookup = new EntityNamespaceLookup( $entityNamespaces );

		$this->assertTrue(
			$entityNamespaceLookup->isEntityNamespace( 120 ),
			'120 is an entity namespace'
		);

		$this->assertFalse(
			$entityNamespaceLookup->isEntityNamespace( 4 ),
			'4 is not an entity namespace'
		);
	}

	private function getNamespaces() {
		return [
			'item' => 120,
			'property' => 122
		];
	}

}
