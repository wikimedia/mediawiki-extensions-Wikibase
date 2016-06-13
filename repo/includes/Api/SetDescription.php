<?php

namespace Wikibase\Repo\Api;

use ApiMain;
use Wikibase\ChangeOp\ChangeOpDescription;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module for the language attributes for a Wikibase entity.
 * Requires API write mode to be enabled.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
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

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $this->getContext() );
		$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

		$this->termChangeOpFactory = $changeOpFactoryProvider->getFingerprintChangeOpFactory();
	}

	/**
	 * @see ModifyEntity::modifyEntity
	 */
	protected function modifyEntity( EntityDocument &$entity, array $params, $baseRevId ) {
		if ( !( $entity instanceof DescriptionsProvider ) ) {
			$this->errorReporter->dieError( 'The given entity cannot contain descriptions', 'not-supported' );
		}

		$summary = $this->createSummary( $params );
		$language = $params['language'];

		$changeOp = $this->getChangeOp( $params );
		$this->applyChangeOp( $changeOp, $entity, $summary );

		$descriptions = $entity->getDescriptions();
		$resultBuilder = $this->getResultBuilder();

		if ( $descriptions->hasTermForLanguage( $language ) ) {
			$termList = $descriptions->getWithLanguages( array( $language ) );
			$resultBuilder->addDescriptions( $termList, 'entity' );
		} else {
			$resultBuilder->addRemovedDescription( $language, 'entity' );
		}

		return $summary;
	}

	/**
	 * @param array $params
	 *
	 * @return ChangeOpDescription
	 */
	private function getChangeOp( array $params ) {
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

		return $op;
	}

	/**
	 * @see ApiBase::needsToken
	 *
	 * @return string
	 */
	public function needsToken() {
		return 'csrf';
	}

	/**
	 * @see ApiBase::isWriteMode()
	 *
	 * @return bool Always true.
	 */
	public function isWriteMode() {
		return true;
	}

	/**
	 * @see ApiBase::getExamplesMessages
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
