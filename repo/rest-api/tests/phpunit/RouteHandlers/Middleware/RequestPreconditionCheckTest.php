<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers\Middleware;

use Generator;
use MediaWiki\Rest\ConditionalHeaderUtil;
use MediaWiki\Rest\RequestData;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\RequestPreconditionCheck;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\RequestPreconditionCheckResult;

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
		LatestItemRevisionMetadataResult $revisionMetadataResult,
		?int $expectedStatusCode,
		string $method = 'GET'
	): void {
		$itemId = new ItemId( 'Q42' );
		$preconditionCheck = new RequestPreconditionCheck(
			$this->newMetadataRetrieverReturningResult( $itemId, $revisionMetadataResult ),
			function () use ( $itemId ) {
				return $itemId->getSerialization();
			},
			new ConditionalHeaderUtil()
		);

		$result = $preconditionCheck->checkPreconditions(
			new RequestData( [
				'headers' => $headers,
				'method' => $method,
			] )
		);

		$this->assertSame( $expectedStatusCode, $result->getStatusCode() );
		if ( $expectedStatusCode ) {
			$this->assertEquals( $revisionMetadataResult, $result->getRevisionMetadata() );
		}
	}

	public function headersAndRevisionMetadataProvider(): Generator {
		yield 'If-None-Match - revision id match' => [
			'headers' => [ 'If-None-Match' => '"42"' ],
			'revisionMetadataResult' => LatestItemRevisionMetadataResult::concreteRevision( 42, '20201111070707' ),
			'statusCodeToCheck' => 304,
		];
		yield 'If-Modified-Since - not modified since specified date' => [
			'headers' => [ 'If-Modified-Since' => wfTimestamp( TS_RFC2822, '20201111070707' ) ],
			'revisionMetadataResult' => LatestItemRevisionMetadataResult::concreteRevision( 42, '20201111060606' ),
			'statusCodeToCheck' => 304,
		];
		yield 'If-None-Match - revision id mismatch' => [
			'headers' => [ 'If-None-Match' => '"41"' ],
			'revisionMetadataResult' => LatestItemRevisionMetadataResult::concreteRevision( 42, '20201111070707' ),
			'statusCodeToCheck' => null,
		];
		yield 'If-None-Match - non-GET request with wildcard' => [
			'headers' => [ 'If-None-Match' => '*' ],
			'revisionMetadataResult' => LatestItemRevisionMetadataResult::concreteRevision( 42, '20201111070707' ),
			'statusCodeToCheck' => 412,
			'method' => 'POST',
		];

		yield 'If-Match - revision id mismatch' => [
			'headers' => [ 'If-Match' => '"43"' ],
			'revisionMetadataResult' => LatestItemRevisionMetadataResult::concreteRevision( 42, '20201111070707' ),
			'statusCodeToCheck' => 412,
		];
		yield 'If-Unmodified-Since - item has been modified since specified date' => [
			'headers' => [ 'If-Unmodified-Since' => wfTimestamp( TS_RFC2822, '20201111070707' ) ],
			'revisionMetadataResult' => LatestItemRevisionMetadataResult::concreteRevision( 42, '20201111080808' ),
			'statusCodeToCheck' => 412,
		];
		yield 'If-Match - revision id match' => [
			'headers' => [ 'If-Match' => '"42"' ],
			'revisionMetadataResult' => LatestItemRevisionMetadataResult::concreteRevision( 42, '20201111070707' ),
			'statusCodeToCheck' => null,
		];
		yield 'If-Unmodified-Since - not modified since specified date' => [
			'headers' => [ 'If-Unmodified-Since' => wfTimestamp( TS_RFC2822, '20201111070707' ) ],
			'revisionMetadataResult' => LatestItemRevisionMetadataResult::concreteRevision( 42, '20201111070707' ),
			'statusCodeToCheck' => null,
		];
	}

	public function testGivenInvalidItemId_returnsMismatchResult(): void {
		$metadataRetriever = $this->createMock( ItemRevisionMetadataRetriever::class );
		$metadataRetriever->expects( $this->never() )->method( $this->anything() );
		$conditionalHeaderUtil = $this->createMock( ConditionalHeaderUtil::class );
		$conditionalHeaderUtil->expects( $this->never() )->method( $this->anything() );

		$preconditionCheck = new RequestPreconditionCheck(
			$metadataRetriever,
			function () {
				return 'some-invalid-item-id';
			},
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
	public function testGivenItemDoesNotExistOrIsRedirect_returnsMismatchResult( LatestItemRevisionMetadataResult $metadataResult ): void {
		$itemId = new ItemId( 'Q42' );
		$conditionalHeaderUtil = $this->createMock( ConditionalHeaderUtil::class );
		$conditionalHeaderUtil->expects( $this->never() )->method( $this->anything() );

		$preconditionCheck = new RequestPreconditionCheck(
			$this->newMetadataRetrieverReturningResult( $itemId, $metadataResult ),
			function () use ( $itemId ) {
				return $itemId->getSerialization();
			},
			$conditionalHeaderUtil
		);

		$this->assertEquals(
			RequestPreconditionCheckResult::newConditionUnmetResult(),
			$preconditionCheck->checkPreconditions( new RequestData() )
		);
	}

	public function redirectOrNotExistMetadataProvider(): Generator {
		yield 'item redirect' => [
			LatestItemRevisionMetadataResult::redirect( new ItemId( 'Q24' ) ),
		];
		yield 'item does not exist' => [
			LatestItemRevisionMetadataResult::itemNotFound(),
		];
	}

	private function newMetadataRetrieverReturningResult(
		ItemId $id,
		LatestItemRevisionMetadataResult $result
	): ItemRevisionMetadataRetriever {
		$revisionMetadataLookup = $this->createMock( ItemRevisionMetadataRetriever::class );
		$revisionMetadataLookup->expects( $this->once() )
			->method( 'getLatestRevisionMetadata' )
			->with( $id )
			->willReturn( $result );

		return $revisionMetadataLookup;
	}

}
