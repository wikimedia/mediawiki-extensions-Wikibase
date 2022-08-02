<?php

namespace Wikibase\Repo\Interactors;

use IContextSource;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Services\Lookup\EntityRedirectLookupException;
use Wikibase\DataModel\Services\Lookup\EntityRedirectTargetLookup;
use Wikibase\Lib\FormatableSummary;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Summary;
use Wikibase\Repo\EditEntity\EditFilterHookRunner;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\SummaryFormatter;

/**
 * An interactor implementing the use case of creating a redirect.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Addshore
 */
abstract class EntityRedirectCreationInteractor {

	/**
	 * @var EntityTitleStoreLookup
	 */
	private $entityTitleLookup;

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
	 * @var SummaryFormatter
	 */
	private $summaryFormatter;

	/**
	 * @var EditFilterHookRunner
	 */
	private $editFilterHookRunner;

	/**
	 * @var EntityRedirectTargetLookup
	 */
	private $entityRedirectLookup;

	public function __construct(
		EntityRevisionLookup $entityRevisionLookup,
		EntityStore $entityStore,
		EntityPermissionChecker $permissionChecker,
		SummaryFormatter $summaryFormatter,
		EditFilterHookRunner $editFilterHookRunner,
		EntityRedirectTargetLookup $entityRedirectLookup,
		EntityTitleStoreLookup $entityTitleLookup
	) {
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->entityStore = $entityStore;
		$this->permissionChecker = $permissionChecker;
		$this->summaryFormatter = $summaryFormatter;
		$this->editFilterHookRunner = $editFilterHookRunner;
		$this->entityRedirectLookup = $entityRedirectLookup;
		$this->entityTitleLookup = $entityTitleLookup;
	}

	/**
	 * Create a redirect at $fromId pointing to $toId.
	 *
	 * @param EntityId $fromId The ID of the entity to be replaced by the redirect. The entity
	 * must exist and be empty (or be a redirect already).
	 * @param EntityId $toId The ID of the entity the redirect should point to. The Entity must
	 * exist and must not be a redirect.
	 * @param bool $bot Whether the edit should be marked as bot
	 * @param string[] $tags
	 * @param IContextSource|null $context The context to pass to the edit filter hook and check permissions
	 *
	 * @return EntityRedirect
	 * @throws RedirectCreationException If creating the redirect fails. Calling code may use
	 * RedirectCreationException::getErrorCode() to get further information about the cause of
	 * the failure. An explanation of the error codes can be obtained from getErrorCodeInfo().
	 * @suppress PhanTypeMismatchDeclaredParam
	 */
	public function createRedirect(
		EntityId $fromId,
		EntityId $toId,
		bool $bot,
		array $tags,
		IContextSource $context
	): EntityRedirect {
		$this->checkCompatible( $fromId, $toId );
		$this->checkPermissions( $fromId, $context );

		$this->checkExistsNoRedirect( $toId );
		$this->checkCanCreateRedirect( $fromId );
		$this->checkSourceAndTargetNotTheSame( $fromId, $toId );

		$summary = new Summary( 'wbcreateredirect' );
		$summary->addAutoCommentArgs( $fromId->getSerialization(), $toId->getSerialization() );

		$redirect = new EntityRedirect( $fromId, $toId );
		$this->saveRedirect( $redirect, $summary, $context, $bot, $tags );

		return $redirect;
	}

