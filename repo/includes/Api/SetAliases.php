<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use ApiMain;
use ApiUsageException;
use IBufferingStatsdDataFactory;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\Lib\EntityFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * API module to set the aliases for a Wikibase entity.
 * Requires API write mode to be enabled.
 *
 * @license GPL-2.0-or-later
 */
class SetAliases extends ModifyEntity {

	/**
	 * @var FingerprintChangeOpFactory
	 */
	private $termChangeOpFactory;

	/** @var IBufferingStatsdDataFactory */
	private $stats;

	/** @var EntityFactory */
	private $entityFactory;

	/**
	 * @var string[]
	 */
	private $sandboxEntityIds;

	public function __construct(
		ApiMain $mainModule,
		string $moduleName,
		FingerprintChangeOpFactory $termChangeOpFactory,
		IBufferingStatsdDataFactory $stats,
		bool $federatedPropertiesEnabled,
		EntityFactory $entityFactory,
		array $sandboxEntityIds
	) {
		parent::__construct( $mainModule, $moduleName, $federatedPropertiesEnabled );
		$this->termChangeOpFactory = $termChangeOpFactory;
		$this->stats = $stats;
		$this->entityFactory = $entityFactory;
		$this->sandboxEntityIds = $sandboxEntityIds;
	}

	public static function factory(
		ApiMain $mainModule,
		string $moduleName,
		IBufferingStatsdDataFactory $stats,
		ChangeOpFactoryProvider $changeOpFactoryProvider,
		EntityFactory $entityFactory,
		SettingsArray $repoSettings
	): self {
		return new self(
			$mainModule,
			$moduleName,
			$changeOpFactoryProvider
				->getFingerprintChangeOpFactory(),
			$stats,
			$repoSettings->getSetting( 'federatedPropertiesEnabled' ),
			$entityFactory,
			$repoSettings->getSetting( 'sandboxEntityIds' )
		);
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
	 * @see ModifyEntity::validateParameters
	 *
	 * @param array $params
	 *
	 * @throws ApiUsageException
	 */
	protected function validateParameters( array $params ): void {
		parent::validateParameters( $params );

		if ( !( ( !empty( $params['add'] ) || !empty( $params['remove'] ) )
			xor isset( $params['set'] )
		) ) {
			$this->errorReporter->dieError(
				"Parameters 'add' and 'remove' are not allowed to be set when parameter 'set' is provided",
				'invalid-list'
			);
		}
	}

	private function adjustSummary( Summary $summary, array $params, AliasesProvider $entity ): void {
		if ( !empty( $params['add'] ) && !empty( $params['remove'] ) ) {
			$language = $params['language'];

			$aliasGroups = $entity->getAliasGroups();

			$summary->setAction( 'update' );
			$summary->setLanguage( $language );

			// Get the full list of current aliases
			if ( $aliasGroups->hasGroupForLanguage( $language ) ) {
				$aliases = $aliasGroups->getByLanguage( $language )->getAliases();
				$summary->addAutoSummaryArgs( $aliases );
			}
		}
	}

	protected function modifyEntity( EntityDocument $entity, ChangeOp $changeOp, array $preparedParameters ): Summary {
		if ( !( $entity instanceof AliasesProvider ) ) {
			$this->errorReporter->dieError( 'The given entity cannot contain aliases', 'not-supported' );
		}

		$language = $preparedParameters['language'];

		// FIXME: if we have ADD and REMOVE operations in the same call,
		// we will also have two ChangeOps updating the same edit summary.
		// This will cause the edit summary to be overwritten by the last ChangeOp being applied.
		$this->stats->increment( 'wikibase.repo.api.wbsetaliases.total' );
		if ( !empty( $preparedParameters['add'] ) && !empty( $preparedParameters['remove'] ) ) {
			$this->stats->increment( 'wikibase.repo.api.wbsetaliases.addremove' );
		}

		$summary = $this->createSummary( $preparedParameters );

		$this->applyChangeOp( $changeOp, $entity, $summary );

		$this->adjustSummary( $summary, $preparedParameters, $entity );

		$aliasGroups = $entity->getAliasGroups();

		if ( $aliasGroups->hasGroupForLanguage( $language ) ) {
			$aliasGroupList = $aliasGroups->getWithLanguages( [ $language ] );
			$this->getResultBuilder()->addAliasGroupList( $aliasGroupList, 'entity' );
		}

		return $summary;
	}

	/**
	 * @param string[] $aliases
	 *
	 * @return string[]
	 */
	private function normalizeAliases( array $aliases ): array {
		$stringNormalizer = $this->stringNormalizer;

		$aliases = array_map(
			function( $str ) use ( $stringNormalizer ) {
				return $stringNormalizer->trimToNFC( $str );
			},
			$aliases
		);

		$aliases = array_filter(
			$aliases,
			function( $str ) {
				return $str !== '';
			}
		);

		return $aliases;
	}

	protected function getChangeOp( array $preparedParameters, EntityDocument $entity ): ChangeOp {
		$changeOps = [];
		$language = $preparedParameters['language'];

		// Set the list of aliases to a user given one OR add/ remove certain entries
		if ( isset( $preparedParameters['set'] ) ) {
			$changeOps[] =
				$this->termChangeOpFactory->newSetAliasesOp(
					$language,
					$this->normalizeAliases( $preparedParameters['set'] )
				);
		} else {
			// FIXME: if we have ADD and REMOVE operations in the same call,
			// we will also have two ChangeOps updating the same edit summary.
			// This will cause the edit summary to be overwritten by the last ChangeOp beeing applied.
			if ( !empty( $preparedParameters['add'] ) ) {
				$changeOps[] =
					$this->termChangeOpFactory->newAddAliasesOp(
						$language,
						$this->normalizeAliases( $preparedParameters['add'] )
					);
			}

			if ( !empty( $preparedParameters['remove'] ) ) {
				$changeOps[] =
					$this->termChangeOpFactory->newRemoveAliasesOp(
						$language,
						$this->normalizeAliases( $preparedParameters['remove'] )
					);
			}
		}

		return $this->termChangeOpFactory->newFingerprintChangeOp( new ChangeOps( $changeOps ) );
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams(): array {
		return array_merge(
			parent::getAllowedParams(),
			[
				'add' => [
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_ISMULTI => true,
				],
				'remove' => [
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_ISMULTI => true,
				],
				'set' => [
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_ISMULTI => true,
				],
				'language' => [
					// TODO inject TermsLanguages as a service
					ParamValidator::PARAM_TYPE => WikibaseRepo::getTermsLanguages()->getLanguages(),
					ParamValidator::PARAM_REQUIRED => true,
				],
				'new' => [
					ParamValidator::PARAM_TYPE => $this->getEntityTypesWithAliases(),
				],
			]
		);
	}

	protected function getEntityTypesWithAliases(): array {
		$supportedEntityTypes = [];
		foreach ( $this->enabledEntityTypes as $entityType ) {
			$testEntity = $this->entityFactory->newEmpty( $entityType );
			if ( $testEntity instanceof AliasesProvider ) {
				$supportedEntityTypes[] = $entityType;
			}
		}
		return $supportedEntityTypes;
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages(): array {
		$id = $this->sandboxEntityIds[ 'mainItem' ];

		return [
			'action=wbsetaliases&language=en&id=' . $id . '&set=Foo|Bar'
				=> [ 'apihelp-wbsetaliases-example-1', $id ],

			'action=wbsetaliases&language=en&id=' . $id . '&add=Foo|Bar'
				=> [ 'apihelp-wbsetaliases-example-2', $id ],

			'action=wbsetaliases&language=en&id=' . $id . '&remove=Foo|Bar'
				=> [ 'apihelp-wbsetaliases-example-3', $id ],

			'action=wbsetaliases&language=en&id=' . $id . '&remove=Foo&add=Bar'
				=> [ 'apihelp-wbsetaliases-example-4', $id ],
		];
	}

}
