<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\Model;

use Generator;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;

/**
 * @covers \Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LatestItemRevisionMetadataResultTest extends TestCase {

	public function testGetRevisionId(): void {
		$revisionId = 123;
		$result = LatestItemRevisionMetadataResult::concreteRevision( $revisionId, '20220101001122' );
		$this->assertSame( $revisionId, $result->getRevisionId() );
	}

	/**
	 * @dataProvider notAConcreteRevisionProvider
	 */
	public function testGivenNotAConcreteRevisionResult_getRevisionIdThrows( LatestItemRevisionMetadataResult $result ): void {
		$this->expectException( RuntimeException::class );
		$result->getRevisionId();
	}

	public function testGetRevisionTimestamp(): void {
		$revisionTimestamp = '20220101001122';
		$result = LatestItemRevisionMetadataResult::concreteRevision( 123, $revisionTimestamp );
		$this->assertSame( $revisionTimestamp, $result->getRevisionTimestamp() );
	}

	/**
	 * @dataProvider notAConcreteRevisionProvider
	 */
	public function testGivenNotAConcreteRevisionResult_getRevisionTimestampThrows( LatestItemRevisionMetadataResult $result ): void {
		$this->expectException( RuntimeException::class );
		$result->getRevisionTimestamp();
	}

	public function testGetRedirectTarget(): void {
		$redirectTarget = new ItemId( 'Q123' );
		$result = LatestItemRevisionMetadataResult::redirect( $redirectTarget );
		$this->assertSame( $redirectTarget, $result->getRedirectTarget() );
	}

	/**
	 * @dataProvider notARedirectProvider
	 */
	public function testGivenNotARedirect_getRedirectTargetThrows( LatestItemRevisionMetadataResult $result ): void {
		$this->expectException( RuntimeException::class );
		$result->getRedirectTarget();
	}

	/**
	 * @dataProvider itemExistsProvider
	 */
	public function testItemExists( LatestItemRevisionMetadataResult $result, bool $exists ): void {
		$this->assertSame( $exists, $result->itemExists() );
	}

	public function testIsRedirect(): void {
		$this->assertTrue( LatestItemRevisionMetadataResult::redirect( new ItemId( 'Q777' ) )->isRedirect() );
	}

	/**
	 * @dataProvider notARedirectProvider
	 */
	public function testIsNotRedirect( LatestItemRevisionMetadataResult $result ): void {
		$this->assertFalse( $result->isRedirect() );
	}

	public function notAConcreteRevisionProvider(): Generator {
		yield 'redirect' => [ LatestItemRevisionMetadataResult::redirect( new ItemId( 'Q123' ) ) ];
		yield 'not found' => [ LatestItemRevisionMetadataResult::itemNotFound() ];
	}

	public function itemExistsProvider(): Generator {
		yield 'concrete revision' => [
			LatestItemRevisionMetadataResult::concreteRevision( 321, '20220101001122' ),
			true,
		];
		yield 'redirect' => [
			LatestItemRevisionMetadataResult::redirect( new ItemId( 'Q666' ) ),
			true,
		];
		yield 'not found' => [
			LatestItemRevisionMetadataResult::itemNotFound(),
			false,
		];
	}

	public function notARedirectProvider(): Generator {
		yield 'concrete revision' => [ LatestItemRevisionMetadataResult::concreteRevision( 777, '20220101001122' ) ];
		yield 'not found' => [ LatestItemRevisionMetadataResult::itemNotFound() ];
	}

}
