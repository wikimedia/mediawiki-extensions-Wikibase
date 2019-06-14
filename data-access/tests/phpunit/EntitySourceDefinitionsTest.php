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
		$itemSourceTwo = new EntitySource( 'dupe test', 'foodb', [ 'item' => [ 'namespaceId' => 100, 'slot' => 'main' ] ], '', '', '', '' );

		new EntitySourceDefinitions( [ $itemSourceOne, $itemSourceTwo ] );
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testTwoSourcesWithSameName_constructorThrowsException() {
		$sourceOne = new EntitySource( 'same name', 'aaa', [ 'entityOne' => [ 'namespaceId' => 100, 'slot' => 'main' ] ], '', '', '', '' );
		$sourceTwo = new EntitySource( 'same name', 'bbb', [ 'entityTwo' => [ 'namespaceId' => 101, 'slot' => 'main2' ] ], '', '' ,'', '' );

		new EntitySourceDefinitions( [ $sourceOne, $sourceTwo ] );
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

	public function testGetConceptBaseUris() {
		$itemSource = $this->newItemSource();
		$propertySource = $this->newPropertySource();

		$sourceDefinitions = new EntitySourceDefinitions( [ $itemSource, $propertySource ] );

		$this->assertEquals( [ 'items' => 'itemsource:', 'properties' => 'propertysource:' ], $sourceDefinitions->getConceptBaseUris() );
	}

	public function testGetRdfNodeNamespacePrefixes() {
		$itemSource = $this->newItemSource();
		$propertySource = $this->newPropertySource();

		$sourceDefinitions = new EntitySourceDefinitions( [ $itemSource, $propertySource ] );

		$this->assertEquals( [ 'items' => 'it', 'properties' => 'pro' ], $sourceDefinitions->getRdfNodeNamespacePrefixes() );
	}

	public function testGetRdfPredicateNamespacePrefixes() {
		$itemSource = $this->newItemSource();
		$propertySource = $this->newPropertySource();

		$sourceDefinitions = new EntitySourceDefinitions( [ $itemSource, $propertySource ] );

		$this->assertEquals( [ 'items' => '', 'properties' => 'pro' ], $sourceDefinitions->getRdfPredicateNamespacePrefixes() );
	}

	private function newItemSource() {
		return new EntitySource( 'items', false, [ 'item' => [ 'namespaceId' => 100, 'slot' => 'main' ] ], 'itemsource:', 'it', '', '' );
	}

	private function newPropertySource() {
		return new EntitySource( 'properties', false, [ 'property' => [ 'namespaceId' => 200, 'slot' => 'main' ] ], 'propertysource:', 'pro', 'pro', '' );
	}

}
