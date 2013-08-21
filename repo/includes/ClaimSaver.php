<?php

namespace Wikibase;

use User;
use MWException;
use Status;
use ValueParsers\ParseException;
use Wikibase\Claims;
use Wikibase\ExceptionWithCode;
use Wikibase\Repo\WikibaseRepo;

/**
 * Class for updating a claim in the primary storage.
 *
 * TODO: add dedicated tests (now tested though SetClaim API module)
 * FIXME: entity content fetching pulls in global factory
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.4
 *
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ClaimSaver {
	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.4
	 *
	 * @param Claim $claim
	 * @param int|null $baseRevId
	 * @param string $token
	 * @param User $user
	 * @param ClaimSummaryBuilder|null $claimSummaryBuilder
	 * @param int $flags
	 *
	 * @return Status The status. The status value is an array which may contain
	 *         the following fields:
	 *
	 *         -revision: the revision object created by the save
	 *         -errorFlags: error flags using the EditEntity::XXX_ERROR constants
	 *         -errorCode: error code to use in API output
	 *
	 *         This status object can be used with ApiWikibase::handleSaveStatus().
	 */
	public function saveClaim( Claim $claim, $baseRevId, $token, User $user,
		ClaimSummaryBuilder $claimSummaryBuilder = null, $flags = 0 ) {

		try {
			$entityId = $this->getEntityIdForClaim( $claim );

			$content = $this->getEntityContent( $entityId, $baseRevId );

			$summary = null;

			if ( $claimSummaryBuilder !== null ) {
				$summary = $claimSummaryBuilder->buildClaimSummary(
						new Claims( $content->getEntity()->getClaims() ),
						$claim
				);
			}

			$this->updateClaim( $content->getEntity(), $claim );

			$status = $this->saveChanges( $content, $baseRevId, $token, $user, $summary, $flags );
		} catch ( ExceptionWithCode $ex ) {
			// put the error code into the status
			$value = array( 'errorCode' => $ex->getErrorCode() );
			$status = Status::newGood();
			$status->setResult( false, $value );
			//TODO: add an error message localization key, perhaps derived from the error code.
		}

		return $status;
	}

	/**
	 * @param Claim $claim
	 *
	 * @return EntityId
	 * @throws ExceptionWithCode
	 */
	protected function getEntityIdForClaim( Claim $claim ) {
		$guid = $claim->getGuid();

		if ( $guid === null ) {
			throw new ExceptionWithCode( 'The ID of the claim needs to be set', 'setclaim-no-guid' );
		}

		try {
			$entityId = Entity::getIdFromClaimGuid( $guid );
		}
		catch ( MWException $exception ) {
			throw new ExceptionWithCode( $exception->getMessage(), 'setclaim-invalid-guid' );
		}

		$idParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();

		try {
			$entityId = $idParser->parse( $entityId );
		}
		catch ( ParseException $parseException ) {
			throw new ExceptionWithCode( $parseException->getMessage(), 'setclaim-invalid-guid' );
		}

		assert( $entityId instanceof EntityId );
		return $entityId;
	}

	/**
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param Claim $claim
	 *
	 */
	protected function updateClaim( Entity $entity, Claim $claim ) {
		$claims = new Claims( $entity->getClaims() );

		if ( $claims->hasClaimWithGuid( $claim->getGuid() ) ) {
			$claims->removeClaimWithGuid( $claim->getGuid() );
		}

		$claims->addClaim( $claim );
		$entity->setClaims( $claims );
	}

	/**
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 * @param int|null $revisionId
	 *
	 * @return EntityContent
	 * @throws ExceptionWithCode
	 */
	protected function getEntityContent( EntityId $entityId, $revisionId ) {
		if ( $revisionId === null ) {
			$content = EntityContentFactory::singleton()->getFromId( $entityId );
		}
		else {
			$content = EntityContentFactory::singleton()->getFromRevision( $revisionId );
		}

		if ( $content === null ) {
			throw new ExceptionWithCode( 'No such entity', 'setclaim-entity-not-found' );
		}

		if ( !$content->getEntity()->getId()->equals( $entityId ) ) {
			throw new ExceptionWithCode(
				'The provided revision belongs to the wrong entity',
				'setclaim-revision-wrong-entity'
			);
		}

		return $content;
	}

	/**
	 * @since 0.4
	 *
	 * @param EntityContent $content
	 * @param int|null $baseRevisionId
	 * @param string $token
	 * @param User $user
	 * @param Summary|null $summary
	 * @param int $flags
	 *
	 * @return Status
	 */
	protected function saveChanges( EntityContent $content, $baseRevisionId, $token, User $user,
		Summary $summary, $flags ) {

		$baseRevisionId = is_int( $baseRevisionId ) && $baseRevisionId > 0 ? $baseRevisionId : false;
		$editEntity = new \Wikibase\EditEntity( $content, $user, $baseRevisionId );

		$flags = $flags !== 0 ? $flags | EDIT_UPDATE : EDIT_UPDATE;

		$status = $editEntity->attemptSave(
			$summary !== null ? $summary->toString() : '',
			$flags,
			$token
		);

		return $status;
	}

}
