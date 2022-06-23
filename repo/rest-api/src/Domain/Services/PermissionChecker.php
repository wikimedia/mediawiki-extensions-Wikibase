<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Model\User;

/**
 * @license GPL-2.0-or-later
 */
interface PermissionChecker {

	public function canEdit( User $user, ItemId $id ): bool;

}