	/**
	 * Check user's permissions for the given entity ID.
	 *
	 * @param EntityId $entityId
	 * @param IContextSource $context
	 * @throws RedirectCreationException if the permission check fails
	 */
	private function checkPermissions( EntityId $entityId, IContextSource $context ) {
		$status = $this->permissionChecker->getPermissionStatusForEntityId(
			$context->getUser(),
			EntityPermissionChecker::ACTION_REDIRECT,
			$entityId
		);

		if ( !$status->isOK() ) {
			// XXX: This is silly, we really want to pass the Status object to the API error handler.
			// Perhaps we should get rid of RedirectCreationException and use Status throughout.
			throw new RedirectCreationException( $status->getWikiText(), 'permissiondenied' );
		}
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws RedirectCreationException
	 */
	private function checkCanCreateRedirect( EntityId $entityId ) {
		try {
			$revision = $this->entityRevisionLookup->getEntityRevision(
				$entityId,
				0,
				 LookupConstants::LATEST_FROM_MASTER
			);

			if ( !$revision ) {
				$title = $this->entityTitleLookup->getTitleForId( $entityId );
				if ( !$title || !$title->isDeleted() ) {
					throw new RedirectCreationException(
						"Couldn't get Title for $entityId or Title is not deleted",
						'no-such-entity',
						[ $entityId->serialize() ]
					);
				}
			} else {
				$this->assertEntityIsRedirectable( $revision->getEntity() );
			}

		} catch ( RevisionedUnresolvedRedirectException $ex ) {
			// Nothing to do. It's ok to override a redirect with a redirect.
		} catch ( StorageException $ex ) {
			throw new RedirectCreationException( $ex->getMessage(), 'cant-load-entity-content', [], $ex );
		}
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws RedirectCreationException
	 */
	private function checkExistsNoRedirect( EntityId $entityId ) {
		try {
			$redirect = $this->entityRedirectLookup->getRedirectForEntityId(
				$entityId,
				EntityRedirectTargetLookup::FOR_UPDATE
			);
		} catch ( EntityRedirectLookupException $ex ) {
			throw new RedirectCreationException(
				$ex->getMessage(),
				'no-such-entity',
				[ $entityId->serialize() ],
				$ex
			);
		}

		if ( $redirect !== null ) {
			throw new RedirectCreationException(
				"Entity $entityId is a redirect",
				'target-is-redirect',
				[ $entityId->serialize() ]
			);
		}
	}

	/**
	 * @param EntityId $fromId
	 * @param EntityId $toId
	 *
	 * @throws RedirectCreationException
	 */
	private function checkCompatible( EntityId $fromId, EntityId $toId ) {
		if ( $fromId->getEntityType() !== $toId->getEntityType() ) {
			throw new RedirectCreationException(
				'Incompatible entity types',
				'target-is-incompatible'
			);
		}
	}

	private function checkSourceAndTargetNotTheSame( EntityId $fromId, EntityId $toId ) {
		if ( $fromId->getSerialization() === $toId->getSerialization() ) {
			throw new RedirectCreationException(
				'Cannot redirect an entity to itself.',
				'source-and-target-are-the-same'
			);
		}
	}

	/**
	 * @throws RedirectCreationException
	 */
	private function saveRedirect(
		EntityRedirect $redirect,
		FormatableSummary $summary,
		IContextSource $context,
		bool $bot,
		array $tags
	): void {
		$summary = $this->summaryFormatter->formatSummary( $summary );
		$flags = 0;
		if ( $bot ) {
			$flags |= EDIT_FORCE_BOT;
		}
		$title = $this->entityTitleLookup->getTitleForId( $redirect->getEntityId() );

		if ( !$title->exists() && $title->isDeletedQuick() ) {
			// Allow creating new pages as redirects, but only if they existed before.
			$flags |= EDIT_NEW;
		} else {
			$flags |= EDIT_UPDATE;
		}

		$hookStatus = $this->editFilterHookRunner->run( $redirect, $context, $summary );
		if ( !$hookStatus->isOK() ) {
			throw new RedirectCreationException(
				'EditFilterHook stopped redirect creation',
				'cant-redirect-due-to-edit-filter-hook'
			);
		}

		try {
			$this->entityStore->saveRedirect(
				$redirect,
				$summary,
				$context->getUser(),
				$flags,
				false,
				$tags
			);
		} catch ( StorageException $ex ) {
			throw new RedirectCreationException( $ex->getMessage(), 'cant-redirect', [], $ex );
		}
	}

	/**
	 * Used to assert that the source entity is redirectable. This can differ depending on the entity type.
	 *
	 * @param EntityDocument $entity
	 *
	 * @return void
	 *
	 * @throws RedirectCreationException
	 */
	abstract protected function assertEntityIsRedirectable( EntityDocument $entity );

}
