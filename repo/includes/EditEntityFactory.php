<?php

namespace Wikibase;

use User;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Hooks\EditFilterHookRunner;
use Wikibase\Repo\Store\EntityPermissionChecker;

final class EditEntityFactory {

	private $titleLookup;
	private $entityRevisionLookup;
	private $entityStore;
	private $permissionChecker;
	private $editFilterHookRunner;
	private $context;

	public function __construct(
		EntityTitleLookup $titleLookup,
		EntityRevisionLookup $entityLookup,
		EntityStore $entityStore,
		EntityPermissionChecker $permissionChecker,
		EditFilterHookRunner $editFilterHookRunner,
		$context = null
	) {
		$this->titleLookup = $titleLookup;
		$this->entityRevisionLookup = $entityLookup;
		$this->entityStore = $entityStore;
		$this->permissionChecker = $permissionChecker;
		$this->editFilterHookRunner = $editFilterHookRunner;
		$this->context = $context;
	}

	/**
	 * @param User $user the user performing the edit
	 * @param Entity $newEntity the new entity object
	 * @param int|bool $baseRevId the base revision ID for conflict checking.
	 *        Defaults to false, disabling conflict checks.
	 *        `true` can be used to set the base revision to the latest revision:
	 *        This will detect "late" edit conflicts, i.e. someone squeezing in an edit
	 *        just before the actual database transaction for saving beings.
	 *        The empty string and 0 are both treated as `false`, disabling conflict checks.
	 *
	 * @return EditEntity
	 */
	public function newEditEntity( User $user, Entity $entity, $baseRevId ) {
		return new EditEntity(
			$this->titleLookup,
			$this->entityRevisionLookup,
			$this->entityStore,
			$this->permissionChecker,
			$entity,
			$user,
			$this->editFilterHookRunner,
			$baseRevId,
			$this->context
		);
	}

}