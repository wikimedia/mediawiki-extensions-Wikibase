<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItem;

use Generator;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetaDataResult;

/**
 * @covers \Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetaDataResult
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LatestItemRevisionMetaDataResultTest extends TestCase {

	public function testGetRevisionId(): void {
		$revisionId = 123;
		$result = LatestItemRevisionMetaDataResult::concreteRevision( $revisionId, '20220101001122' );
		$this->assertSame( $revisionId, $result->getRevisionId() );
	}

	/**
	 * @dataProvider notAConcreteRevisionProvider
	 */
	public function testGivenNotAConcreteRevisionResult_getRevisionIdThrows( LatestItemRevisionMetaDataResult $result ): void {
		$this->expectException( RuntimeException::class );
		$result->getRevisionId();
	}

	public function testGetRevisionTimestamp(): void {
		$revisionTimestamp = '20220101001122';
		$result = LatestItemRevisionMetaDataResult::concreteRevision( 123, $revisionTimestamp );
		$this->assertSame( $revisionTimestamp, $result->getRevisionTimestamp() );
	}

	/**
	 * @dataProvider notAConcreteRevisionProvider
	 */
	public function testGivenNotAConcreteRevisionResult_getRevisionTimestampThrows( LatestItemRevisionMetaDataResult $result ): void {
		$this->expectException( RuntimeException::class );
		$result->getRevisionTimestamp();
	}

	public function testGetRedirectTarget(): void {
		$redirectTarget = new ItemId( 'Q123' );
		$result = LatestItemRevisionMetaDataResult::redirect( $redirectTarget );
		$this->assertSame( $redirectTarget, $result->getRedirectTarget() );
	}

	/**
	 * @dataProvider notARedirectProvider
	 */
	public function testGivenNotARedirect_getRedirectTargetThrows( LatestItemRevisionMetaDataResult $result ): void {
		$this->expectException( RuntimeException::class );
		$result->getRedirectTarget();
	}

	/**
	 * @dataProvider itemExistsProvider
	 */
	public function testItemExists( LatestItemRevisionMetaDataResult $result, bool $exists ): void {
		$this->assertSame( $exists, $result->itemExists() );
	}

	public function testIsRedirect(): void {
		$this->assertTrue( LatestItemRevisionMetaDataResult::redirect( new ItemId( 'Q777' ) )->isRedirect() );
	}

	/**
	 * @dataProvider notARedirectProvider
	 */
	public function testIsNotRedirect( LatestItemRevisionMetaDataResult $result ): void {
		$this->assertFalse( $result->isRedirect() );
	}

	public function notAConcreteRevisionProvider(): Generator {
		yield 'redirect' => [ LatestItemRevisionMetaDataResult::redirect( new ItemId( 'Q123' ) ) ];
		yield 'not found' => [ LatestItemRevisionMetaDataResult::itemNotFound() ];
	}

	public function itemExistsProvider(): Generator {
		yield 'concrete revision' => [
			LatestItemRevisionMetaDataResult::concreteRevision( 321, '20220101001122' ),
			true,
		];
		yield 'redirect' => [
			LatestItemRevisionMetaDataResult::redirect( new ItemId( 'Q666' ) ),
			true,
		];
		yield 'not found' => [
			LatestItemRevisionMetaDataResult::itemNotFound(),
			false,
		];
	}

	public function notARedirectProvider(): Generator {
		yield 'concrete revision' => [ LatestItemRevisionMetaDataResult::concreteRevision( 777, '20220101001122' ) ];
		yield 'not found' => [ LatestItemRevisionMetaDataResult::itemNotFound() ];
	}

}
