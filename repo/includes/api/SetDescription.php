<?php

namespace Wikibase\Api;

use ApiMain;
use Wikibase\ChangeOp\ChangeOpDescription;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module for the language attributes for a Wikibase entity.
 * Requires API write mode to be enabled.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class SetDescription extends ModifyTerm {

	/**
	 * @var FingerprintChangeOpFactory
	 */
	private $termChangeOpFactory;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$changeOpFactoryProvider = WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider();
		$this->termChangeOpFactory = $changeOpFactoryProvider->getFingerprintChangeOpFactory();
	}

	/**
	 * @see \Wikibase\Api\ModifyEntity::modifyEntity()
	 */
	protected function modifyEntity( Entity &$entity, array $params, $baseRevId ) {
		wfProfileIn( __METHOD__ );
		$summary = $this->createSummary( $params );
		$language = $params['language'];

		$changeOp = $this->getChangeOp( $params );
		$this->applyChangeOp( $changeOp, $entity, $summary );

		$descriptions = array( $language => ( $entity->getDescription( $language ) !== false ) ? $entity->getDescription( $language ) : "" );

		$this->getResultBuilder()->addDescriptions( $descriptions, 'entity' );

		wfProfileOut( __METHOD__ );
		return $summary;
	}

	/**
	 * @param array $params
	 *
	 * @return ChangeOpDescription
	 */
	private function getChangeOp( array $params ) {
		wfProfileIn( __METHOD__ );
		$description = "";
		$language = $params['language'];

		if ( isset( $params['value'] ) ) {
			$description = $this->stringNormalizer->trimToNFC( $params['value'] );
		}

		if ( $description === "" ) {
			$op = $this->termChangeOpFactory->newRemoveDescriptionOp( $language );
		} else {
			$op = $this->termChangeOpFactory->newSetDescriptionOp( $language, $description );
		}

		wfProfileOut( __METHOD__ );
		return $op;
	}

	/**
	 * @see ApiBase::getExamplesMessages()
	 *
	 * @return array
	 */
	protected function getExamplesMessages() {
		return array(
			'action=wbsetdescription&id=Q42&language=en&value=An%20encyclopedia%20that%20everyone%20can%20edit'
				=> 'apihelp-wbsetdescription-example-1',
			'action=wbsetdescription&site=enwiki&title=Wikipedia&language=en&value=An%20encyclopedia%20that%20everyone%20can%20edit'
				=> 'apihelp-wbsetdescription-example-2',
		);
	}

}
