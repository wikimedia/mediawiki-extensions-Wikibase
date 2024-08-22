<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure\DataAccess;

use MediaWiki\Status\Status;
use MediaWiki\User\UserFactory;
use MessageSpecifier;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\ReadModel\PermissionCheckResult;
use Wikibase\Repo\RestApi\Domain\Services\PermissionChecker;
use Wikibase\Repo\Store\EntityPermissionChecker;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseEntityPermissionChecker implements PermissionChecker {

	private EntityPermissionChecker $entityPermissionChecker;
	private UserFactory $userFactory;

	public function __construct( EntityPermissionChecker $entityPermissionChecker, UserFactory $userFactory ) {
		$this->entityPermissionChecker = $entityPermissionChecker;
		$this->userFactory = $userFactory;
	}

	public function canEdit( User $user, EntityId $id ): PermissionCheckResult {
		$mwUser = $user->isAnonymous() ?
			$this->userFactory->newAnonymous() :
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable isAnonymous checks for null
			$this->userFactory->newFromName( $user->getUsername() );

		return $this->newPermissionCheckResultFromStatus(
			$this->entityPermissionChecker->getPermissionStatusForEntityId(
				$mwUser,
				EntityPermissionChecker::ACTION_EDIT,
				$id
			)
		);
	}

	public function canCreateItem( User $user ): PermissionCheckResult {
		$mwUser = $user->isAnonymous() ?
			$this->userFactory->newAnonymous() :
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable isAnonymous checks for null
			$this->userFactory->newFromName( $user->getUsername() );

		return $this->newPermissionCheckResultFromStatus(
			$this->entityPermissionChecker->getPermissionStatusForEntity(
				$mwUser,
				EntityPermissionChecker::ACTION_EDIT,
				new Item()
			)
		);
	}

	private function newPermissionCheckResultFromStatus( Status $status ): PermissionCheckResult {
		if ( $status->isGood() ) {
			return PermissionCheckResult::newAllowed();
		} elseif ( $this->hasError( 'protectedpagetext', $status ) ) {
			return PermissionCheckResult::newPageProtected();
		} elseif ( $this->hasError( 'blockedtext', $status ) ) {
			return PermissionCheckResult::newUserBlocked();
		}

		return PermissionCheckResult::newDenialForUnknownReason();
	}

	private function hasError( string $error, Status $status ): bool {
		return in_array(
			$error,
			array_map(
				fn( MessageSpecifier $message ) => $message->getKey(),
				$status->getMessages()
			)
		);
	}

}
