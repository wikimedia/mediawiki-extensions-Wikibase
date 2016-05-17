<?php

namespace Wikibase\Lib\Tests\Store;

use Wikibase\Lib\Store\EntityNamespaceLookup;

/**
 * @covers Wikibase\Lib\Store\EntityNamespaceLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityNamespaceLookupTest extends \PHPUnit_Framework_TestCase {

	public function testGetEntityNamespaces() {
		$entityNamespaces = $this->getNamespaces();
		$entityNamespaceLookup = new EntityNamespaceLookup( $this->getNamespaces() );

		$this->assertEquals( $entityNamespaces, $entityNamespaceLookup->getEntityNamespaces() );
	}

	/**
	 * @dataProvider getEntityNamespaceProvider
	 */
	public function testGetEntityNamespace( array $entityNamespaces, $namespace, $expected ) {
		$entityNamespaces = $this->getNamespaces();

		$entityNamespaceLookup = new EntityNamespaceLookup( $entityNamespaces );
		$this->assertEquals( $expected, $entityNamespaceLookup->getEntityNamespace( $namespace ) );
	}

	public function getEntityNamespaceProvider() {
		$entityNamespaces = $this->getNamespaces();

		return array(
			array( $entityNamespaces, 'wikibase-item', 120 ),
			array( $entityNamespaces, 'kittens', false )
		);
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
		return array(
			'wikibase-item' => 120,
			'wikibase-property' => 122
		);
	}

	public function testIsCoreNamespace() {
		$this->assertTrue(
			EntityNamespaceLookup::isCoreNamespace( 4 ),
			'4 is a core namespace'
		);

		$this->assertFalse(
			EntityNamespaceLookup::isCoreNamespace( 9000 ),
			'9000 is not a core namespace'
		);
	}

}
