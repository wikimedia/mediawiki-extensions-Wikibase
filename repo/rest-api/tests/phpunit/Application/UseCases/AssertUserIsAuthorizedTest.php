<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\ReadModel\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PermissionChecker;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\WikibaseEntityPermissionChecker;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AssertUserIsAuthorizedTest extends TestCase {

	/**
	 * @dataProvider provideEntityId
	 */
	public function testGivenUserIsAuthorized( EntityId $entityId ): void {
		$metadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::concreteRevision( 321, '20201111070707' ) );

		$permissionChecker = $this->createMock( WikibaseEntityPermissionChecker::class );
		$permissionChecker->expects( $this->once() )
			->method( 'canEdit' )
			->with( User::newAnonymous(), $entityId )
			->willReturn( true );

		$this->newAssertUserIsAuthorized( $permissionChecker )->execute( $entityId, User::newAnonymous() );
	}

	/**
	 * @dataProvider provideEntityId
	 */
	public function testGivenUserIsUnauthorized_throwsUseCaseError( EntityId $entityId ): void {
		$metadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::concreteRevision( 321, '20201111070707' ) );

		$permissionChecker = $this->createMock( WikibaseEntityPermissionChecker::class );
		$permissionChecker->expects( $this->once() )
			->method( 'canEdit' )
			->with( User::newAnonymous(), $entityId )
			->willReturn( false );

		try {
			$this->newAssertUserIsAuthorized( $permissionChecker )->execute( $entityId, User::newAnonymous() );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame(
				UseCaseError::PERMISSION_DENIED,
				$e->getErrorCode()
			);
		}
	}

	public function provideEntityId(): Generator {
		yield 'item id' => [ new ItemId( 'Q123' ) ];
		yield 'property id' => [ new NumericPropertyId( 'P123' ) ];
	}

	private function newAssertUserIsAuthorized( PermissionChecker $permissionChecker ): AssertUserIsAuthorized {
		return new AssertUserIsAuthorized( $permissionChecker );
	}

}
