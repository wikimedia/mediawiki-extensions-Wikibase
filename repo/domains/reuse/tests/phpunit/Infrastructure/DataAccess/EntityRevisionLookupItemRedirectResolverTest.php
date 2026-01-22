<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\DataAccess;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LatestRevisionIdResult;
use Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\EntityRevisionLookupItemRedirectResolver;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\EntityRevisionLookupItemRedirectResolver
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityRevisionLookupItemRedirectResolverTest extends TestCase {

	/**
	 * @dataProvider revisionLookupResultProvider
	 */
	public function testResolveRedirect( ItemId $requestedId, LatestRevisionIdResult $revisionIdResult, ItemId $expectedId ): void {
		$revisionLookup = $this->createStub( EntityRevisionLookup::class );
		$revisionLookup->method( 'getLatestRevisionId' )->willReturn( $revisionIdResult );
		$resolver = new EntityRevisionLookupItemRedirectResolver( $revisionLookup );

		$this->assertEquals( $expectedId, $resolver->resolveRedirect( $requestedId ) );
	}

	public function revisionLookupResultProvider(): Generator {
		yield 'no redirect' => [
			new ItemId( 'Q123' ),
			LatestRevisionIdResult::concreteRevision( 123, '20260101001122' ),
			new ItemId( 'Q123' ),
		];
		yield 'item not found' => [
			new ItemId( 'Q9999' ),
			LatestRevisionIdResult::nonexistentEntity(),
			new ItemId( 'Q9999' ),
		];
		yield 'redirect' => [
			new ItemId( 'Q321' ),
			LatestRevisionIdResult::redirect( 321, new ItemId( 'Q123' ) ),
			new ItemId( 'Q123' ),
		];
	}
}
