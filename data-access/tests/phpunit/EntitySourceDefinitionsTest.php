<?php

namespace Wikibase\DataAccess\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;

/**
 * @covers \Wikibase\DataAccess\EntitySourceDefinitions
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntitySourceDefinitionsTest extends TestCase {

	public function testGivenInvalidArguments_constructorThrowsException() {
		$this->expectException( InvalidArgumentException::class );
		new EntitySourceDefinitions( [ 'foobar' ], new EntityTypeDefinitions( [] ) );
	}

	public function testGivenEntityTypeProvidedByMultipleSources_constructorThrowsException() {
		$itemSourceOne = $this->newItemSource();
		$itemSourceTwo = new EntitySource( 'dupe test', 'foodb', [ 'item' => [ 'namespaceId' => 100, 'slot' => 'main' ] ], '', '', '', '' );

		$this->expectException( InvalidArgumentException::class );
		new EntitySourceDefinitions( [ $itemSourceOne, $itemSourceTwo ], new EntityTypeDefinitions( [] ) );
	}

	public function testTwoSourcesWithSameName_constructorThrowsException() {
		$sourceOne = new EntitySource( 'same name', 'aaa', [ 'entityOne' => [ 'namespaceId' => 100, 'slot' => 'main' ] ], '', '', '', '' );
		$sourceTwo = new EntitySource( 'same name', 'bbb', [ 'entityTwo' => [ 'namespaceId' => 101, 'slot' => 'main2' ] ], '', '', '', '' );

		$this->expectException( InvalidArgumentException::class );
		new EntitySourceDefinitions( [ $sourceOne, $sourceTwo ], new EntityTypeDefinitions( [] ) );
	}

	public function testGivenKnownType_getSourceForEntityTypeReturnsTheConfiguredSource() {
		$itemSource = $this->newItemSource();
		$propertySource = $this->newPropertySource();

		$sourceDefinitions = new EntitySourceDefinitions( [ $itemSource, $propertySource ], new EntityTypeDefinitions( [] ) );

		$this->assertEquals( $itemSource, $sourceDefinitions->getSourceForEntityType( 'item' ) );
	}

	public function testGivenSubEntityOfKnownType_getSourceForEntityTypeReturnsTheRelevantSource() {
		$itemSource = $this->newItemSource();
		$propertySource = $this->newPropertySource();

		$sourceDefinitions = new EntitySourceDefinitions(
			[ $itemSource, $propertySource ],
			new EntityTypeDefinitions( [ 'item' => [ EntityTypeDefinitions::SUB_ENTITY_TYPES => [ 'subitem' ] ] ] )
		);

		$this->assertEquals( $itemSource, $sourceDefinitions->getSourceForEntityType( 'subitem' ) );
	}

	public function testGivenUnknownType_getSourceForEntityTypeReturnsNull() {
		$itemSource = $this->newItemSource();

		$sourceDefinitions = new EntitySourceDefinitions( [ $itemSource ], new EntityTypeDefinitions( [] ) );

		$this->assertNull( $sourceDefinitions->getSourceForEntityType( 'property' ) );
	}

	public function testGetEntityTypeToSourceMapping() {
		$itemSource = $this->newItemSource();
		$propertySource = $this->newPropertySource();
		$otherSource = $this->newOtherSource();

		$sources = [ $itemSource, $propertySource, $otherSource ];
		$entityTypeDefinitions = [
			'other' => [
				EntityTypeDefinitions::SUB_ENTITY_TYPES => [ 'otherSub' ],
			],
			'otherSub' => [],
		];

		$sourceDefinitions = new EntitySourceDefinitions( $sources, new EntityTypeDefinitions( $entityTypeDefinitions ) );

		$this->assertEquals(
			[ 'item' => $itemSource, 'property' => $propertySource, 'other' => $otherSource, 'otherSub' => $otherSource ],
			$sourceDefinitions->getEntityTypeToSourceMapping()
		);
	}

	public function testGetConceptBaseUris() {
		$itemSource = $this->newItemSource();
		$propertySource = $this->newPropertySource();

		$sourceDefinitions = new EntitySourceDefinitions( [ $itemSource, $propertySource ], new EntityTypeDefinitions( [] ) );

		$this->assertEquals( [ 'items' => 'itemsource:', 'properties' => 'propertysource:' ], $sourceDefinitions->getConceptBaseUris() );
	}

	public function testGetRdfNodeNamespacePrefixes() {
		$itemSource = $this->newItemSource();
		$propertySource = $this->newPropertySource();

		$sourceDefinitions = new EntitySourceDefinitions( [ $itemSource, $propertySource ], new EntityTypeDefinitions( [] ) );

		$this->assertEquals( [ 'items' => 'it', 'properties' => 'pro' ], $sourceDefinitions->getRdfNodeNamespacePrefixes() );
	}

	public function testGetRdfPredicateNamespacePrefixes() {
		$itemSource = $this->newItemSource();
		$propertySource = $this->newPropertySource();

		$sourceDefinitions = new EntitySourceDefinitions( [ $itemSource, $propertySource ], new EntityTypeDefinitions( [] ) );

		$this->assertEquals( [ 'items' => '', 'properties' => 'pro' ], $sourceDefinitions->getRdfPredicateNamespacePrefixes() );
	}

	private function newItemSource() {
		return new EntitySource(
			'items',
			false,
			[ 'item' => [ 'namespaceId' => 100, 'slot' => 'main' ] ],
			'itemsource:',
			'it',
			'',
			''
		);
	}

	private function newPropertySource() {
		return new EntitySource(
			'properties',
			false,
			[ 'property' => [ 'namespaceId' => 200, 'slot' => 'main' ] ],
			'propertysource:',
			'pro',
			'pro',
			''
		);
	}

	private function newOtherSource() {
		return new EntitySource(
			'others',
			false,
			[ 'other' => [ 'namespaceId' => 666, 'slot' => 'other' ] ],
			'othersource:',
			'ot',
			'',
			''
		);
	}

}
