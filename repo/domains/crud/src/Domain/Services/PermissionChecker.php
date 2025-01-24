<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Domain\Services;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\Domains\Crud\Domain\Model\User;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\PermissionCheckResult;

/**
 * @license GPL-2.0-or-later
 */
interface PermissionChecker {

	public function canCreateItem( User $user ): PermissionCheckResult;

	public function canCreateProperty( User $user ): PermissionCheckResult;

	public function canEdit( User $user, EntityId $id ): PermissionCheckResult;

}
