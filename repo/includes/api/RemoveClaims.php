<?php

namespace Wikibase\Api;

use ApiBase;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\Claims;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Entity;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\ChangeOpMainSnak;
use Wikibase\ChangeOp\ChangeOpException;

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
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class RemoveClaims extends ModifyClaim {

	/**
	 * @see \ApiBase::execute
	 *
	 * @since 0.3
	 */
	public function execute() {
		wfProfileIn( __METHOD__ );

		$params = $this->extractRequestParams();
		$entityId = $this->getEntityId( $params );
		$entityTitle = $this->claimModificationHelper->getEntityTitle( $entityId );
		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;
		$entityContent = $this->loadEntityContent( $entityTitle, $baseRevisionId );

		$this->checkClaims( $entityContent->getEntity(), $params['claim'] );
		$summary = $this->claimModificationHelper->createSummary( $params, $this );

		$changeOps = new ChangeOps();
		$changeOps->add( $this->getChangeOps( $params ) );

		try {
			$changeOps->apply( $entityContent->getEntity(), $summary );
		} catch ( ChangeOpException $e ) {
			$this->dieUsage( $e->getMessage(), 'failed-save' );
		}

		$this->saveChanges( $entityContent, $summary );
		$this->getResultBuilder()->markSuccess();
		$this->getResultBuilder()->setList( null, 'claims', $params['claim'], 'claim' );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Validates the parameters and returns the EntityId to act upon on success
	 *
	 * @since 0.4
	 *
	 * @param array $params
	 *
	 * @return EntityId
	 */
	protected function getEntityId( array $params ) {
		$claimGuidParser = WikibaseRepo::getDefaultInstance()->getClaimGuidParser();
		$entityId = null;

		foreach ( $params['claim'] as $guid ) {
			if ( !$this->claimModificationHelper->validateClaimGuid( $guid ) ) {
				$this->dieUsage( "Invalid claim guid $guid" , 'invalid-guid' );
			}

			if ( is_null( $entityId ) ) {
				$entityId = $claimGuidParser->parse( $guid )->getEntityId();
			} else {
				if ( !$claimGuidParser->parse( $guid )->getEntityId()->equals( $entityId ) ) {
					$this->dieUsage( 'All claims must belong to the same entity' , 'invalid-guid' );
				}
			}
		}

		if ( is_null( $entityId ) ) {
			$this->dieUsage( 'Could not find an entity for the claims' , 'invalid-guid' );
		}

		return $entityId ;
	}

	/**
	 * Checks whether the claims can be found
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param array $guids
	 */
	protected function checkClaims( Entity $entity, array $guids ) {
		$claims = new Claims( $entity->getClaims() );

		foreach ( $guids as $guid) {
			if ( !$claims->hasClaimWithGuid( $guid ) ) {
				$this->dieUsage( "Claim with guid $guid not found" , 'invalid-guid' );
			}
		}
	}

	/**
	 * @since 0.4
	 *
	 * @param array $params
	 *
	 * @return ChangeOp[]
	 */
	protected function getChangeOps( array $params ) {
		$changeOps = array();
		$guidGenerator = new ClaimGuidGenerator( $this->getEntityId( $params ) );

		foreach ( $params['claim'] as $guid ) {
			$changeOps[] = new ChangeOpMainSnak( $guid, null, $guidGenerator );
		}

		return $changeOps;
	}

	/**
	 * @see \ApiBase::getAllowedParams
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return array_merge(
			array(
				'claim' => array(
					ApiBase::PARAM_TYPE => 'string',
					ApiBase::PARAM_ISMULTI => true,
					ApiBase::PARAM_REQUIRED => true,
				),
			),
			parent::getAllowedParams()
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
		return array_merge(
			parent::getParamDescription(),
			array(
				'claim' => array( 'One GUID or several (pipe-separated) GUIDs identifying the claims to be removed.',
					'All claims must belong to the same entity.'
				),
			)
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
			'api.php?action=wbremoveclaims&claim=Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0N&token=foobar&baserevid=7201010' => 'Remove claim with GUID of "Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0N"',
		);
	}

}
