<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Tests\FederatedProperties;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Lib\FederatedProperties\FederatedPropertyId;
use Wikibase\Repo\FederatedProperties\ApiEntityExistenceChecker;
use Wikibase\Repo\FederatedProperties\ApiEntityLookup;

/**
 * @covers \Wikibase\Repo\FederatedProperties\ApiEntityExistenceChecker
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ApiEntityExistenceCheckerTest extends TestCase {

	public function testGivenApiResultContainsMissingKey_existsReturnsFalse() {
		$id = new FederatedPropertyId( 'http://wikidata.org/entity/P321', 'P321' );

		$apiEntityLookup = $this->createMock( ApiEntityLookup::class );
		$apiEntityLookup->expects( $this->once() )
			->method( 'getResultPartForId' )
			->with( $id )
			->willReturn( [ 'id' => 'P321', 'missing' => '' ] );

		$existenceChecker = new ApiEntityExistenceChecker( $apiEntityLookup );

		$this->assertFalse( $existenceChecker->exists( $id ) );
	}

	public function testGivenApiResultDoesNotContainMissingKey_existsReturnsTrue() {
		$id = new FederatedPropertyId( 'http://wikidata.org/entity/P123', 'P123' );

		$apiEntityLookup = $this->createMock( ApiEntityLookup::class );
		$apiEntityLookup->expects( $this->once() )
			->method( 'getResultPartForId' )
			->with( $id )
			->willReturn( [
				'id' => 'P123',
				'datatype' => 'string',
				// ...
			] );

		$existenceChecker = new ApiEntityExistenceChecker( $apiEntityLookup );

		$this->assertTrue( $existenceChecker->exists( $id ) );
	}

	public function testExistsBatch() {
		$p123 = new FederatedPropertyId( 'http://wikidata.org/entity/P123', 'P123' );
		$p321 = new FederatedPropertyId( 'http://wikidata.org/entity/P321', 'P321' );
		$ids = [ $p123, $p321 ];

		$apiEntityLookup = $this->createMock( ApiEntityLookup::class );
		$apiEntityLookup->expects( $this->once() )
			->method( 'fetchEntities' )
			->with( $ids );
		$apiEntityLookup->expects( $this->exactly( 2 ) )
			->method( 'getResultPartForId' )
			->withConsecutive(
				[ $ids[0] ],
				[ $ids[1] ]
			)
			->willReturnOnConsecutiveCalls(
				[ 'id' => $p123->getRemoteIdSerialization(), 'datatype' => 'string' ],
				[ 'id' => $p321->getRemoteIdSerialization(), 'missing' => '' ]
			);

		$existenceChecker = new ApiEntityExistenceChecker( $apiEntityLookup );
		$result = $existenceChecker->existsBatch( $ids );

		$expected = [ $p123->getSerialization() => true, $p321->getSerialization() => false ];
		$this->assertSame( $expected, $result );
	}

	public function testGivenNotAFederatedPropertyId_existsThrows() {
		$existenceChecker = new ApiEntityExistenceChecker( $this->createStub( ApiEntityLookup::class ) );

		$this->expectException( InvalidArgumentException::class );

		$existenceChecker->exists( new NumericPropertyId( 'P777' ) );
	}

	public function testGivenListWithNonFederatedPropertyId_existsBatchThrows() {
		$existenceChecker = new ApiEntityExistenceChecker( $this->createStub( ApiEntityLookup::class ) );

		$this->expectException( InvalidArgumentException::class );

		$existenceChecker->existsBatch( [
			new FederatedPropertyId( 'http://yolo/entity/P666', 'P666' ),
			new NumericPropertyId( 'P777' ),
		] );
	}

}
