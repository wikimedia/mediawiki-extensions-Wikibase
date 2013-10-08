<?php

namespace Wikibase\Api;

use ApiBase;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\ChangeOp\ChangeOpReference;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\SnakList;
use Wikibase\Statement;
use Wikibase\Reference;
use Wikibase\Lib\Serializers\SerializerFactory;
use DataValues\IllegalValueException;

/**
 * API module for creating a reference or setting the value of an existing one.
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
class SetReference extends ModifyClaim {

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.3
	 */
	public function execute() {
		wfProfileIn( __METHOD__ );

		$params = $this->extractRequestParams();
		$this->validateParameters( $params );

		$entityId = $this->claimGuidParser->parse( $params['statement'] )->getEntityId();
		$entityTitle = $this->claimModificationHelper->getEntityTitle( $entityId );
		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;
		$entityContent = $this->loadEntityContent( $entityTitle, $baseRevisionId );
		$entity = $entityContent->getEntity();
		$summary = $this->claimModificationHelper->createSummary( $params, $this );

		$claim = $this->claimModificationHelper->getClaimFromEntity( $params['statement'], $entity );

		if ( ! ( $claim instanceof Statement ) ) {
			$this->dieUsage( 'The referenced claim is not a statement and thus cannot have references', 'not-statement' );
		}

		if ( isset( $params['reference'] ) ) {
			$this->validateReferenceHash( $claim, $params['reference'] );
		}

		$unserializer = $this->serializerFactory->newUnserializerForClass( 'Wikibase\Reference' );

		$decodedParams = array(
			'snaks' => $this->getArrayFromParam( $params['snaks'] )
		);

		if( isset( $params['snaks-order' ] ) ) {
			$decodedParams['snaks-order'] = $this->getArrayFromParam( $params['snaks-order'] );
		}

		$newReference = $unserializer->newFromSerialization(
			array_merge( $params, $decodedParams )
		);

		$changeOp = $this->getChangeOp( $newReference );

		try {
			$changeOp->apply( $entity, $summary );
		} catch ( ChangeOpException $e ) {
			$this->dieUsage( $e->getMessage(), 'failed-save' );
		}

		$this->saveChanges( $entityContent, $summary );

		$this->outputReference( $newReference );

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
	 * @param Statement $claim
	 * @param string $referenceHash
	 */
	protected function validateReferenceHash( Statement $claim, $referenceHash ) {
		if ( !$claim->getReferences()->hasReferenceHash( $referenceHash ) ) {
			$this->dieUsage( "Claim does not have a reference with the given hash" , 'no-such-reference' );
		}
	}

	/**
	 * @since 0.5
	 *
	 * @param string $arrayParam
	 *
	 * @return array
	 */
	protected function getArrayFromParam( $arrayParam ) {
		$rawArray = \FormatJson::decode( $arrayParam, true );

		if ( !is_array( $rawArray ) || !count( $rawArray ) ) {
			$this->dieUsage( 'No array or invalid JSON given', 'invalid-json' );
		}

		return $rawArray;
	}

	/**
	 * @since 0.3
	 *
	 * @param array $rawSnaks
	 * @param array $snakOrder List of property ids the snaks are supposed to be ordered by.
	 *
	 * @return SnakList
	 */
	protected function getSnaks( array $rawSnaks, array $snakOrder = array() ) {
		$snaks = new SnakList();

		$snakUnserializer = $this->serializerFactory->newUnserializerForClass( 'Wikibase\Snak' );

		$snakOrder = ( count( $snakOrder ) > 0 ) ? $snakOrder : array_keys( $rawSnaks );

		try {
			foreach( $snakOrder as $propertyId ) {
				if ( !is_array( $rawSnaks[$propertyId] ) ) {
					$this->dieUsage( 'Invalid snak JSON given', 'invalid-json' );
				}
				foreach ( $rawSnaks[$propertyId] as $rawSnak ) {
					if ( !is_array( $rawSnak ) ) {
						$this->dieUsage( 'Invalid snak JSON given', 'invalid-json' );
					}

					$snak = $snakUnserializer->newFromSerialization( $rawSnak );
					$this->snakValidation->validateSnak( $snak );
					$snaks[] = $snak;
				}
			}
		} catch ( IllegalValueException $ex ) {
			// Handle Snak instantiation failures
			$this->dieUsage( 'Invalid snak JSON given. IllegalValueException', 'invalid-json' );
		}

		return $snaks;
	}

	/**
	 * @since 0.4
	 *
	 * @param Reference $reference
	 *
	 * @return ChangeOpReference
	 */
	protected function getChangeOp( Reference $reference ) {
		$params = $this->extractRequestParams();

		$claimGuid = $params['statement'];
		$hash = isset( $params['reference'] ) ? $params['reference'] : '';
		$index = isset( $params['index'] ) ? $params['index'] : null;

		return new ChangeOpReference( $claimGuid, $reference, $hash, $index );
	}

	/**
	 * @since 0.3
	 *
	 * @param \Wikibase\Reference $reference
	 */
	protected function outputReference( Reference $reference ) {
		$serializer = $this->serializerFactory->newSerializerForObject( $reference );
		$serializer->getOptions()->setIndexTags( $this->getResult()->getIsRawMode() );

		$this->getResult()->addValue(
			null,
			'reference',
			$serializer->getSerialized( $reference )
		);
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
				'statement' => array(
					ApiBase::PARAM_TYPE => 'string',
					ApiBase::PARAM_REQUIRED => true,
				),
				'snaks' => array(
					ApiBase::PARAM_TYPE => 'string',
					ApiBase::PARAM_REQUIRED => true,
				),
				'snaks-order' => array(
					ApiBase::PARAM_TYPE => 'string',
				),
				'reference' => array(
					ApiBase::PARAM_TYPE => 'string',
				),
				'index' => array(
					ApiBase::PARAM_TYPE => 'integer',
				),
			),
			parent::getAllowedParams()
		);
	}

	/**
	 * @see ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge(
			parent::getPossibleErrors(),
			$this->claimModificationHelper->getPossibleErrors(),
			array(
				array( 'code' => 'no-such-claim', 'info' => $this->msg( 'wikibase-api-no-such-claim' )->text() ),
				array( 'code' => 'not-statement', 'info' => $this->msg( 'wikibase-api-not-statement' )->text() ),
				array( 'code' => 'no-such-reference', 'info' => $this->msg( 'wikibase-api-no-such-reference' )->text() ),
			)
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
				'statement' => 'A GUID identifying the statement for which a reference is being set',
				'snaks' => 'The snaks to set the reference to. JSON object with property ids pointing to arrays containing the snaks for that property',
				'reference' => 'A hash of the reference that should be updated. Optional. When not provided, a new reference is created',
				'index' => 'The index within the statement\'s list of references where to move the reference to. Optional. When not provided, the reference will stay in place.',
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
			'API module for creating a reference or setting the value of an existing one.'
		);
	}

	/**
	 * @see \ApiBase::getExamples()
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbsetreference&statement=Q76$D4FDE516-F20C-4154-ADCE-7C5B609DFDFF&snaks={"P212":[{"snaktype":"value","property":"P212","datavalue":{"type":"string","value":"foo"}}]}&baserevid=7201010&token=foobar'
				=> 'Create a new reference for claim with GUID Q76$D4FDE516-F20C-4154-ADCE-7C5B609DFDFF',
			'api.php?action=wbsetreference&statement=Q76$D4FDE516-F20C-4154-ADCE-7C5B609DFDFF&reference=1eb8793c002b1d9820c833d234a1b54c8e94187e&snaks={"P212":[{"snaktype":"value","property":"P212","datavalue":{"type":"string","value":"bar"}}]}&baserevid=7201010&token=foobar'
				=> 'Set reference for claim with GUID Q76$D4FDE516-F20C-4154-ADCE-7C5B609DFDFF which has hash of 1eb8793c002b1d9820c833d234a1b54c8e94187e',
			'api.php?action=wbsetreference&statement=Q76$D4FDE516-F20C-4154-ADCE-7C5B609DFDFF&snaks={"P212":[{"snaktype":"novalue","property":"P212"}]}&index=0&baserevid=7201010&token=foobar'
				=> 'Creates a new reference for the claim with GUID Q76$D4FDE516-F20C-4154-ADCE-7C5B609DFDFF and inserts the new reference at the top of the list of references instead of appending it to the bottom.',
		);
	}
}
