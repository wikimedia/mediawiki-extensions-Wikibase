<?php

declare( strict_types = 1 );

namespace Wikibase\DataAccess\Tests;

use LogicException;
use MediaWiki\Revision\SlotRecord;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\ApiEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Lib\FederatedProperties\FederatedPropertyId;
use Wikibase\Lib\SubEntityTypesMapper;

/**
 * @covers \Wikibase\DataAccess\EntitySourceLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntitySourceLookupTest extends TestCase {

	public function testGivenUriEntityId_returnsEntitySourceWithMatchingConceptUri() {
		$expectedSource = new ApiEntitySource(
			'feddy-props',
			[ 'property' ],
			'http://wikidata.org/entity/',
			'',
			'',
			''
		);
		$entityId = new FederatedPropertyId( 'http://wikidata.org/entity/P123', 'P123' );

		$lookup = new EntitySourceLookup( $this->newEntitySourceDefinitionsFromSources( [
			NewDatabaseEntitySource::havingName( 'some other source' )->build(),
			$expectedSource,
		] ), new SubEntityTypesMapper( [] ) );

		$this->assertSame( $expectedSource, $lookup->getEntitySourceById( $entityId ) );
	}

	public function testGivenUnprefixedEntityId_returnsDbEntitySourceForEntityType() {
		$id = new NumericPropertyId( 'P123' );
		$expectedSource = NewDatabaseEntitySource::havingName( 'im a db source!' )
			->withEntityNamespaceIdsAndSlots( [ 'property' => [ 'namespaceId' => 121, 'slot' => SlotRecord::MAIN ] ] )
			->build();

		$lookup = new EntitySourceLookup( $this->newEntitySourceDefinitionsFromSources( [
			NewDatabaseEntitySource::havingName( 'some other source' )->build(),
			$expectedSource,
		] ), new SubEntityTypesMapper( [] ) );

		$this->assertSame( $expectedSource, $lookup->getEntitySourceById( $id ) );
	}

	public function testGivenEntityIdWithNoMatchingSource_throwsException() {
		$lookup = new EntitySourceLookup( $this->newEntitySourceDefinitionsFromSources( [
			NewDatabaseEntitySource::havingName( 'im a property source' )
				->withEntityNamespaceIdsAndSlots( [ 'property' => [ 'namespaceId' => 121, 'slot' => SlotRecord::MAIN ] ] )
				->build(),
		] ), new SubEntityTypesMapper( [] ) );

		$this->expectException( LogicException::class );

		$lookup->getEntitySourceById( new ItemId( 'Q666' ) );
	}

	public function testGivenUriEntityId_WithMatchingConceptUri_ButWithDBEntitySource_throws() {
		$expectedSource = NewDatabaseEntitySource::havingName( 'expected source' )
			->withConceptBaseUri( 'http://wikidata.org/entity/' )
			->build();
		$entityId = new FederatedPropertyId( 'http://wikidata.org/entity/P123', 'P123' );

		$lookup = new EntitySourceLookup( $this->newEntitySourceDefinitionsFromSources( [
			NewDatabaseEntitySource::havingName( 'some other source' )->build(),
			$expectedSource,
		] ), new SubEntityTypesMapper( [] ) );

		$this->expectException( LogicException::class );
		$lookup->getEntitySourceById( $entityId );
	}

	public function testGivenSubEntityId_returnsParentEntitySource() {
		$subEntityId = $this->createStub( EntityId::class );
		$subEntityId->method( 'getSerialization' )->willReturn( 'L123-F123' );
		$subEntityId->method( 'getEntityType' )->willReturn( 'form' );

		$expectedSource = NewDatabaseEntitySource::havingName( 'lexeme source' )
			->withEntityNamespaceIdsAndSlots( [ 'lexeme' => [ 'namespaceId' => 121, 'slot' => SlotRecord::MAIN ] ] )
			->build();

		$lookup = new EntitySourceLookup(
			$this->newEntitySourceDefinitionsFromSources( [
				NewDatabaseEntitySource::havingName( 'some other source' )->build(),
				$expectedSource,
			] ),
			new SubEntityTypesMapper( [ 'lexeme' => [ 'form', 'sense' ] ] )
		);

		$this->assertSame( $expectedSource, $lookup->getEntitySourceById( $subEntityId ) );
	}

	private function newEntitySourceDefinitionsFromSources( array $sources ): EntitySourceDefinitions {
		return new EntitySourceDefinitions( $sources, new SubEntityTypesMapper( [] ) );
	}

}
