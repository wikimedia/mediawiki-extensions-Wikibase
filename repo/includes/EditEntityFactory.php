<?php

namespace Wikibase;

use IContextSource;
use User;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Hooks\EditFilterHookRunner;
use Wikibase\Repo\Store\EntityPermissionChecker;

/**
 * @since 0.5
 *
 * @license GPLv2+
 * @author Addshore
 */
class EditEntityFactory {

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var EntityStore
	 */
	private $entityStore;

	/**
	 * @var EntityPermissionChecker
	 */
	private $permissionChecker;

	/**
	 * @var EditFilterHookRunner
	 */
	private $editFilterHookRunner;

	/**
	 * @var IContextSource|null
	 */
	private $context;

	/**
	 * @param EntityTitleLookup $titleLookup
	 * @param EntityRevisionLookup $entityLookup
	 * @param EntityStore $entityStore
	 * @param EntityPermissionChecker $permissionChecker
	 * @param EditFilterHookRunner $editFilterHookRunner
	 * @param IContextSource|null $context
	 */
	public function __construct(
		EntityTitleLookup $titleLookup,
		EntityRevisionLookup $entityLookup,
		EntityStore $entityStore,
		EntityPermissionChecker $permissionChecker,
		EditFilterHookRunner $editFilterHookRunner,
		IContextSource $context = null
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
	 * @param Entity $entity the new entity object
	 * @param int|bool $baseRevId the base revision ID for conflict checking.
	 *        Defaults to false, disabling conflict checks.
	 *        `true` can be used to set the base revision to the latest revision:
	 *        This will detect "late" edit conflicts, i.e. someone squeezing in an edit
	 *        just before the actual database transaction for saving beings.
	 *        The empty string and 0 are both treated as `false`, disabling conflict checks.
	 *
	 * @return EditEntity
	 */
	public function newEditEntity( User $user, Entity $entity, $baseRevId = false ) {
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
