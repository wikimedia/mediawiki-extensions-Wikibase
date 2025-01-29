<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\Model;

use Generator;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\ReadModel\LatestStatementSubjectRevisionMetadataResult;

/**
 * @covers \Wikibase\Repo\RestApi\Domain\ReadModel\LatestStatementSubjectRevisionMetadataResult
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LatestStatementSubjectRevisionMetadataResultTest extends TestCase {

	public function testGetRevisionId(): void {
		$revisionId = 123;
		$result = LatestStatementSubjectRevisionMetadataResult::concreteRevision( $revisionId, '20220101001122' );
		$this->assertSame( $revisionId, $result->getRevisionId() );
	}

	/**
	 * @dataProvider notAConcreteRevisionProvider
	 */
	public function testGivenNotAConcreteRevisionResult_getRevisionIdThrows( LatestStatementSubjectRevisionMetadataResult $result ): void {
		$this->expectException( RuntimeException::class );
		$result->getRevisionId();
	}

	public function testGetRevisionTimestamp(): void {
		$revisionTimestamp = '20220101001122';
		$result = LatestStatementSubjectRevisionMetadataResult::concreteRevision( 123, $revisionTimestamp );
		$this->assertSame( $revisionTimestamp, $result->getRevisionTimestamp() );
	}

	/**
	 * @dataProvider notAConcreteRevisionProvider
	 */
	public function testGivenNotAConcreteRevisionResult_getRevisionTimestampThrows(
		LatestStatementSubjectRevisionMetadataResult $result
	): void {
		$this->expectException( RuntimeException::class );
		$result->getRevisionTimestamp();
	}

	public function testGetRedirectTarget(): void {
		$redirectTarget = new ItemId( 'Q123' );
		$result = LatestStatementSubjectRevisionMetadataResult::redirect( $redirectTarget );
		$this->assertSame( $redirectTarget, $result->getRedirectTarget() );
	}

	/**
	 * @dataProvider notARedirectProvider
	 */
	public function testGivenNotARedirect_getRedirectTargetThrows( LatestStatementSubjectRevisionMetadataResult $result ): void {
		$this->expectException( RuntimeException::class );
		$result->getRedirectTarget();
	}

	/**
	 * @dataProvider subjectExistsProvider
	 */
	public function testSubjectExists( LatestStatementSubjectRevisionMetadataResult $result, bool $exists ): void {
		$this->assertSame( $exists, $result->subjectExists() );
	}

	public function testIsRedirect(): void {
		$this->assertTrue( LatestStatementSubjectRevisionMetadataResult::redirect( new ItemId( 'Q777' ) )->isRedirect() );
	}

	/**
	 * @dataProvider notARedirectProvider
	 */
	public function testIsNotRedirect( LatestStatementSubjectRevisionMetadataResult $result ): void {
		$this->assertFalse( $result->isRedirect() );
	}

	public static function notAConcreteRevisionProvider(): Generator {
		yield 'redirect' => [ LatestStatementSubjectRevisionMetadataResult::redirect( new ItemId( 'Q123' ) ) ];
		yield 'not found' => [ LatestStatementSubjectRevisionMetadataResult::subjectNotFound() ];
	}

	public static function subjectExistsProvider(): Generator {
		yield 'concrete revision' => [
			LatestStatementSubjectRevisionMetadataResult::concreteRevision( 321, '20220101001122' ),
			true,
		];
		yield 'redirect' => [
			LatestStatementSubjectRevisionMetadataResult::redirect( new ItemId( 'Q666' ) ),
			true,
		];
		yield 'not found' => [
			LatestStatementSubjectRevisionMetadataResult::subjectNotFound(),
			false,
		];
	}

	public static function notARedirectProvider(): Generator {
		yield 'concrete revision' => [ LatestStatementSubjectRevisionMetadataResult::concreteRevision( 777, '20220101001122' ) ];
		yield 'not found' => [ LatestStatementSubjectRevisionMetadataResult::subjectNotFound() ];
	}

}
