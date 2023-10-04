<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers\Middleware;

use Generator;
use MediaWiki\Rest\ConditionalHeaderUtil;
use MediaWiki\Rest\RequestData;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LatestRevisionIdResult;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\RequestPreconditionCheck;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\RequestPreconditionCheckResult;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\Middleware\RequestPreconditionCheck
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\Middleware\RequestPreconditionCheckResult
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RequestPreconditionCheckTest extends TestCase {

	/**
	 * @dataProvider headersAndRevisionMetadataProvider
	 */
	public function testMeetsPreconditionForStatusCode(
		array $headers,
		LatestRevisionIdResult $revisionIdResult,
		RequestPreconditionCheckResult $expectedResult,
		string $method = 'GET'
	): void {
		$subjectId = new ItemId( 'Q42' );
		$preconditionCheck = new RequestPreconditionCheck(
			$this->newEntityRevisionLookupReturningResult( $subjectId, $revisionIdResult ),
			WikibaseRepo::getEntityIdParser(),
			fn() => $subjectId->getSerialization(),
			new ConditionalHeaderUtil()
		);

		$this->assertEquals(
			$expectedResult,
			$preconditionCheck->checkPreconditions(
				new RequestData( [ 'headers' => $headers, 'method' => $method ] )
			)
		);
	}

	public static function headersAndRevisionMetadataProvider(): Generator {
		yield 'If-None-Match - revision id match' => [
			'headers' => [ 'If-None-Match' => '"42"' ],
			'revisionIdResult' => LatestRevisionIdResult::concreteRevision( 42, '20201111070707' ),
			'expectedResult' => RequestPreconditionCheckResult::newConditionMetResult( 42, 304 ),
		];
		yield 'If-Modified-Since - not modified since specified date' => [
			'headers' => [ 'If-Modified-Since' => wfTimestamp( TS_RFC2822, '20201111070707' ) ],
			'revisionIdResult' => LatestRevisionIdResult::concreteRevision( 42, '20201111060606' ),
			'expectedResult' => RequestPreconditionCheckResult::newConditionMetResult( 42, 304 ),
		];
		yield 'If-None-Match - revision id mismatch' => [
			'headers' => [ 'If-None-Match' => '"41"' ],
			'revisionIdResult' => LatestRevisionIdResult::concreteRevision( 42, '20201111070707' ),
			'expectedResult' => RequestPreconditionCheckResult::newConditionUnmetResult(),
		];
		yield 'If-None-Match - non-GET request with wildcard' => [
			'headers' => [ 'If-None-Match' => '*' ],
			'revisionIdResult' => LatestRevisionIdResult::concreteRevision( 42, '20201111070707' ),
			'expectedResult' => RequestPreconditionCheckResult::newConditionMetResult( 42, 412 ),
			'method' => 'POST',
		];

		yield 'If-Match - revision id mismatch' => [
			'headers' => [ 'If-Match' => '"43"' ],
			'revisionIdResult' => LatestRevisionIdResult::concreteRevision( 42, '20201111070707' ),
			'expectedResult' => RequestPreconditionCheckResult::newConditionMetResult( 42, 412 ),
		];
		yield 'If-Unmodified-Since - item has been modified since specified date' => [
			'headers' => [ 'If-Unmodified-Since' => wfTimestamp( TS_RFC2822, '20201111070707' ) ],
			'revisionIdResult' => LatestRevisionIdResult::concreteRevision( 42, '20201111080808' ),
			'expectedResult' => RequestPreconditionCheckResult::newConditionMetResult( 42, 412 ),
		];
		yield 'If-Match - revision id match' => [
			'headers' => [ 'If-Match' => '"42"' ],
			'revisionIdResult' => LatestRevisionIdResult::concreteRevision( 42, '20201111070707' ),
			'expectedResult' => RequestPreconditionCheckResult::newConditionUnmetResult(),
		];
		yield 'If-Unmodified-Since - not modified since specified date' => [
			'headers' => [ 'If-Unmodified-Since' => wfTimestamp( TS_RFC2822, '20201111070707' ) ],
			'revisionIdResult' => LatestRevisionIdResult::concreteRevision( 42, '20201111070707' ),
			'expectedResult' => RequestPreconditionCheckResult::newConditionUnmetResult(),
		];
	}

	public function testGivenInvalidItemId_returnsMismatchResult(): void {
		$metadataRetriever = $this->createMock( ItemRevisionMetadataRetriever::class );
		$metadataRetriever->expects( $this->never() )->method( $this->anything() );
		$conditionalHeaderUtil = $this->createMock( ConditionalHeaderUtil::class );
		$conditionalHeaderUtil->expects( $this->never() )->method( $this->anything() );

		$preconditionCheck = new RequestPreconditionCheck(
			$this->createStub( EntityRevisionLookup::class ),
			WikibaseRepo::getEntityIdParser(),
			fn() => 'some-invalid-item-id',
			$conditionalHeaderUtil
		);

		$this->assertEquals(
			RequestPreconditionCheckResult::newConditionUnmetResult(),
			$preconditionCheck->checkPreconditions( new RequestData() )
		);
	}

	/**
	 * @dataProvider redirectOrNotExistMetadataProvider
	 */
	public function testGivenEntityDoesNotExistOrIsRedirect_returnsMismatchResult( LatestRevisionIdResult $revisionIdResult ): void {
		$entityId = new ItemId( 'Q42' );
		$conditionalHeaderUtil = $this->createMock( ConditionalHeaderUtil::class );
		$conditionalHeaderUtil->expects( $this->never() )->method( $this->anything() );

		$preconditionCheck = new RequestPreconditionCheck(
			$this->newEntityRevisionLookupReturningResult( $entityId, $revisionIdResult ),
			WikibaseRepo::getEntityIdParser(),
			fn() => $entityId->getSerialization(),
			$conditionalHeaderUtil
		);

		$this->assertEquals(
			RequestPreconditionCheckResult::newConditionUnmetResult(),
			$preconditionCheck->checkPreconditions( new RequestData() )
		);
	}

	public static function redirectOrNotExistMetadataProvider(): Generator {
		yield 'item redirect' => [
			LatestRevisionIdResult::redirect( 12345, new ItemId( 'Q25' ) ),
		];
		yield 'entity does not exist' => [ LatestRevisionIdResult::nonexistentEntity() ];
	}

	private function newEntityRevisionLookupReturningResult(
		EntityId $id,
		LatestRevisionIdResult $result
	): EntityRevisionLookup {
		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getLatestRevisionId' )
			->with( $id )
			->willReturn( $result );

		return $entityRevisionLookup;
	}

}
