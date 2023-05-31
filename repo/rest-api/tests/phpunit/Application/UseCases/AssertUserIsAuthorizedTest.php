<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
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

	public function testGivenUserIsAuthorized(): void {
		$itemId = new ItemId( 'Q123' );

		$metadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::concreteRevision( 321, '20201111070707' ) );

		$permissionChecker = $this->createMock( WikibaseEntityPermissionChecker::class );
		$permissionChecker->expects( $this->once() )
			->method( 'canEdit' )
			->with( User::newAnonymous(), $itemId )
			->willReturn( true );

		$this->newAssertUserIsAuthorized( $permissionChecker )->execute( $itemId, null );
	}

	public function testGivenUserIsUnauthorized_throwsUseCaseError(): void {
		$itemId = new ItemId( 'Q123' );

		$metadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::concreteRevision( 321, '20201111070707' ) );

		$permissionChecker = $this->createMock( WikibaseEntityPermissionChecker::class );
		$permissionChecker->expects( $this->once() )
			->method( 'canEdit' )
			->with( User::newAnonymous(), $itemId )
			->willReturn( false );

		try {
			$this->newAssertUserIsAuthorized( $permissionChecker )->execute( $itemId, null );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame(
				UseCaseError::PERMISSION_DENIED,
				$e->getErrorCode()
			);
		}
	}

	private function newAssertUserIsAuthorized( PermissionChecker $permissionChecker ): AssertUserIsAuthorized {
		return new AssertUserIsAuthorized( $permissionChecker );
	}

}
