<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\DataAccess;

use MediaWiki\User\UserFactory;
use Wikibase\Repo\Domains\Search\Domain\Model\User;
use Wikibase\Repo\Domains\Search\Domain\Services\PermissionChecker;

/**
 * @license GPL-2.0-or-later
 */
class UserFactoryPermissionChecker implements PermissionChecker {

	public function __construct( private UserFactory $userFactory ) {
	}

	public function hasApiHighLimits( User $user ): bool {
		$mwUser = $user->isAnonymous() ?
			$this->userFactory->newAnonymous() :
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable isAnonymous checks for null
			$this->userFactory->newFromName( $user->getUsername() );

		if ( $mwUser === null ) {
			return false;
		}

		return $mwUser->isAllowed( 'apihighlimits' );
	}
}
