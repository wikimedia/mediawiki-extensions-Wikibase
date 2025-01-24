<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LatestRevisionIdResult;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\WikibaseEntityRevisionLookupPropertyRevisionMetadataRetriever;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\DataAccess\WikibaseEntityRevisionLookupPropertyRevisionMetadataRetriever
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibaseEntityRevisionLookupPropertyRevisionMetadataRetrieverTest extends TestCase {

	public function testGivenConcreteRevision_getLatestRevisionMetadataReturnsMetadata(): void {
		$property = new NumericPropertyId( 'P1234' );
		$expectedRevisionId = 777;
		$expectedRevisionTimestamp = '20201111070707';
		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getLatestRevisionId' )
			->with( $property )
			->willReturn( LatestRevisionIdResult::concreteRevision( $expectedRevisionId, $expectedRevisionTimestamp ) );

		$metaDataRetriever = new WikibaseEntityRevisionLookupPropertyRevisionMetadataRetriever( $entityRevisionLookup );
		$result = $metaDataRetriever->getLatestRevisionMetadata( $property );

		$this->assertSame( $expectedRevisionId, $result->getRevisionId() );
		$this->assertSame( $expectedRevisionTimestamp, $result->getRevisionTimestamp() );
	}

	public function testGivenPropertyDoesNotExist_getLatestRevisionMetadataReturnsPropertyNotFoundResult(): void {
		$nonexistentProperty = new NumericPropertyId( 'P9999999' );
		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getLatestRevisionId' )
			->with( $nonexistentProperty )
			->willReturn( LatestRevisionIdResult::nonexistentEntity() );

		$metaDataRetriever = new WikibaseEntityRevisionLookupPropertyRevisionMetadataRetriever( $entityRevisionLookup );
		$result = $metaDataRetriever->getLatestRevisionMetadata( $nonexistentProperty );

		$this->assertFalse( $result->propertyExists() );
	}

}
