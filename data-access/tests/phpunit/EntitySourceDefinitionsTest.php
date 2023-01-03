<?php

namespace Wikibase\DataAccess\Tests;

use InvalidArgumentException;
use MediaWiki\Revision\SlotRecord;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\ApiEntitySource;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\SubEntityTypesMapper;

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
		new EntitySourceDefinitions( [ 'foobar' ], new SubEntityTypesMapper( [] ) );
	}

	public function testGivenEntityTypeProvidedByMultipleSources_constructorThrowsException() {
		$itemSourceOne = $this->newItemSource();
		$itemSourceTwo = new DatabaseEntitySource(
			'dupe test',
			'foodb',
			[ 'item' => [ 'namespaceId' => 100, 'slot' => SlotRecord::MAIN ] ],
			'',
			'',
			'',
			''
		);

		$this->expectException( InvalidArgumentException::class );
		new EntitySourceDefinitions( [ $itemSourceOne, $itemSourceTwo ], new SubEntityTypesMapper( [] ) );
	}

	public function testTwoSourcesWithSameName_constructorThrowsException() {
		$sourceOne = new DatabaseEntitySource(
			'same name',
			'aaa', [ 'entityOne' => [ 'namespaceId' => 100, 'slot' => SlotRecord::MAIN ] ],
			'',
			'',
			'',
			''
		);
		$sourceTwo = new DatabaseEntitySource(
			'same name',
			'bbb', [ 'entityTwo' => [ 'namespaceId' => 101, 'slot' => 'main2' ] ],
			'',
			'',
			'',
			''
		);

		$this->expectException( InvalidArgumentException::class );
		new EntitySourceDefinitions( [ $sourceOne, $sourceTwo ], new SubEntityTypesMapper( [] ) );
	}

	public function testGivenKnownType_getDatabaseSourceForEntityTypeReturnsTheConfiguredSource() {
		$itemSource = $this->newItemSource();
		$propertySource = $this->newPropertySource();

		$sourceDefinitions = new EntitySourceDefinitions( [ $itemSource, $propertySource ], new SubEntityTypesMapper( [] ) );

		$this->assertEquals( $itemSource, $sourceDefinitions->getDatabaseSourceForEntityType( 'item' ) );
	}

	public function testGivenSubEntityOfKnownType_getDatabaseSourceForEntityTypeReturnsTheRelevantSource() {
		$itemSource = $this->newItemSource();
		$propertySource = $this->newPropertySource();

		$sourceDefinitions = new EntitySourceDefinitions(
			[ $itemSource, $propertySource ],
			new SubEntityTypesMapper( [ 'item' => [ 'subitem' ] ] )
		);

		$this->assertEquals( $itemSource, $sourceDefinitions->getDatabaseSourceForEntityType( 'subitem' ) );
	}

	public function testGivenUnknownType_getDatabaseSourceForEntityTypeReturnsNull() {
		$itemSource = $this->newItemSource();

		$sourceDefinitions = new EntitySourceDefinitions( [ $itemSource ], new SubEntityTypesMapper( [] ) );

		$this->assertNull( $sourceDefinitions->getDatabaseSourceForEntityType( 'property' ) );
	}

	public function testGetEntityTypeToSourceMapping() {
		$itemSource = $this->newItemSource();
		$propertySource = $this->newPropertySource();
		$otherSource = $this->newOtherSource();

		$sources = [ $itemSource, $propertySource, $otherSource ];
		$subEntityTypes = [
			'other' => [ 'otherSub' ],
			'otherSub' => [],
		];

		$sourceDefinitions = new EntitySourceDefinitions( $sources, new SubEntityTypesMapper( $subEntityTypes ) );

		$this->assertEquals(
			[ 'item' => $itemSource, 'property' => $propertySource, 'other' => $otherSource, 'otherSub' => $otherSource ],
			$sourceDefinitions->getEntityTypeToDatabaseSourceMapping()
		);
	}

	public function testGetConceptBaseUris() {
		$itemSource = $this->newItemSource();
		$propertySource = $this->newPropertySource();

		$sourceDefinitions = new EntitySourceDefinitions( [ $itemSource, $propertySource ], new SubEntityTypesMapper( [] ) );

		$this->assertEquals( [ 'items' => 'itemsource:', 'properties' => 'propertysource:' ], $sourceDefinitions->getConceptBaseUris() );
	}

	public function testGetRdfNodeNamespacePrefixes() {
		$itemSource = $this->newItemSource();
		$propertySource = $this->newPropertySource();

		$sourceDefinitions = new EntitySourceDefinitions( [ $itemSource, $propertySource ], new SubEntityTypesMapper( [] ) );

		$this->assertEquals( [ 'items' => 'it', 'properties' => 'pro' ], $sourceDefinitions->getRdfNodeNamespacePrefixes() );
	}

	public function testGetRdfPredicateNamespacePrefixes() {
		$itemSource = $this->newItemSource();
		$propertySource = $this->newPropertySource();

		$sourceDefinitions = new EntitySourceDefinitions( [ $itemSource, $propertySource ], new SubEntityTypesMapper( [] ) );

		$this->assertEquals( [ 'items' => '', 'properties' => 'pro' ], $sourceDefinitions->getRdfPredicateNamespacePrefixes() );
	}

	public function testGivenFedPropsSource_getApiSourceForEntityTypeReturnsSource(): void {
		$fedPropSource = new ApiEntitySource(
			'feddy-props',
			[ 'property' ],
			'someUrl',
			'',
			'',
			''
		);

		$sourceDefinitions = new EntitySourceDefinitions( [
			$this->newItemSource(),
			$fedPropSource,
		], new SubEntityTypesMapper( [] ) );

		$this->assertSame(
			$fedPropSource,
			$sourceDefinitions->getApiSourceForEntityType( Property::ENTITY_TYPE )
		);
	}

	public function testGivenNoFedPropsSource_getApiSourceForEntityTypeReturnsNull(): void {
		$sourceDefinitions = new EntitySourceDefinitions( [
			$this->newItemSource(),
			$this->newPropertySource(),
		], new SubEntityTypesMapper( [] ) );

		$this->assertNull(
			$sourceDefinitions->getApiSourceForEntityType( Property::ENTITY_TYPE )
		);
	}

	private function newItemSource() {
		return new DatabaseEntitySource(
			'items',
			false,
			[ 'item' => [ 'namespaceId' => 100, 'slot' => SlotRecord::MAIN ] ],
			'itemsource:',
			'it',
			'',
			''
		);
	}

	private function newPropertySource() {
		return new DatabaseEntitySource(
			'properties',
			false,
			[ 'property' => [ 'namespaceId' => 200, 'slot' => SlotRecord::MAIN ] ],
			'propertysource:',
			'pro',
			'pro',
			''
		);
	}

	private function newOtherSource() {
		return new DatabaseEntitySource(
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
