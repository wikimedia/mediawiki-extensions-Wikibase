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

	/**
	 * @dataProvider itemCreationDeniedProvider
	 */
	public function testGivenUserIsUnauthorizedToCreateAnItem_throwsUseCaseError(
		PermissionCheckResult $checkResult,
		UseCaseError $expectedError
	): void {
		$permissionChecker = $this->createMock( WikibaseEntityPermissionChecker::class );
		$permissionChecker->expects( $this->once() )
			->method( 'canCreateItem' )
			->with( User::newAnonymous() )
			->willReturn( $checkResult );

		try {
			$this->newAssertUserIsAuthorized( $permissionChecker )->checkCreateItemPermissions( User::newAnonymous() );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public static function itemCreationDeniedProvider(): Generator {
		yield 'unknown reason' => [
			PermissionCheckResult::newDenialForUnknownReason(),
			new UseCaseError(
				UseCaseError::PERMISSION_DENIED_UNKNOWN_REASON,
				'You have no permission to create an item'
			),
		];

		yield 'user blocked' => [
			PermissionCheckResult::newUserBlocked(),
			UseCaseError::newPermissionDenied( UseCaseError::PERMISSION_DENIED_REASON_USER_BLOCKED ),
		];
	}

	public function testGivenUserIsAuthorizedToCreateProperty(): void {
		$permissionChecker = $this->createMock( WikibaseEntityPermissionChecker::class );
		$permissionChecker->expects( $this->once() )
			->method( 'canCreateProperty' )
			->with( User::newAnonymous() )
			->willReturn( PermissionCheckResult::newAllowed() );

		$this->newAssertUserIsAuthorized( $permissionChecker )->checkCreatePropertyPermissions( User::newAnonymous() );
	}

	/**
	 * @dataProvider propertyCreationDeniedProvider
	 */
	public function testGivenUserIsUnauthorizedToCreateProperty_throwsUseCaseError(
		PermissionCheckResult $checkResult,
		UseCaseError $expectedError
	): void {
		$permissionChecker = $this->createMock( WikibaseEntityPermissionChecker::class );
		$permissionChecker->expects( $this->once() )
			->method( 'canCreateProperty' )
			->with( User::newAnonymous() )
			->willReturn( $checkResult );

		try {
			$this->newAssertUserIsAuthorized( $permissionChecker )->checkCreatePropertyPermissions( User::newAnonymous() );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public static function propertyCreationDeniedProvider(): Generator {
		yield 'unknown reason' => [
			PermissionCheckResult::newDenialForUnknownReason(),
			new UseCaseError(
				UseCaseError::PERMISSION_DENIED_UNKNOWN_REASON,
				'You have no permission to create a property'
			),
		];

		yield 'user blocked' => [
			PermissionCheckResult::newUserBlocked(),
			UseCaseError::newPermissionDenied( UseCaseError::PERMISSION_DENIED_REASON_USER_BLOCKED ),
		];
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

	public static function provideEntityId(): Generator {
		yield 'item id' => [ new ItemId( 'Q123' ) ];
		yield 'property id' => [ new NumericPropertyId( 'P123' ) ];
	}

	public static function editPermissionDeniedProvider(): Generator {
		foreach ( self::provideEntityId() as [ $id ] ) {
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
				UseCaseError::newPermissionDenied( UseCaseError::PERMISSION_DENIED_REASON_PAGE_PROTECTED ),
			];

			yield "{$id->getEntityType()} - permission denied, user blocked" => [
				$id,
				PermissionCheckResult::newUserBlocked(),
				UseCaseError::newPermissionDenied( UseCaseError::PERMISSION_DENIED_REASON_USER_BLOCKED ),
			];
		}
	}

	private function newAssertUserIsAuthorized( PermissionChecker $permissionChecker ): AssertUserIsAuthorized {
		return new AssertUserIsAuthorized( $permissionChecker );
	}

}
