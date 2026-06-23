<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Domain\Services;

use Wikibase\Repo\Domains\Search\Domain\Model\User;

/**
 * @license GPL-2.0-or-later
 */
interface PermissionChecker {

	public function hasApiHighLimits( User $user ): bool;

}
