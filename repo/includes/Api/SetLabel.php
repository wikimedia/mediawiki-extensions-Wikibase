<?php

namespace Wikibase\Repo\Api;

use ApiMain;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpLabel;
use Wikibase\Repo\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\Summary;

/**
 * API module to set the label for a Wikibase entity.
 * Requires API write mode to be enabled.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class SetLabel extends ModifyTerm {

	/**
	 * @var FingerprintChangeOpFactory
	 */
	private $termChangeOpFactory;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param FingerprintChangeOpFactory $termChangeOpFactory
	 */
	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		FingerprintChangeOpFactory $termChangeOpFactory
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->termChangeOpFactory = $termChangeOpFactory;
	}

	/**
	 * @see ModifyEntity::modifyEntity
	 *
	 * @param EntityDocument &$entity
	 * @param ChangeOp $changeOp
	 * @param array $preparedParameters
	 *
	 * @return Summary
	 */
	protected function modifyEntity( EntityDocument &$entity, ChangeOp $changeOp, array $preparedParameters ) {
		if ( !( $entity instanceof LabelsProvider ) ) {
			$this->errorReporter->dieError( 'The given entity cannot contain labels', 'not-supported' );
		}

		$summary = $this->createSummary( $preparedParameters );
		$language = $preparedParameters['language'];

		$this->applyChangeOp( $changeOp, $entity, $summary );

		$labels = $entity->getLabels();
		$resultBuilder = $this->getResultBuilder();

		if ( $labels->hasTermForLanguage( $language ) ) {
			$termList = $labels->getWithLanguages( [ $language ] );
			$resultBuilder->addLabels( $termList, 'entity' );
		} else {
			$resultBuilder->addRemovedLabel( $language, 'entity' );
		}

		return $summary;
	}

	/**
	 * @param array $preparedParameters
	 * @param EntityDocument $entity
	 *
	 * @return ChangeOpLabel
	 */
	protected function getChangeOp( array $preparedParameters, EntityDocument $entity ) {
		$label = "";
		$language = $preparedParameters['language'];

		if ( isset( $preparedParameters['value'] ) ) {
			$label = $this->stringNormalizer->trimToNFC( $preparedParameters['value'] );
		}

		if ( $label === "" ) {
			$op = $this->termChangeOpFactory->newRemoveLabelOp( $language );
		} else {
			$op = $this->termChangeOpFactory->newSetLabelOp( $language, $label );
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
		return [
			'action=wbsetlabel&id=Q42&language=en&value=Wikimedia&format=jsonfm'
				=> 'apihelp-wbsetlabel-example-1',
			'action=wbsetlabel&site=enwiki&title=Earth&language=en&value=Earth'
				=> 'apihelp-wbsetlabel-example-2',
		];
	}

}
