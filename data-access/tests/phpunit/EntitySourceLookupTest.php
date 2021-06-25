<?php

declare( strict_types = 1 );

namespace Wikibase\DataAccess\Tests;

use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\FederatedProperties\FederatedPropertyId;

/**
 * @covers \Wikibase\DataAccess\EntitySourceLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntitySourceLookupTest extends TestCase {

	public function testGivenUriEntityId_returnsEntitySourceWithMatchingConceptUri() {
		$expectedSource = $this->newEntitySourceFromArray(
			[
				'conceptBaseUri' => 'http://wikidata.org/entity/',
				'type' => EntitySource::TYPE_API
			]
		);
		$entityId = new FederatedPropertyId( 'http://wikidata.org/entity/P123' );

		$lookup = new EntitySourceLookup( $this->newEntitySourceDefinitionsFromSources( [
			$this->newEntitySourceFromArray( [ 'name' => 'some other source' ] ),
			$expectedSource,
		] ) );

		$this->assertSame( $expectedSource, $lookup->getEntitySourceById( $entityId ) );
	}

	public function testGivenUnprefixedEntityId_returnsDbEntitySourceForEntityType() {
		$id = new PropertyId( 'P123' );
		$expectedSource = $this->newEntitySourceFromArray( [
			'name' => 'im a db source!',
			'entityNamespaceIdsAndSlots' => [ 'property' => [ 'namespaceId' => 121, 'slot' => 'main' ] ],
		] );

		$lookup = new EntitySourceLookup( $this->newEntitySourceDefinitionsFromSources( [
			$this->newEntitySourceFromArray( [ 'name' => 'some other source' ] ),
			$expectedSource,
		] ) );

		$this->assertSame( $expectedSource, $lookup->getEntitySourceById( $id ) );
	}

	public function testGivenEntityIdWithNoMatchingSource_throwsException() {
		$lookup = new EntitySourceLookup( $this->newEntitySourceDefinitionsFromSources( [
			$this->newEntitySourceFromArray( [
				'name' => 'im a property source',
				'entityNamespaceIdsAndSlots' => [ 'property' => [ 'namespaceId' => 121, 'slot' => 'main' ] ],
			] ),
		] ) );

		$this->expectException( LogicException::class );

		$lookup->getEntitySourceById( new ItemId( 'Q666' ) );
	}

	private function newEntitySourceFromArray( array $sourceConfig ): EntitySource {
		return new EntitySource(
			$sourceConfig['name'] ?? 'some name',
			false,
			$sourceConfig['entityNamespaceIdsAndSlots'] ?? [],
			$sourceConfig['conceptBaseUri'] ?? 'http://some-uri/',
			'',
			'',
			'',
			$sourceConfig['type'] ?? EntitySource::TYPE_DB
		);
	}

	public function testGivenUriEntityId_WithMatchingConceptUri_ButWithDBEntitySource_throws() {
		$expectedSource = $this->newEntitySourceFromArray(
			[
				'conceptBaseUri' => 'http://wikidata.org/entity/',
			]
		);
		$entityId = new FederatedPropertyId( 'http://wikidata.org/entity/P123' );

		$lookup = new EntitySourceLookup( $this->newEntitySourceDefinitionsFromSources( [
			$this->newEntitySourceFromArray( [ 'name' => 'some other source' ] ),
			$expectedSource,
		] ) );

		$this->expectException( LogicException::class );
		$lookup->getEntitySourceById( $entityId );
	}

	private function newEntitySourceDefinitionsFromSources( array $sources ): EntitySourceDefinitions {
		return new EntitySourceDefinitions( $sources, $this->createStub( EntityTypeDefinitions::class ) );
	}

}
