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
use Wikibase\Repo\RestApi\Domain\ReadModel\PermissionCheckResult;
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

	public function testGivenUserIsAuthorizedToCreateAnItem(): void {
		$permissionChecker = $this->createMock( WikibaseEntityPermissionChecker::class );
		$permissionChecker->expects( $this->once() )
			->method( 'canCreateItem' )
			->with( User::newAnonymous() )
			->willReturn( PermissionCheckResult::newAllowed() );

		$this->newAssertUserIsAuthorized( $permissionChecker )->checkCreateItemPermissions( User::newAnonymous() );
	}

	public function testGivenUserIsUnauthorizedToCreateAnItem_throwsUseCaseError(): void {
		$permissionChecker = $this->createMock( WikibaseEntityPermissionChecker::class );
		$permissionChecker->expects( $this->once() )
			->method( 'canCreateItem' )
			->with( User::newAnonymous() )
			->willReturn( PermissionCheckResult::newDenialForUnknownReason() );

		try {
			$this->newAssertUserIsAuthorized( $permissionChecker )->checkCreateItemPermissions( User::newAnonymous() );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::PERMISSION_DENIED_UNKNOWN_REASON, $e->getErrorCode() );
		}
	}

	/**
	 * @dataProvider provideEntityId
	 */
	public function testGivenUserIsAuthorizedToEdit( EntityId $entityId ): void {
		$permissionChecker = $this->createMock( WikibaseEntityPermissionChecker::class );
		$permissionChecker->expects( $this->once() )
			->method( 'canEdit' )
			->with( User::newAnonymous(), $entityId )
			->willReturn( PermissionCheckResult::newAllowed() );

		$this->newAssertUserIsAuthorized( $permissionChecker )->checkEditPermissions( $entityId, User::newAnonymous() );
	}

	/**
	 * @dataProvider editPermissionDeniedProvider
	 */
	public function testGivenUserIsUnauthorizedToEdit_throwsUseCaseError(
		EntityId $entityId,
		PermissionCheckResult $checkResult,
		UseCaseError $expectedError
	): void {
		$permissionChecker = $this->createMock( WikibaseEntityPermissionChecker::class );
		$permissionChecker->expects( $this->once() )
			->method( 'canEdit' )
			->with( User::newAnonymous(), $entityId )
			->willReturn( $checkResult );

		try {
			$this->newAssertUserIsAuthorized( $permissionChecker )->checkEditPermissions( $entityId, User::newAnonymous() );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public function provideEntityId(): Generator {
		yield 'item id' => [ new ItemId( 'Q123' ) ];
		yield 'property id' => [ new NumericPropertyId( 'P123' ) ];
	}

	public function editPermissionDeniedProvider(): Generator {
		foreach ( $this->provideEntityId() as [ $id ] ) {
			yield "{$id->getEntityType()} - permission denied, unknown reason" => [
				$id,
				PermissionCheckResult::newDenialForUnknownReason(),
				new UseCaseError(
					UseCaseError::PERMISSION_DENIED_UNKNOWN_REASON,
					'You have no permission to edit this resource'
				),
			];

			yield "{$id->getEntityType()} - permission denied, page protected" => [
				$id,
				PermissionCheckResult::newPageProtected(),
				new UseCaseError(
					UseCaseError::PERMISSION_DENIED,
					'Access to resource is denied',
					[ UseCaseError::CONTEXT_REASON => UseCaseError::PERMISSION_DENIED_REASON_PAGE_PROTECTED ]
				),
			];
		}
	}

	private function newAssertUserIsAuthorized( PermissionChecker $permissionChecker ): AssertUserIsAuthorized {
		return new AssertUserIsAuthorized( $permissionChecker );
	}

}
