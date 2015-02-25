<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use FormatJson;
use InvalidArgumentException;
use OutOfBoundsException;
use Wikibase\ChangeOp\ChangeOpReference;
use Wikibase\ChangeOp\StatementChangeOpFactory;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module for creating a reference or setting the value of an existing one.
 *
 * @since 0.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class SetReference extends ModifyClaim {

	/**
	 * @var StatementChangeOpFactory
	 */
	private $statementChangeOpFactory;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$changeOpFactoryProvider = WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider();
		$this->statementChangeOpFactory = $changeOpFactoryProvider->getStatementChangeOpFactory();
	}

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.3
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$this->validateParameters( $params );

		$entityId = $this->claimGuidParser->parse( $params['statement'] )->getEntityId();
		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;
		$entityRevision = $this->loadEntityRevision( $entityId, $baseRevisionId );
		$entity = $entityRevision->getEntity();

		$summary = $this->claimModificationHelper->createSummary( $params, $this );

		$claim = $this->claimModificationHelper->getClaimFromEntity( $params['statement'], $entity );

		if ( ! ( $claim instanceof Statement ) ) {
			$this->dieError( 'The referenced claim is not a statement and thus cannot have references', 'not-statement' );
		}

		if ( isset( $params['reference'] ) ) {
			$this->validateReferenceHash( $claim, $params['reference'] );
		}

		if( isset( $params['snaks-order' ] ) ) {
			$snaksOrder = $this->getArrayFromParam( $params['snaks-order'] );
		} else {
			$snaksOrder = array();
		}

		$newReference = new Reference(
			$this->getSnaks(
				$this->getArrayFromParam( $params['snaks'] ),
				$snaksOrder
			)
		);

		$changeOp = $this->getChangeOp( $newReference );
		$this->claimModificationHelper->applyChangeOp( $changeOp, $entity, $summary );

		$this->saveChanges( $entity, $summary );
		$this->getResultBuilder()->markSuccess();
		$this->getResultBuilder()->addReference( $newReference );
	}

	/**
	 * Check the provided parameters
	 */
	private function validateParameters( array $params ) {
		if ( !( $this->claimModificationHelper->validateClaimGuid( $params['statement'] ) ) ) {
			$this->dieError( 'Invalid claim guid' , 'invalid-guid' );
		}
	}

	/**
	 * @param Statement $claim
	 * @param string $referenceHash
	 */
	private function validateReferenceHash( Statement $claim, $referenceHash ) {
		if ( !$claim->getReferences()->hasReferenceHash( $referenceHash ) ) {
			$this->dieError( "Claim does not have a reference with the given hash" , 'no-such-reference' );
		}
	}

	/**
	 * @param string $arrayParam
	 *
	 * @return array
	 */
	private function getArrayFromParam( $arrayParam ) {
		$rawArray = FormatJson::decode( $arrayParam, true );

		if ( !is_array( $rawArray ) || !count( $rawArray ) ) {
			$this->dieError( 'No array or invalid JSON given', 'invalid-json' );
		}

		return $rawArray;
	}

	/**
	 * @param array $rawSnaks array of snaks
	 * @param array $snakOrder array of property ids the snaks are supposed to be ordered by.
	 *
	 * @todo: Factor deserialization out of the API class.
	 *
	 * @return SnakList
	 */
	private function getSnaks( array $rawSnaks, array $snakOrder = array() ) {
		$snaks = new SnakList();

		$serializerFactory = new SerializerFactory();
		$snakUnserializer = $serializerFactory->newUnserializerForClass( 'Wikibase\DataModel\Snak\Snak' );

		$snakOrder = ( count( $snakOrder ) > 0 ) ? $snakOrder : array_keys( $rawSnaks );

		try {
			foreach( $snakOrder as $propertyId ) {
				if ( !is_array( $rawSnaks[$propertyId] ) ) {
					$this->dieError( 'Invalid snak JSON given', 'invalid-json' );
				}
				foreach ( $rawSnaks[$propertyId] as $rawSnak ) {
					if ( !is_array( $rawSnak ) ) {
						$this->dieError( 'Invalid snak JSON given', 'invalid-json' );
					}

					$snak = $snakUnserializer->newFromSerialization( $rawSnak );
					$snaks[] = $snak;
				}
			}
		} catch( InvalidArgumentException $invalidArgumentException ) {
			// Handle Snak instantiation failures
			$this->dieError(
				'Failed to get reference from reference Serialization '
					. $invalidArgumentException->getMessage(),
				'snak-instantiation-failure'
			);
		} catch( OutOfBoundsException $outOfBoundsException ) {
			$this->dieError(
				'Failed to get reference from reference Serialization '
					. $outOfBoundsException->getMessage(),
				'snak-instantiation-failure'
			);
		}

		return $snaks;
	}

	/**
	 * @param Reference $reference
	 *
	 * @return ChangeOpReference
	 */
	private function getChangeOp( Reference $reference ) {
		$params = $this->extractRequestParams();

		$claimGuid = $params['statement'];
		$hash = isset( $params['reference'] ) ? $params['reference'] : '';
		$index = isset( $params['index'] ) ? $params['index'] : null;

		return $this->statementChangeOpFactory->newSetReferenceOp( $claimGuid, $reference, $hash, $index );
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
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
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return array(
			'action=wbsetreference&statement=Q76$D4FDE516-F20C-4154-ADCE-7C5B609DFDFF&snaks={"P212":[{"snaktype":"value","property":"P212","datavalue":{"type":"string","value":"foo"}}]}&baserevid=7201010&token=foobar'
				=> 'apihelp-wbsetreference-example-1',
			'action=wbsetreference&statement=Q76$D4FDE516-F20C-4154-ADCE-7C5B609DFDFF&reference=1eb8793c002b1d9820c833d234a1b54c8e94187e&snaks={"P212":[{"snaktype":"value","property":"P212","datavalue":{"type":"string","value":"bar"}}]}&baserevid=7201010&token=foobar'
				=> 'apihelp-wbsetreference-example-2',
			'action=wbsetreference&statement=Q76$D4FDE516-F20C-4154-ADCE-7C5B609DFDFF&snaks={"P212":[{"snaktype":"novalue","property":"P212"}]}&index=0&baserevid=7201010&token=foobar'
				=> 'apihelp-wbsetreference-example-3',
		);
	}

}
