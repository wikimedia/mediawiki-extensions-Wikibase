<?php

namespace Wikibase;

use User;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Services\Diff\EntityPatcher;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Hooks\EditFilterHookRunner;
use Wikibase\Repo\Store\EntityPermissionChecker;

/**
 * @license GPL-2.0+
 * @author Addshore
 */
class EditEntityFactory {

	/**
	 * @var EntityTitleStoreLookup
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
	 * @var EntityDiffer
	 */
	private $entityDiffer;

	/**
	 * @var EntityPatcher
	 */
	private $entityPatcher;

	/**
	 * @var EditFilterHookRunner
	 */
	private $editFilterHookRunner;

	/**
	 * @param EntityTitleStoreLookup $titleLookup
	 * @param EntityRevisionLookup $entityLookup
	 * @param EntityStore $entityStore
	 * @param EntityPermissionChecker $permissionChecker
	 * @param EntityDiffer $entityDiffer
	 * @param EntityPatcher $entityPatcher
	 * @param EditFilterHookRunner $editFilterHookRunner
	 */
	public function __construct(
		EntityTitleStoreLookup $titleLookup,
		EntityRevisionLookup $entityLookup,
		EntityStore $entityStore,
		EntityPermissionChecker $permissionChecker,
		EntityDiffer $entityDiffer,
		EntityPatcher $entityPatcher,
		EditFilterHookRunner $editFilterHookRunner
	) {
		$this->titleLookup = $titleLookup;
		$this->entityRevisionLookup = $entityLookup;
		$this->entityStore = $entityStore;
		$this->permissionChecker = $permissionChecker;
		$this->entityDiffer = $entityDiffer;
		$this->entityPatcher = $entityPatcher;
		$this->editFilterHookRunner = $editFilterHookRunner;
	}

	/**
	 * @param User $user the user performing the edit
	 * @param EntityId|null $entityId the id of the entity to edit
	 * @param bool|int $baseRevId the base revision ID for conflict checking.
	 *        Use 0 to indicate that the current revision should be used as the base revision,
	 *        effectively disabling conflict detections. true and false will be accepted for
	 *        backwards compatibility, but both will be treated like 0. Note that the behavior
	 *        of EditEntity was changed so that "late" conflicts that arise between edit conflict
	 *        detection and database update are always detected, and result in the update to fail.
	 *
	 * @param bool $allowMasterConnection Can use a master connection or not
	 * @return EditEntity
	 */
	public function newEditEntity(
		User $user,
		EntityId $entityId = null,
		$baseRevId = false,
		$allowMasterConnection = true
	) {
		return new EditEntity(
			$this->titleLookup,
			$this->entityRevisionLookup,
			$this->entityStore,
			$this->permissionChecker,
			$this->entityDiffer,
			$this->entityPatcher,
			$entityId,
			$user,
			$this->editFilterHookRunner,
			$baseRevId,
			$allowMasterConnection
		);
	}

}
