<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Services\PermissionChecker;

/**
 * @license GPL-2.0-or-later
 */
class AssertUserIsAuthorized {

	private PermissionChecker $permissionChecker;

	public function __construct( PermissionChecker $permissionChecker ) {
		$this->permissionChecker = $permissionChecker;
	}

	public function execute( EntityId $id, ?string $username ): void {
		$user = $username !== null ? User::withUsername( $username ) : User::newAnonymous();
		if ( !$this->permissionChecker->canEdit( $user, $id ) ) {
			throw new UseCaseError(
				UseCaseError::PERMISSION_DENIED,
				'You have no permission to edit this resource'
			);
		}
	}

}
