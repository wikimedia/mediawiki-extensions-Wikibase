<?php

namespace Wikibase\Repo\Api;

use ApiMain;
use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpAliases;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module to set the aliases for a Wikibase entity.
 * Requires API write mode to be enabled.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class SetAliases extends ModifyEntity {

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
		$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

		$this->termChangeOpFactory = $changeOpFactoryProvider->getFingerprintChangeOpFactory();
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
		$permissions = $this->isWriteMode() ? array( 'read', 'edit' ) : array( 'read' );
		$permissions[] = $entity->getType() . '-term';
		return $permissions;
	}

	/**
	 * @see ModifyEntity::validateParameters
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
	 */
	protected function modifyEntity( EntityDocument &$entity, array $params, $baseRevId ) {
		if ( !( $entity instanceof AliasesProvider ) ) {
			$this->errorReporter->dieError( 'The given entity cannot contain aliases', 'not-supported' );
		}

		$summary = $this->createSummary( $params );
		$language = $params['language'];

		/** @var ChangeOp[] $aliasesChangeOps */
		$aliasesChangeOps = $this->getChangeOps( $params );

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
			$aliasGroupList = $aliasGroups->getWithLanguages( array( $language ) );
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
	 * @return ChangeOpAliases
	 */
	private function getChangeOps( array $params ) {
		$changeOps = array();
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
			array(
				'add' => array(
					self::PARAM_TYPE => 'string',
					self::PARAM_ISMULTI => true,
				),
				'remove' => array(
					self::PARAM_TYPE => 'string',
					self::PARAM_ISMULTI => true,
				),
				'set' => array(
					self::PARAM_TYPE => 'string',
					self::PARAM_ISMULTI => true,
				),
				'language' => array(
					self::PARAM_TYPE => WikibaseRepo::getDefaultInstance()->getTermsLanguages()->getLanguages(),
					self::PARAM_REQUIRED => true,
				),
			)
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return array(
			'action=wbsetaliases&language=en&id=Q1&set=Foo|Bar'
				=> 'apihelp-wbsetaliases-example-1',

			'action=wbsetaliases&language=en&id=Q1&add=Foo|Bar'
				=> 'apihelp-wbsetaliases-example-2',

			'action=wbsetaliases&language=en&id=Q1&remove=Foo|Bar'
				=> 'apihelp-wbsetaliases-example-3',

			'action=wbsetaliases&language=en&id=Q1&remove=Foo&add=Bar'
				=> 'apihelp-wbsetaliases-example-4',
		);
	}

}
