<?php

namespace Wikibase;

use User;
use MWException;
use Status;
use ValueParsers\ParseException;
use Wikibase\ExceptionWithCode;

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
 */
class ClaimSaver {

	/**
	 * @var Summary
	 */
	protected $summary;

	/**
	 * Constructs a new ClaimSaver
	 *
	 * @since 0.4
	 *
	 * @param Summary|null $summary
	 */
	public function __construct( $summary = null ) {
		$this->summary = $summary;
	}

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.4
	 *
	 * @param Claim $claim
	 * @param int|null $baseRevId
	 * @param string $token
	 * @param User $user
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
	public function saveClaim( Claim $claim, ClaimDiffer $claimDiffer, $baseRevId, $token, User $user ) {
		try {
			$entityId = $this->getEntityIdForClaim( $claim );

			$content = $this->getEntityContent( $entityId, $baseRevId );

			$this->updateClaim( $content->getEntity(), $claim, $claimDiffer );

			$status = $this->saveChanges( $content, $baseRevId, $token, $user );
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

		$libRegistry = new \Wikibase\LibRegistry( \Wikibase\Settings::singleton() );
		$idParser = $libRegistry->getEntityIdParser();

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
	 * @param ClaimDiffer $claimDiffer
	 */
	protected function updateClaim( Entity $entity, Claim $claim, ClaimDiffer $claimDiffer ) {
		$claims = new \Wikibase\Claims( $entity->getClaims() );
		$this->summary->addAutoCommentArgs( 1 ); // we're always having singular here

		if ( $claims->hasClaimWithGuid( $claim->getGuid() ) ) {
			//claim is changed
			$oldClaim = $claims->getClaimWithGuid( $claim->getGuid() );
			$claimDifference = $claimDiffer->diffClaims( $oldClaim, $claim );

			$this->summary->setAction( 'update' );

			if ( $claimDifference->getMainSnakChange() !== null ) {
				$summaryArgs = $this->buildSummaryArgs( new \Wikibase\Claims( array( $claim ) ), array( $claim->getGuid() ) );
				$this->summary->addAutoSummaryArgs( $summaryArgs );
			}

			if ( $claimDifference->getQualifierChanges()->isEmpty() === false ) {
				$pair = array();
				$pair[$claim->getMainSnak()->getPropertyId()->getPrefixedId()][] = '/* wikibase-item-summary-wbsetqualifier-update */'; #"Modified qualifiers";
				$summaryArgs = array( $pair );
				$this->summary->addAutoSummaryArgs( $summaryArgs );
			}

			$claims->removeClaimWithGuid( $claim->getGuid() );
		} else {
			//new claim is added
			$summaryArgs = $this->buildSummaryArgs( new \Wikibase\Claims( array( $claim ) ), array( $claim->getGuid() ) );
			$this->summary->addAutoSummaryArgs( $summaryArgs );
			$this->summary->setAction( 'create' );
		}

		$claims->addClaim( $claim );

		$entity->setClaims( $claims );
	}
	
	/**
	 * Build key (property) => value pairs for summary arguments
	 *
	 * @todo see if this can be more generic and put elsewhere...
	 *
	 * @param Claims $claims
	 * @param string[] $guids
	 *
	 * @return mixed[] // propertyId (prefixed) => array of values
	 */
	protected function buildSummaryArgs( Claims $claims, array $guids ) {
		$pairs = array();

		foreach( $guids as $guid ) {
			if ( $claims->hasClaimWithGuid( $guid ) ) {
				$snak = $claims->getClaimWithGuid( $guid )->getMainSnak();
				$key = $snak->getPropertyId()->getPrefixedId();

				if ( !array_key_exists( $key, $pairs ) ) {
					$pairs[$key] = array();
				}

				if ( $snak instanceof PropertyValueSnak ) {
					$value = $snak->getDataValue();
				} else {
					$value = '-'; // todo handle no values in general way (needed elsewhere)
				}

				$pairs[$key][] = $value;
			}
		}

		return array( $pairs );
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
	 *
	 * @return Status
	 */
	protected function saveChanges( EntityContent $content, $baseRevisionId, $token, User $user ) {
		$baseRevisionId = is_int( $baseRevisionId ) && $baseRevisionId > 0 ? $baseRevisionId : false;
		$editEntity = new \Wikibase\EditEntity( $content, $user, $baseRevisionId );

		$status = $editEntity->attemptSave(
			$this->summary ? $this->summary->toString() : '',
			EDIT_UPDATE,
			$token
		);

		return $status;
	}

}