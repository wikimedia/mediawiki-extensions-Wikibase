<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use ApiMain;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\Lib\EntityFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\FingerprintChangeOpFactory;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * API module for the language attributes for a Wikibase entity.
 * Requires API write mode to be enabled.
 *
 * @license GPL-2.0-or-later
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
		if ( !( $entity instanceof DescriptionsProvider ) ) {
			$this->errorReporter->dieError( 'The given entity cannot contain descriptions', 'not-supported' );
		}

		$summary = $this->createSummary( $preparedParameters );
		$language = $preparedParameters['language'];

		$this->applyChangeOp( $changeOp, $entity, $summary );

		$descriptions = $entity->getDescriptions();
		$resultBuilder = $this->getResultBuilder();

		if ( $descriptions->hasTermForLanguage( $language ) ) {
			$termList = $descriptions->getWithLanguages( [ $language ] );
			$resultBuilder->addDescriptions( $termList, 'entity' );
		} else {
			$resultBuilder->addRemovedDescription( $language, 'entity' );
		}

		return $summary;
	}

	protected function getChangeOp( array $preparedParameters, EntityDocument $entity ): ChangeOp {
		$description = "";
		$language = $preparedParameters['language'];

		if ( isset( $preparedParameters['value'] ) ) {
			$description = $this->stringNormalizer->trimToNFC( $preparedParameters['value'] );
		}

		if ( $description === "" ) {
			$op = $this->termChangeOpFactory->newRemoveDescriptionOp( $language );
		} else {
			$op = $this->termChangeOpFactory->newSetDescriptionOp( $language, $description );
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
			'action=wbsetdescription&id=' . $id . '&language=en&value=An%20encyclopedia%20that%20everyone%20can%20edit'
				=> [ 'apihelp-wbsetdescription-example-1', $id ],
			'action=wbsetdescription&site=enwiki&title=Wikipedia&language=en&value=An%20encyclopedia%20that%20everyone%20can%20edit'
				=> 'apihelp-wbsetdescription-example-2',
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
					ParamValidator::PARAM_TYPE => $this->getEntityTypesWithDescriptions(),
				],
			]
		);
	}

	protected function getEntityTypesWithDescriptions(): array {
		$supportedEntityTypes = [];
		foreach ( $this->enabledEntityTypes as $entityType ) {
			$testEntity = $this->entityFactory->newEmpty( $entityType );
			if ( $testEntity instanceof DescriptionsProvider ) {
				$supportedEntityTypes[] = $entityType;
			}
		}
		return $supportedEntityTypes;
	}

}
