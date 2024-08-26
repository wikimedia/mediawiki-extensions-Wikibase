<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\ReadModel\PermissionCheckResult;
use Wikibase\Repo\RestApi\Domain\Services\PermissionChecker;

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
		} elseif ( $permissionCheckResult->getDenialReason() === PermissionCheckResult::DENIAL_REASON_PAGE_PROTECTED ) {
			throw UseCaseError::newPermissionDenied( UseCaseError::PERMISSION_DENIED_REASON_PAGE_PROTECTED );
		} elseif ( $permissionCheckResult->getDenialReason() === PermissionCheckResult::DENIAL_REASON_USER_BLOCKED ) {
			throw UseCaseError::newPermissionDenied( UseCaseError::PERMISSION_DENIED_REASON_USER_BLOCKED );
		} else {
			throw new UseCaseError(
				UseCaseError::PERMISSION_DENIED_UNKNOWN_REASON,
				'You have no permission to edit this resource'
			);
		}
	}

	public function checkCreateItemPermissions( User $user ): void {
		$permissionCheckResult = $this->permissionChecker->canCreateItem( $user );
		if ( !$permissionCheckResult->isDenied() ) {
			return;
		} elseif ( $permissionCheckResult->getDenialReason() === PermissionCheckResult::DENIAL_REASON_USER_BLOCKED ) {
			throw UseCaseError::newPermissionDenied( UseCaseError::PERMISSION_DENIED_REASON_USER_BLOCKED );
		}

		throw new UseCaseError(
			UseCaseError::PERMISSION_DENIED_UNKNOWN_REASON,
			'You have no permission to create an item'
		);
	}

}
