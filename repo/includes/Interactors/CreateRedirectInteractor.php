<?php

namespace Wikibase\Repo\Interactors;

use User;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\UnresolvedRedirectException;
use Wikibase\StorageException;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * An interactor implementing the use case of creating a redirect.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class CreateRedirectInteractor {

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var EntityStore
	 */
	private $entityStore;

	/**
	 * @var SummaryFormatter
	 */
	private $summaryFormatter;

	/**
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param EntityStore $entityStore
	 * @param SummaryFormatter $summaryFormatter
	 */
	public function __construct(
		EntityRevisionLookup $entityRevisionLookup,
		EntityStore $entityStore,
		SummaryFormatter $summaryFormatter
	) {
		$this->entityRevisionLookup =$entityRevisionLookup;
		$this->entityStore = $entityStore;
		$this->summaryFormatter = $summaryFormatter;
	}

	/**
	 * Create a redirect at $fromId pointing to $toId.
	 *
	 * @param EntityId $fromId The ID of the entity to be replaced by the redirect. The entity
	 * must exist and be empty (or be a redirect already).
	 * @param EntityId $toId The ID of the entity the redirect should point to. The Entity must
	 * exist and must not be a redirect.
	 * @param Summary $summary The summary to use when storing the new redirect. $fromId and $to
	 * will automatically be added as parameters.
	 * @param User $user The user performing the edit.
	 *
	 * @return EntityRedirect
	 *
	 * @throws CreateRedirectException If creating the redirect fails. Calling code may use
	 * CreateRedirectException::getErrorCode() to get further information about the cause of
	 * the failure. An explanation of the error codes can be obtained from getErrorCodeInfo().
	 */
	public function createRedirect( EntityId $fromId, EntityId $toId, Summary $summary, User $user ) {
		wfProfileIn( __METHOD__ );

		$this->checkCompatible( $fromId, $toId );

		$this->checkExists( $toId );
		$this->checkEmpty( $fromId );

		$redirect = new EntityRedirect( $fromId, $toId );
		$this->saveRedirect( $redirect, $summary, $user );

		wfProfileOut( __METHOD__ );

		return $redirect;
	}

	/**
	 * @param EntityId $id
	 *
	 * @throws CreateRedirectException
	 */
	private function checkEmpty( EntityId $id ) {
		try {
			$revision = $this->entityRevisionLookup->getEntityRevision( $id );

			if ( !$revision ) {
				throw new CreateRedirectException(
					'Entity ' . $id->getSerialization() . ' not found',
					'no-such-entity' );
			}

			$entity = $revision->getEntity();

			if ( !$entity->isEmpty() ) {
				throw new CreateRedirectException(
					'Entity ' . $id->getSerialization() . ' is not empty',
					'not-empty' );
			}
		} catch ( UnresolvedRedirectException $ex ) {
			// Nothing to do. It's ok to override a redirect with a redirect.
		} catch ( StorageException $ex ) {
			throw new CreateRedirectException( $ex->getMessage(), 'cant-load-entity-content', $ex );
		}
	}

	/**
	 * @param EntityId $id
	 *
	 * @throws CreateRedirectException
	 */
	private function checkExists( EntityId $id ) {
		try {
			$revision = $this->entityRevisionLookup->getLatestRevisionId( $id );

			if ( !$revision ) {
				throw new CreateRedirectException(
					'Entity ' . $id->getSerialization() . ' not found',
					'no-such-entity' );
			}
		} catch ( UnresolvedRedirectException $ex ) {
			throw new CreateRedirectException(
				$ex->getMessage(),
				'target-is-redirect',
				$ex );
		}
	}

	/**
	 * @param EntityId $fromId
	 * @param EntityId $toId
	 *
	 * @throws CreateRedirectException
	 */
	private function checkCompatible( EntityId $fromId, EntityId $toId ) {
		if ( $fromId->getEntityType() !== $toId->getEntityType() ) {
			throw new CreateRedirectException(
				'Incompatible entity types',
				'target-is-incompatible' );
		}
	}

	/**
	 * @param EntityRedirect $redirect
	 * @param Summary $summary
	 * @param User $user
	 *
	 * @throws CreateRedirectException
	 */
	private function saveRedirect( EntityRedirect $redirect, Summary $summary, User $user ) {
		$summary->addAutoSummaryArgs( $redirect->getEntityId(), $redirect->getTargetId() );

		try {
			$this->entityStore->saveRedirect(
				$redirect,
				$this->summaryFormatter->formatSummary( $summary ),
				$user,
				EDIT_UPDATE
			);
		} catch ( StorageException $ex ) {
			throw new CreateRedirectException( $ex->getMessage(), 'cant-redirect', $ex );
		}
	}

	/**
	 * Returns information about the error codes used with CreateRedirectException by this class.
	 *
	 * @return string[] a map of error codes as returned by CreateRedirectException::getErrorCode()
	 * to a human readable explanation (in English).
	 *
	 * @see CreateRedirectException::getErrorCode()
	 * @see ApiMain::getPossibleErrors
	 */
	public function getErrorCodeInfo() {
		return array(
			'invalid-entity-id'=> 'Invalid entity ID',
			'not-empty'=> 'The entity that is to be turned into a redirect is not empty',
			'no-such-entity'=> 'Entity not found',
			'target-is-redirect'=> 'The redirect target is itself a redirect',
			'target-is-incompatible'=> 'The redirect target is incompatible (e.g. a different type of entity)',
			'cant-redirect'=> 'Can\'t create the redirect (e.g. the given type of entity does not support redirects)',
		);
	}

}
