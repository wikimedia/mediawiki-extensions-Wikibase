<?php

namespace Wikibase\Api;

use ApiBase;
use Wikibase\Entity;
use Wikibase\Statement;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\ChangeOpReference;
use Wikibase\ChangeOps;
use Wikibase\ChangeOpException;

/**
 * API module for removing one or more references of the same statement.
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
class RemoveReferences extends ModifyClaim {

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.3
	 */
	public function execute() {
		wfProfileIn( __METHOD__ );

		$params = $this->extractRequestParams();
		$this->validateParameters( $params );

		$claimGuid = $params['statement'];
		$entityId = $this->entityModificationHelper->getEntityIdFromString(
			Entity::getIdFromClaimGuid( $claimGuid )
		);
		$entityTitle = $this->entityModificationHelper->getEntityTitleFromEntityId( $entityId );
		$entityContent = $this->entityModificationHelper->getEntityContent( $entityTitle );
		$entity = $entityContent->getEntity();
		$summary = $this->claimModificationHelper->createSummary( $params, $this );

		$claim = $this->claimModificationHelper->getClaimFromEntity( $claimGuid, $entity );

		if ( ! ( $claim instanceof Statement ) ) {
			$this->dieUsage( 'The referenced claim is not a statement and thus cannot have references', 'not-statement' );
		}

		$referenceHashes = $this->getReferenceHashesFromParams( $params, $claim );

		$changeOps = new ChangeOps();
		$changeOps->add( $this->getChangeOps( $claimGuid, $referenceHashes ) );

		try {
			$changeOps->apply( $entity, $summary );
		} catch ( ChangeOpException $e ) {
			$this->dieUsage( $e->getMessage(), 'failed-save' );
		}

		$this->saveChanges( $entityContent, $summary );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Check the provided parameters
	 *
	 * @since 0.4
	 */
	protected function validateParameters( array $params ) {
		if ( !( $this->claimModificationHelper->validateClaimGuid( $params['statement'] ) ) ) {
			$this->dieUsage( 'Invalid claim guid' , 'invalid-guid' );
		}
	}

	/**
	 * @since 0.4
	 *
	 * @param string $claimGuid
	 * @param array $referenceHashes
	 *
	 * @return ChangeOp[] $changeOps
	 */
	protected function getChangeOps( $claimGuid, array $referenceHashes ) {
		$changeOps = array();
		$idFormatter = WikibaseRepo::getDefaultInstance()->getIdFormatter();

		foreach ( $referenceHashes as $referenceHash ) {
			$changeOps[] = new ChangeOpReference( $claimGuid, null, $referenceHash, $idFormatter );
		}

		return $changeOps;
	}

	/**
	 * @since 0.4
	 *
	 * @param array $params
	 * @param Statement $statement
	 *
	 * @return string[]
	 */
	protected function getReferenceHashesFromParams( array $params, Statement $statement ) {
		$references = $statement->getReferences();
		$hashes = array();
	
		foreach ( array_unique( $params['references'] ) as $referenceHash ) {
			if ( !$references->hasReferenceHash( $referenceHash ) ) {
				$this->dieUsage( 'Invalid reference hash', 'no-such-reference' );
			}
			$hashes[] = $referenceHash;
		}
	
		return $hashes;
	}

	/**
	 * @see ApiBase::getAllowedParams
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return array_merge(
			parent::getAllowedParams(),
			array(
				'statement' => array(
					ApiBase::PARAM_TYPE => 'string',
					ApiBase::PARAM_REQUIRED => true,
				),
				'references' => array(
					ApiBase::PARAM_TYPE => 'string',
					ApiBase::PARAM_REQUIRED => true,
					ApiBase::PARAM_ISMULTI => true,
				),
			)
		);
	}

	/**
	 * @see ApiBase::getParamDescription
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function getParamDescription() {
		return array_merge(
			parent::getParamDescription(),
			array(
				'statement' => 'A GUID identifying the statement for which a reference is being set',
				'references' => 'The hashes of the references that should be removed',
			)
		);
	}

	/**
	 * @see \ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge(
			parent::getPossibleErrors(),
			$this->claimModificationHelper->getPossibleErrors(),
			array(
				array( 'code' => 'no-such-reference', 'info' => $this->msg( 'wikibase-api-no-such-reference' )->text() ),
				array( 'code' => 'not-statement', 'info' => $this->msg( 'wikibase-api-not-statement' )->text() ),
			)
		);
	}

	/**
	 * @see ApiBase::getDescription
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getDescription() {
		return array(
			'API module for removing one or more references of the same statement.'
		);
	}

	/**
	 * @see ApiBase::getExamples
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbremovereferences&statement=Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F&references=455481eeac76e6a8af71a6b493c073d54788e7e9&token=foobar&baserevid=7201010' => 'Remove reference with hash "455481eeac76e6a8af71a6b493c073d54788e7e9" from claim with GUID of "Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F"',
		);
	}
}
