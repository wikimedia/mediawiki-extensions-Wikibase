<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use InvalidArgumentException;
use Status;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpAliases;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module to set the aliases for a Wikibase entity.
 * Requires API write mode to be enabled.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
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

		$changeOpFactoryProvider = WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider();
		$this->termChangeOpFactory = $changeOpFactoryProvider->getFingerprintChangeOpFactory();
	}

	/**
	 * @see ModifyEntity::getRequiredPermissions()
	 *
	 * @param Entity $entity
	 * @param array $params
	 *
	 * @throws \InvalidArgumentException
	 * @return array|Status
	 */
	protected function getRequiredPermissions( Entity $entity, array $params ) {
		$permissions = parent::getRequiredPermissions( $entity, $params );
		if( $entity instanceof Item ) {
			$type = 'item';
		} else if ( $entity instanceof Property ) {
			$type = 'property';
		} else {
			throw new InvalidArgumentException( 'Unexpected Entity type when checking special page term change permissions' );
		}
		$permissions[] = $type . '-term';
		return $permissions;
	}

	/**
	 * @see ModifyEntity::validateParameters()
	 */
	protected function validateParameters( array $params ) {
		parent::validateParameters( $params );

		if ( !( ( !empty( $params['add'] ) || !empty( $params['remove'] ) ) xor isset( $params['set'] ) ) ) {
			$this->dieError( "Parameters 'add' and 'remove' are not allowed to be set when parameter 'set' is provided" , 'invalid-list' );
		}
	}

	/**
	 * @see ApiModifyEntity::createEntity()
	 */
	protected function createEntity( array $params ) {
		$this->dieError( 'Could not find an existing entity' , 'no-such-entity' );
	}

	/**
	 * @see ModifyEntity::modifyEntity()
	 */
	protected function modifyEntity( Entity &$entity, array $params, $baseRevId ) {
		wfProfileIn( __METHOD__ );

		$summary = $this->createSummary( $params );
		$language = $params['language'];

		/** @var ChangeOp[] $aliasesChangeOps */
		$aliasesChangeOps = $this->getChangeOps( $params );

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
			$summary->addAutoSummaryArgs( $entity->getAliases( $language ) );
		}

		$aliases = $entity->getAliases( $language );
		if ( count( $aliases ) ) {
			$this->getResultBuilder()->addAliases( array( $language => $aliases ), 'entity' );
		}

		wfProfileOut( __METHOD__ );
		return $summary;
	}

	private function normalizeAliases( $aliases ) {
		$stringNormalizer = $this->stringNormalizer; // hack for PHP fail.

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
	 * @return ChangeOpAliases
	 */
	private function getChangeOps( array $params ) {
		wfProfileIn( __METHOD__ );
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

		wfProfileOut( __METHOD__ );
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
					ApiBase::PARAM_TYPE => 'string',
					ApiBase::PARAM_ISMULTI => true,
				),
				'remove' => array(
					ApiBase::PARAM_TYPE => 'string',
					ApiBase::PARAM_ISMULTI => true,
				),
				'set' => array(
					ApiBase::PARAM_TYPE => 'string',
					ApiBase::PARAM_ISMULTI => true,
				),
				'language' => array(
					ApiBase::PARAM_TYPE => WikibaseRepo::getDefaultInstance()->getTermsLanguages()->getLanguages(),
					ApiBase::PARAM_REQUIRED => true,
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
