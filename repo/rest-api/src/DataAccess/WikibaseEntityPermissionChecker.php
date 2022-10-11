<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\DataAccess;

use MediaWiki\User\UserFactory;
use Wikibase\DataModel\Entity\ItemId;
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

	public function canEdit( User $user, ItemId $id ): bool {
		$mwUser = $user->isAnonymous() ?
			$this->userFactory->newAnonymous() :
			$this->userFactory->newFromName( $user->getUsername() );

		return $this->entityPermissionChecker->getPermissionStatusForEntityId(
			$mwUser,
			EntityPermissionChecker::ACTION_EDIT,
			$id
		)->isGood();
	}

}
