<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess;

use MediaWiki\Block\Block;
use MediaWiki\Permissions\PermissionStatus;
use MediaWiki\User\UserFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\Domains\Crud\Domain\Model\User;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\PermissionCheckResult;
use Wikibase\Repo\Domains\Crud\Domain\Services\PermissionChecker;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikimedia\Message\MessageSpecifier;

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

	public function canCreateProperty( User $user ): PermissionCheckResult {
		$mwUser = $user->isAnonymous() ?
			$this->userFactory->newAnonymous() :
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable isAnonymous checks for null
			$this->userFactory->newFromName( $user->getUsername() );

		return $this->newPermissionCheckResultFromStatus(
			$this->entityPermissionChecker->getPermissionStatusForEntity(
				$mwUser,
				EntityPermissionChecker::ACTION_EDIT,
				new Property( null, null, 'string' )
			)
		);
	}

	private function newPermissionCheckResultFromStatus( PermissionStatus $status ): PermissionCheckResult {
		if ( $status->isGood() ) {
			return PermissionCheckResult::newAllowed();
		} elseif ( $this->hasError( 'protectedpagetext', $status ) ) {
			return PermissionCheckResult::newPageProtected();
		}
		return match ( $this->getBlockType( $status ) ) {
			Block::TYPE_USER => PermissionCheckResult::newUserBlocked(),
			Block::TYPE_IP,
			Block::TYPE_RANGE,
			Block::TYPE_AUTO => PermissionCheckResult::newIpBlocked(),
			default => PermissionCheckResult::newDenialForUnknownReason()
		};
	}

	private function hasError( string $error, PermissionStatus $status ): bool {
		return in_array(
			$error,
			array_map(
				fn( MessageSpecifier $message ) => $message->getKey(),
				$status->getMessages()
			)
		);
	}

	private function getBlockType( PermissionStatus $status ): ?int {
		return $status->getBlock()
			?->getTarget()
			->getType();
	}

}
