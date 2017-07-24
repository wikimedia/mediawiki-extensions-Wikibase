<?php

namespace Wikibase\Repo\Api;

use ApiMain;
use ApiUsageException;
use InvalidArgumentException;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Summary;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpAliases;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module to set the aliases for a Wikibase entity.
 * Requires API write mode to be enabled.
 *
 * @license GPL-2.0+
 */
class SetAliases extends ModifyEntity {

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
		FingerprintChangeOpFactory  $termChangeOpFactory
	) {
		parent::__construct( $mainModule, $moduleName );
		$this->termChangeOpFactory = $termChangeOpFactory;
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
	 * @param EntityDocument $entity
	 *
	 * @throws InvalidArgumentException
	 * @return string[] A list of permissions
	 */
	protected function getRequiredPermissions( EntityDocument $entity ) {
		return [ EntityPermissionChecker::ACTION_EDIT_TERMS ];
	}

	/**
	 * @see ModifyEntity::validateParameters
	 *
	 * @param array $params
	 *
	 * @throws ApiUsageException
	 */
	protected function validateParameters( array $params ) {
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

	/**
	 * @see ModifyEntity::modifyEntity
	 *
	 * @param EntityDocument &$entity
	 * @param array $preparedParameters
	 * @param int $baseRevId
	 *
	 * @return Summary
	 */
	protected function modifyEntity( EntityDocument &$entity, array $preparedParameters, $baseRevId ) {
		if ( !( $entity instanceof AliasesProvider ) ) {
			$this->errorReporter->dieError( 'The given entity cannot contain aliases', 'not-supported' );
		}

		$summary = $this->createSummary( $preparedParameters );
		$language = $preparedParameters['language'];

		/** @var ChangeOp[] $aliasesChangeOps */
		$aliasesChangeOps = $this->getChangeOps( $preparedParameters );

		$aliasGroups = $entity->getAliasGroups();

		if ( count( $aliasesChangeOps ) == 1 ) {
			$this->applyChangeOp( $aliasesChangeOps[0], $entity, $summary );
		} else {
			$changeOps = new ChangeOps();
			$changeOps->add( $aliasesChangeOps );

			$this->applyChangeOp( $changeOps, $entity );

			// Set the action to 'set' in case we add and remove aliases in a single edit
			$summary->setAction( 'set' );
			$summary->setLanguage( $language );

			// Get the full list of current aliases
			if ( $aliasGroups->hasGroupForLanguage( $language ) ) {
				$aliases = $aliasGroups->getByLanguage( $language )->getAliases();
				$summary->addAutoSummaryArgs( $aliases );
			}
		}

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
	private function normalizeAliases( array $aliases ) {
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

	/**
	 * @param array $params
	 *
	 * @return ChangeOpAliases[]
	 */
	private function getChangeOps( array $params ) {
		$changeOps = [];
		$language = $params['language'];

		// Set the list of aliases to a user given one OR add/ remove certain entries
		if ( isset( $params['set'] ) ) {
			$changeOps[] =
				$this->termChangeOpFactory->newSetAliasesOp(
					$language,
					$this->normalizeAliases( $params['set'] )
				);
		} else {
			// FIXME: if we have ADD and REMOVE operations in the same call,
			// we will also have two ChangeOps updating the same edit summary.
			// This will cause the edit summary to be overwritten by the last ChangeOp beeing applied.
			if ( !empty( $params['add'] ) ) {
				$changeOps[] =
					$this->termChangeOpFactory->newAddAliasesOp(
						$language,
						$this->normalizeAliases( $params['add'] )
					);
			}

			if ( !empty( $params['remove'] ) ) {
				$changeOps[] =
					$this->termChangeOpFactory->newRemoveAliasesOp(
						$language,
						$this->normalizeAliases( $params['remove'] )
					);
			}
		}

		return $changeOps;
	}

	/**
	 * @see ModifyEntity::getAllowedParams
	 */
	protected function getAllowedParams() {
		return array_merge(
			parent::getAllowedParams(),
			[
				'add' => [
					self::PARAM_TYPE => 'string',
					self::PARAM_ISMULTI => true,
				],
				'remove' => [
					self::PARAM_TYPE => 'string',
					self::PARAM_ISMULTI => true,
				],
				'set' => [
					self::PARAM_TYPE => 'string',
					self::PARAM_ISMULTI => true,
				],
				'language' => [
					self::PARAM_TYPE => WikibaseRepo::getDefaultInstance()->getTermsLanguages()->getLanguages(),
					self::PARAM_REQUIRED => true,
				],
			]
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return [
			'action=wbsetaliases&language=en&id=Q1&set=Foo|Bar'
				=> 'apihelp-wbsetaliases-example-1',

			'action=wbsetaliases&language=en&id=Q1&add=Foo|Bar'
				=> 'apihelp-wbsetaliases-example-2',

			'action=wbsetaliases&language=en&id=Q1&remove=Foo|Bar'
				=> 'apihelp-wbsetaliases-example-3',

			'action=wbsetaliases&language=en&id=Q1&remove=Foo&add=Bar'
				=> 'apihelp-wbsetaliases-example-4',
		];
	}

}
