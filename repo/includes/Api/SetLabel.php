<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use ApiMain;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\Lib\EntityFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\FingerprintChangeOpFactory;
use Wikimedia\ParamValidator\ParamValidator;

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
	 * @var EntityFactory
	 */
	private $entityFactory;

	/**
	 * @var string[]
	 */
	private $sandboxEntityIds;

	public function __construct(
		ApiMain $mainModule,
		string $moduleName,
		FingerprintChangeOpFactory $termChangeOpFactory,
		bool $federatedPropertiesEnabled,
		EntityFactory $entityFactory,
		array $sandboxEntityIds
	) {
		parent::__construct( $mainModule, $moduleName, $federatedPropertiesEnabled );

		$this->termChangeOpFactory = $termChangeOpFactory;
		$this->entityFactory = $entityFactory;
		$this->sandboxEntityIds = $sandboxEntityIds;
	}

	public static function factory(
		ApiMain $mainModule,
		string $moduleName,
		ChangeOpFactoryProvider $changeOpFactoryProvider,
		EntityFactory $entityFactory,
		SettingsArray $repoSettings
	): self {
		return new self(
			$mainModule,
			$moduleName,
			$changeOpFactoryProvider
				->getFingerprintChangeOpFactory(),
			$repoSettings->getSetting( 'federatedPropertiesEnabled' ),
			$entityFactory,
			$repoSettings->getSetting( 'sandboxEntityIds' )
		);
	}

	protected function modifyEntity( EntityDocument $entity, ChangeOp $changeOp, array $preparedParameters ): Summary {
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

	protected function getChangeOp( array $preparedParameters, EntityDocument $entity ): ChangeOp {
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

		return $this->termChangeOpFactory->newFingerprintChangeOp( new ChangeOps( $op ) );
	}

	/**
	 * @see ApiBase::needsToken
	 *
	 * @return string
	 */
	public function needsToken(): string {
		return 'csrf';
	}

	/**
	 * @see ApiBase::isWriteMode()
	 *
	 * @return bool Always true.
	 */
	public function isWriteMode(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages(): array {
		$id = $this->sandboxEntityIds[ 'mainItem' ];

		return [
			'action=wbsetlabel&id=' . $id . '&language=en&value=Wikimedia&format=jsonfm'
				=> [ 'apihelp-wbsetlabel-example-1', $id ],
			'action=wbsetlabel&site=enwiki&title=Earth&language=en&value=Earth'
				=> 'apihelp-wbsetlabel-example-2',
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams(): array {
		return array_merge(
			parent::getAllowedParams(),
			[
				'new' => [
					ParamValidator::PARAM_TYPE => $this->getEntityTypesWithLabels(),
				],
			]
		);
	}

	protected function getEntityTypesWithLabels(): array {
		$supportedEntityTypes = [];
		foreach ( $this->enabledEntityTypes as $entityType ) {
			$testEntity = $this->entityFactory->newEmpty( $entityType );
			if ( $testEntity instanceof LabelsProvider ) {
				$supportedEntityTypes[] = $entityType;
			}
		}
		return $supportedEntityTypes;
	}

}
