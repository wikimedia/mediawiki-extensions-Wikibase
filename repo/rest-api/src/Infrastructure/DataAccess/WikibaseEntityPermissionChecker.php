<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure\DataAccess;

use MediaWiki\User\UserFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\RestApi\Domain\Model\User;
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

	public function canEdit( User $user, EntityId $id ): bool {
		$mwUser = $user->isAnonymous() ?
			$this->userFactory->newAnonymous() :
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable isAnonymous checks for null
			$this->userFactory->newFromName( $user->getUsername() );

		return $this->entityPermissionChecker->getPermissionStatusForEntityId(
			$mwUser,
			EntityPermissionChecker::ACTION_EDIT,
			$id
		)->isGood();
	}

	public function canCreateItem( User $user ): bool {
		$mwUser = $user->isAnonymous() ?
			$this->userFactory->newAnonymous() :
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable isAnonymous checks for null
			$this->userFactory->newFromName( $user->getUsername() );

		return $this->entityPermissionChecker->getPermissionStatusForEntity(
			$mwUser,
			EntityPermissionChecker::ACTION_EDIT,
			new Item()
		)->isGood();
	}

}
