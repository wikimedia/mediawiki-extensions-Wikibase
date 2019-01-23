<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;

/**
 * @covers \Wikibase\DataAccess\EntitySourceDefinitions
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntitySourceDefinitionsTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testGivenInvalidArguments_constructorThrowsException() {
		new EntitySourceDefinitions( [ 'foobar' ] );
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testGivenEntityTypeProvidedByMultipleSources_constructorThrowsException() {
		$itemSourceOne = $this->newItemSource();
		$itemSourceTwo = new EntitySource( 'dupe test', 'foodb', [ 'item' => [ 'namespaceId' => 100, 'slot' => 'main' ] ] );

		new EntitySourceDefinitions( [ $itemSourceOne, $itemSourceTwo ] );
	}

	public function testGivenKnownType_getSourceForEntityTypeReturnsTheConfiguredSource() {
		$itemSource = $this->newItemSource();
		$propertySource = $this->newPropertySource();

		$sourceDefinitions = new EntitySourceDefinitions( [ $itemSource, $propertySource ] );

		$this->assertEquals( $itemSource, $sourceDefinitions->getSourceForEntityType( 'item' ) );
	}

	public function testGivenUnknownType_getSourceForEntityTypeReturnsNull() {
		$itemSource = $this->newItemSource();

		$sourceDefinitions = new EntitySourceDefinitions( [ $itemSource ] );

		$this->assertNull( $sourceDefinitions->getSourceForEntityType( 'property' ) );
	}

	public function testGetEntityTypeToSourceMapping() {
		$itemSource = $this->newItemSource();
		$propertySource = $this->newPropertySource();

		$sourceDefinitions = new EntitySourceDefinitions( [ $itemSource, $propertySource ] );

		$this->assertEquals( [ 'item' => $itemSource, 'property' => $propertySource ], $sourceDefinitions->getEntityTypeToSourceMapping() );
	}

	private function newItemSource() {
		return new EntitySource( 'items', false, [ 'item' => [ 'namespaceId' => 100, 'slot' => 'main' ] ] );
	}

	private function newPropertySource() {
		return new EntitySource( 'properties', false, [ 'property' => [ 'namespaceId' => 200, 'slot' => 'main' ] ] );
	}

}
