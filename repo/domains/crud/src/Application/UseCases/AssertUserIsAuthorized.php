<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\Domains\Crud\Domain\Model\User;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\PermissionCheckResult;
use Wikibase\Repo\Domains\Crud\Domain\Services\PermissionChecker;

/**
 * @license GPL-2.0-or-later
 */
class AssertUserIsAuthorized {

	private PermissionChecker $permissionChecker;

	public function __construct( PermissionChecker $permissionChecker ) {
		$this->permissionChecker = $permissionChecker;
	}

	public function checkEditPermissions( EntityId $id, User $user ): void {
		$permissionCheckResult = $this->permissionChecker->canEdit( $user, $id );
		if ( !$permissionCheckResult->isDenied() ) {
			return;
		}

		$this->throwUseCaseError(
			$permissionCheckResult->getDenialReason(),
			'You have no permission to edit this resource'
		);
	}

	public function checkCreateItemPermissions( User $user ): void {
		$permissionCheckResult = $this->permissionChecker->canCreateItem( $user );
		if ( !$permissionCheckResult->isDenied() ) {
			return;
		}

		$this->throwUseCaseError(
			$permissionCheckResult->getDenialReason(),
			'You have no permission to create an item'
		);
	}

	public function checkCreatePropertyPermissions( User $user ): void {
		$permissionCheckResult = $this->permissionChecker->canCreateProperty( $user );
		if ( !$permissionCheckResult->isDenied() ) {
			return;
		}

		$this->throwUseCaseError(
			$permissionCheckResult->getDenialReason(),
			'You have no permission to create a property'
		);
	}

	private function throwUseCaseError( ?int $reason, string $defaultMessage ): never {
		throw match ( $reason ) {
			PermissionCheckResult::DENIAL_REASON_PAGE_PROTECTED => UseCaseError::newPermissionDenied(
				UseCaseError::PERMISSION_DENIED_REASON_PAGE_PROTECTED
			),
			PermissionCheckResult::DENIAL_REASON_USER_BLOCKED => UseCaseError::newPermissionDenied(
				UseCaseError::PERMISSION_DENIED_REASON_USER_BLOCKED
			),
			PermissionCheckResult::DENIAL_REASON_IP_BLOCKED => UseCaseError::newPermissionDenied(
				UseCaseError::PERMISSION_DENIED_REASON_IP_BLOCKED
			),
			default => new UseCaseError(
				UseCaseError::PERMISSION_DENIED_UNKNOWN_REASON,
				$defaultMessage
			)
		};
	}

}
