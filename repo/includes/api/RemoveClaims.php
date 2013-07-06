<?php

namespace Wikibase\Api;

use ApiBase;
use MWException;

use Wikibase\Autocomment;
use Wikibase\EntityId;
use Wikibase\Entity;
use Wikibase\EntityContent;
use Wikibase\EntityContentFactory;
use Wikibase\Claims;
use Wikibase\Summary;
use Wikibase\PropertyValueSnak;
use Wikibase\Lib\ClaimGuidValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module for removing claims.
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
 * @since 0.3
 *
 * @ingroup WikibaseRepo
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class RemoveClaims extends ModifyClaim {

	/**
	 * @see ApiBase::isWriteMode
	 * @return bool true
	 */
	public function isWriteMode() {
		return true;
	}

	/**
	 * @see \ApiBase::execute
	 *
	 * @since 0.3
	 */
	public function execute() {
		wfProfileIn( __METHOD__ );

		$guids = $this->getGuidsByEntity();

		$removedClaimKeys = $this->removeClaims(
			$this->getEntityContents( array_keys( $guids ) ),
			$guids
		);

		$this->outputResult( $removedClaimKeys );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Create a summary
	 *
	 * @since 0.4
	 *
	 * @param Claims $claims
	 * @param string[] $guids
	 * @param string $action
	 *
	 * @return Summary
	 */
	protected function createSummary( Claims $claims, array $guids, $action ) {
		if ( !is_string( $action ) ) {
			throw new \MWException( 'action is invalid or unknown type.' );
		}

		$summary = new Summary( $this->getModuleName() );
		$summary->setAction( $action );

		$count = count( $guids );

		$summary->addAutoCommentArgs( $count );

		$summaryArgs = $this->buildSummaryArgs( $claims, $guids );

		if ( $summaryArgs !== array() ) {
			$summary->addAutoSummaryArgs( $summaryArgs );
		}

		return $summary;
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
	 * Parses the key parameter and returns it as an array with as keys
	 * prefixed entity ids and as values arrays with the claim GUIDs for
	 * the specific entity.
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	protected function getGuidsByEntity() {
		$params = $this->extractRequestParams();

		$guids = array();

		$settings = WikibaseRepo::getDefaultInstance()->getSettings();
		$entityPrefixes = $settings->getSetting( 'entityPrefixes' );
		$claimGuidValidator = new ClaimGuidValidator( $entityPrefixes );

		foreach ( $params['claim'] as $guid ) {
			if ( $claimGuidValidator->validateFormat( $guid ) ) {
				$entityId = Entity::getIdFromClaimGuid( $guid );

				if ( !array_key_exists( $entityId, $guids ) ) {
					$guids[$entityId] = array();
				}

				$guids[$entityId][] = $guid;
			} else {
				$this->dieUsage( 'Invalid claim guid', 'removeclaims-invalid-guid' );
			}
		}

		return $guids;
	}

	/**
	 * Does the claim removal and returns a list of claim keys for
	 * the claims that actually got removed.
	 *
	 * @since 0.3
	 i*
	 * @param \Wikibase\EntityContent[] $entityContents
	 * @param array $guids
	 *
	 * @return string[]
	 */
	protected function removeClaims( $entityContents, array $guids ) {
		$removedClaims = new Claims();

		foreach ( $entityContents as $entityContent ) {
			$entity = $entityContent->getEntity();

			$claims = new Claims( $entity->getClaims() );

			$removedBatch = $this->removeClaimsFromList( $claims, $guids[$entity->getPrefixedId()] );
			foreach( $removedBatch as $claim ) {
				$removedClaims->addClaim( $claim );
			}

			$entity->setClaims( $claims );

			$summary = $this->createSummary( $removedClaims, $guids[$entity->getPrefixedId()], 'remove' );

			$this->saveChanges( $entityContent, $summary );
		}

		return $removedClaims->getGuids();
	}

	/**
	 * @since 0.3
	 *
	 * @param string[] $ids
	 *
	 * @return EntityContent[]
	 */
	protected function getEntityContents( array $ids ) {
		$params = $this->extractRequestParams();
		$contents = array();

		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;

		// TODO: use proper batch select
		foreach ( $ids as $id ) {
			$entityId = EntityId::newFromPrefixedId( $id );

			if ( $entityId === null ) {
				$this->dieUsage( 'Invalid entity id provided', 'removeclaims-invalid-entity-id' );
			}

			$entityTitle = EntityContentFactory::singleton()->getTitleForId( $entityId );

			$content = $this->loadEntityContent( $entityTitle, $baseRevisionId );

			if ( $content === null ) {
				$this->dieUsage( "The specified entity does not exist, so it's claims cannot be obtained", 'removeclaims-entity-not-found' );
			}

			$contents[] = $content;
		}

		return $contents;
	}

	/**
	 * @since 0.3
	 *
	 * @param Claims $claims
	 * @param string[] $guids
	 *
	 * @return Claims
	 */
	protected function removeClaimsFromList( Claims &$claims, array $guids ) {
		$removedClaims = new Claims();

		foreach ( $guids as $guid ) {
			if ( $claims->hasClaimWithGuid( $guid ) ) {
				$removedClaims->addClaim( $claims->getClaimWithGuid( $guid ) );
				$claims->removeClaimWithGuid( $guid );
			}
		}

		return $removedClaims;
	}

	/**
	 * @since 0.3
	 *
	 * @param string[] $removedClaimGuids
	 */
	protected function outputResult( $removedClaimGuids ) {
		$this->getResult()->addValue(
			null,
			'success',
			1
		);

		$this->getResult()->setIndexedTagName( $removedClaimGuids, 'claim' );

		$this->getResult()->addValue(
			null,
			'claims',
			$removedClaimGuids
		);
	}

	/**
	 * @since 0.3
	 *
	 * @param EntityContent $content
	 */
	protected function saveChanges( EntityContent $content, Summary $summary ) {
		$status = $this->attemptSaveEntity(
			$content,
			$summary->toString(),
			EDIT_UPDATE
		);

		$this->addRevisionIdFromStatusToResult( 'pageinfo', 'lastrevid', $status );
	}

	/**
	 * @see \ApiBase::getAllowedParams
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return array(
			'claim' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_REQUIRED => true,
			),
			'token' => null,
			'baserevid' => array(
				ApiBase::PARAM_TYPE => 'integer',
			),
			'bot' => null,
		);
	}

	/**
	 * @see \ApiBase::getParamDescription
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function getParamDescription() {
		return array(
			'claim' => 'A GUID identifying the claim',
			'token' => 'An "edittoken" token previously obtained through the token module (prop=info).',
			'baserevid' => array( 'The numeric identifier for the revision to base the modification on.',
				"This is used for detecting conflicts during save."
			),
			'bot' => array( 'Mark this edit as bot',
				'This URL flag will only be respected if the user belongs to the group "bot".'
			),
		);
	}

	/**
	 * @see \ApiBase::getDescription
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getDescription() {
		return array(
			'API module for removing Wikibase claims.'
		);
	}

	/**
	 * @see \ApiBase::getExamples
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	protected function getExamples() {
		return array(
			// TODO
			// 'ex' => 'desc'
		);
	}

}
