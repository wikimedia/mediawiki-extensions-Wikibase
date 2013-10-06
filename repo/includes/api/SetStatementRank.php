<?php

namespace Wikibase\Api;

use ApiBase;
use Wikibase\Claim;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\ChangeOp\ChangeOpStatementRank;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\Statement;
use Wikibase\Lib\Serializers\ClaimSerializer;

/**
 * API module for setting the rank of a statement
 *
 * @since 0.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class SetStatementRank extends ModifyClaim {

	/**
	 * @see \ApiBase::execute
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
			$this->dieUsage( 'The referenced claim is not a statement and thus cannot have a rank', 'not-statement' );
		}

		$changeOp = $this->getChangeOp();

		try {
			$changeOp->apply( $entity, $summary );
		} catch ( ChangeOpException $e ) {
			$this->dieUsage( $e->getMessage(), 'failed-save' );
		}

		$this->saveChanges( $entityContent, $summary );

		$this->claimModificationHelper->addClaimToApiResult( $claim, 'statement' );

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
	 * @return ChangeOpStatementRank
	 */
	protected function getChangeOp() {
		$params = $this->extractRequestParams();

		$claimGuid = $params['statement'];
		$idFormatter = WikibaseRepo::getDefaultInstance()->getIdFormatter();

		$rank = ClaimSerializer::unserializeRank( $params['rank'] );
		$changeOp = new ChangeOpStatementRank( $claimGuid, $rank, $idFormatter );

		return $changeOp;
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
				'rank' => array(
					ApiBase::PARAM_TYPE => ClaimSerializer::getRanks(),
					ApiBase::PARAM_REQUIRED => true,
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
				array( 'code' => 'not-statement', 'info' => $this->msg( 'wikibase-api-not-statement' )->text() ),
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
				'statement' => 'A GUID identifying the statement for which to set the rank',
				'rank' => 'The new value to set for the rank',
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
			'API module for setting the rank of a Wikibase statement.'
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
			'api.php?action=wbsetstatementrank&format=json&statement=Q2$4554c0f4-47b2-1cd9-2db9-aa270064c9f3&rank=normal&token=foobar' => 'Set the rank for the given statement to normal',
		);
	}
}
