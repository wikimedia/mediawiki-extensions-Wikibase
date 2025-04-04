<?php

namespace Wikibase\Repo\EditEntity;

use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use MediaWiki\Context\IContextSource;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\TempUser\TempUserCreator;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Services\Diff\EntityPatcher;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;

/**
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class MediaWikiEditEntityFactory {

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
	 * @var StatsdDataFactoryInterface
	 */
	private $stats;

	/**
	 * @var UserOptionsLookup
	 */
	private $userOptionsLookup;

	/**
	 * @var TempUserCreator
	 */
	private $tempUserCreator;

	/**
	 * @var int
	 */
	private $maxSerializedEntitySize;

	/** @var string[] */
	private $localEntityTypes;

	public function __construct(
		EntityTitleStoreLookup $titleLookup,
		EntityRevisionLookup $entityLookup,
		EntityStore $entityStore,
		EntityPermissionChecker $permissionChecker,
		EntityDiffer $entityDiffer,
		EntityPatcher $entityPatcher,
		EditFilterHookRunner $editFilterHookRunner,
		StatsdDataFactoryInterface $statsdDataFactory,
		UserOptionsLookup $userOptionsLookup,
		TempUserCreator $tempUserCreator,
		int $maxSerializedEntitySize,
		array $localEntityTypes
	) {
		$this->titleLookup = $titleLookup;
		$this->entityRevisionLookup = $entityLookup;
		$this->entityStore = $entityStore;
		$this->permissionChecker = $permissionChecker;
		$this->entityDiffer = $entityDiffer;
		$this->entityPatcher = $entityPatcher;
		$this->editFilterHookRunner = $editFilterHookRunner;
		$this->stats = $statsdDataFactory;
		$this->userOptionsLookup = $userOptionsLookup;
		$this->tempUserCreator = $tempUserCreator;
		$this->maxSerializedEntitySize = $maxSerializedEntitySize;
		$this->localEntityTypes = $localEntityTypes;
	}

	/**
	 * @param IContextSource $context The request context for the edit.
	 * @param EntityId|null $entityId the id of the entity to edit
	 * @param int $baseRevId the base revision ID for conflict checking.
	 *        Use 0 to indicate that the current revision should be used as the base revision,
	 *        effectively disabling conflict detections. Note that the behavior
	 *        of EditEntity was changed so that "late" conflicts that arise between edit conflict
	 *        detection and database update are always detected, and result in the update to fail.
	 * @param bool $allowMasterConnection Can use a master connection or not
	 *
	 * @return EditEntity
	 */
	public function newEditEntity(
		IContextSource $context,
		?EntityId $entityId = null,
		int $baseRevId = 0,
		$allowMasterConnection = true
	) {
		$statsTimingPrefix = "wikibase.repo.EditEntity.timing";
		return new StatsdSaveTimeRecordingEditEntity(
			new MediaWikiEditEntity( $this->titleLookup,
				$this->entityRevisionLookup,
				new StatsdSaveTimeRecordingEntityStore(
					$this->entityStore,
					$this->stats,
					$statsTimingPrefix . '.EntityStore'
				),
				$this->permissionChecker,
				$this->entityDiffer,
				$this->entityPatcher,
				$entityId,
				$context,
				new StatsdTimeRecordingEditFilterHookRunner(
					$this->editFilterHookRunner,
					$this->stats,
					$statsTimingPrefix . '.EditFilterHookRunner'
				),
				$this->userOptionsLookup,
				$this->tempUserCreator,
				$this->maxSerializedEntitySize,
				$this->localEntityTypes,
				$baseRevId,
				$allowMasterConnection
			),
			$this->stats,
			$statsTimingPrefix . '.EditEntity'
		);
	}

}
