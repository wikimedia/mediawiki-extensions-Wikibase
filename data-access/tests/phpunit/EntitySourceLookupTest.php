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
		$expectedSource = NewEntitySource::create()
			->withConceptBaseUri( 'http://wikidata.org/entity/' )
			->withType( EntitySource::TYPE_API )
			->build();
		$entityId = new FederatedPropertyId( 'http://wikidata.org/entity/P123' );

		$lookup = new EntitySourceLookup( $this->newEntitySourceDefinitionsFromSources( [
			NewEntitySource::havingName( 'some other source' )->build(),
			$expectedSource,
		] ) );

		$this->assertSame( $expectedSource, $lookup->getEntitySourceById( $entityId ) );
	}

	public function testGivenUnprefixedEntityId_returnsDbEntitySourceForEntityType() {
		$id = new PropertyId( 'P123' );
		$expectedSource = NewEntitySource::havingName( 'im a db source!' )
			->withEntityNamespaceIdsAndSlots( [ 'property' => [ 'namespaceId' => 121, 'slot' => 'main' ] ] )
			->build();

		$lookup = new EntitySourceLookup( $this->newEntitySourceDefinitionsFromSources( [
			NewEntitySource::havingName( 'some other source' )->build(),
			$expectedSource,
		] ) );

		$this->assertSame( $expectedSource, $lookup->getEntitySourceById( $id ) );
	}

	public function testGivenEntityIdWithNoMatchingSource_throwsException() {
		$lookup = new EntitySourceLookup( $this->newEntitySourceDefinitionsFromSources( [
			NewEntitySource::havingName( 'im a property source' )
				->withEntityNamespaceIdsAndSlots( [ 'property' => [ 'namespaceId' => 121, 'slot' => 'main' ] ] )
				->build(),
		] ) );

		$this->expectException( LogicException::class );

		$lookup->getEntitySourceById( new ItemId( 'Q666' ) );
	}

	public function testGivenUriEntityId_WithMatchingConceptUri_ButWithDBEntitySource_throws() {
		$expectedSource = NewEntitySource::havingName( 'expected source' )
			->withConceptBaseUri( 'http://wikidata.org/entity/' )
			->build();
		$entityId = new FederatedPropertyId( 'http://wikidata.org/entity/P123' );

		$lookup = new EntitySourceLookup( $this->newEntitySourceDefinitionsFromSources( [
			NewEntitySource::havingName( 'some other source' )->build(),
			$expectedSource,
		] ) );

		$this->expectException( LogicException::class );
		$lookup->getEntitySourceById( $entityId );
	}

	private function newEntitySourceDefinitionsFromSources( array $sources ): EntitySourceDefinitions {
		return new EntitySourceDefinitions( $sources, $this->createStub( EntityTypeDefinitions::class ) );
	}

}
