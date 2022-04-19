<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItem;

use Generator;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Model\ItemRevision;
use Wikibase\Repo\RestApi\Domain\Model\ItemRevisionResult;

/**
 * @covers \Wikibase\Repo\RestApi\Domain\Model\ItemRevisionResult
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemRevisionResultTest extends TestCase {

	public function testGetItemRevision(): void {
		$revision = $this->createStub( ItemRevision::class );
		$result = ItemRevisionResult::concreteRevision( $revision );
		$this->assertSame( $revision, $result->getRevision() );
	}

	/**
	 * @dataProvider notAConcreteRevisionProvider
	 */
	public function testGivenNotAConcreteRevisionResult_getItemRevisionThrows( ItemRevisionResult $result ): void {
		$this->expectException( RuntimeException::class );
		$result->getRevision();
	}

	public function testGetRedirectTarget(): void {
		$redirectTarget = new ItemId( 'Q123' );
		$result = ItemRevisionResult::redirect( $redirectTarget );
		$this->assertSame( $redirectTarget, $result->getRedirectTarget() );
	}

	/**
	 * @dataProvider notARedirectProvider
	 */
	public function testGivenNotARedirect_getRedirectTargetThrows( ItemRevisionResult $result ): void {
		$this->expectException( RuntimeException::class );
		$result->getRedirectTarget();
	}

	/**
	 * @dataProvider itemExistsProvider
	 */
	public function testItemExists( ItemRevisionResult $result, bool $exists ): void {
		$this->assertSame( $exists, $result->itemExists() );
	}

	public function testIsRedirect(): void {
		$this->assertTrue( ItemRevisionResult::redirect( new ItemId( 'Q777' ) )->isRedirect() );
	}

	/**
	 * @dataProvider notARedirectProvider
	 */
	public function testIsNotRedirect( ItemRevisionResult $result ): void {
		$this->assertFalse( $result->isRedirect() );
	}

	public function notAConcreteRevisionProvider(): Generator {
		yield 'redirect' => [ ItemRevisionResult::redirect( new ItemId( 'Q123' ) ) ];
		yield 'not found' => [ ItemRevisionResult::itemNotFound() ];
	}

	public function itemExistsProvider(): Generator {
		yield 'concrete revision' => [
			ItemRevisionResult::concreteRevision( $this->createStub( ItemRevision::class ) ),
			true,
		];
		yield 'redirect' => [
			ItemRevisionResult::redirect( new ItemId( 'Q666' ) ),
			true,
		];
		yield 'not found' => [
			ItemRevisionResult::itemNotFound(),
			false,
		];
	}

	public function notARedirectProvider(): Generator {
		yield 'concrete revision' => [ ItemRevisionResult::concreteRevision( $this->createStub( ItemRevision::class ) ) ];
		yield 'not found' => [ ItemRevisionResult::itemNotFound() ];
	}

}
