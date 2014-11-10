<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpAliases;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Utils;

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
	protected $termChangeOpFactory;

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
	 * @throws InvalidArgumentException
	 * @return string[]
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
	 * @since 0.4
	 *
	 * @param array $params
	 * @return ChangeOpAliases
	 */
	protected function getChangeOps( array $params ) {
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
	 * @see ApiBase::getAllowedParams()
	 */
	public function getAllowedParams() {
		return array_merge(
			parent::getAllowedParams(),
			parent::getAllowedParamsForId(),
			parent::getAllowedParamsForSiteLink(),
			parent::getAllowedParamsForEntity(),
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
					ApiBase::PARAM_TYPE => Utils::getLanguageCodes(),
					ApiBase::PARAM_REQUIRED => true,
				),
			)
		);
	}

	/**
	 * @see ApiBase::getParamDescription()
	 */
	public function getParamDescription() {
		return array_merge(
			parent::getParamDescription(),
			parent::getParamDescriptionForId(),
			parent::getParamDescriptionForSiteLink(),
			parent::getParamDescriptionForEntity(),
			array(
				'add' => 'List of aliases to add (can be combined with remove)',
				'remove' => 'List of aliases to remove (can be combined with add)',
				'set' => 'A list of aliases that will replace the current list (can not be combined with neither add nor remove)',
				'language' => 'The language of which to set the aliases',
			)
		);
	}

	/**
	 * @see ApiBase::getDescription()
	 */
	public function getDescription() {
		return array(
			'API module to set the aliases for a Wikibase entity.'
		);
	}

	/**
	 * @see ApiBase::getExamples()
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbsetaliases&language=en&id=Q1&set=Foo|Bar'
				=> 'Set the English aliases for the entity with id Q1 to Foo and Bar',

			'api.php?action=wbsetaliases&language=en&id=Q1&add=Foo|Bar'
				=> 'Add Foo and Bar to the list of English aliases for the entity with id Q1',

			'api.php?action=wbsetaliases&language=en&id=Q1&remove=Foo|Bar'
				=> 'Remove Foo and Bar from the list of English aliases for the entity with id Q1',

			'api.php?action=wbsetaliases&language=en&id=Q1&remove=Foo&add=Bar'
				=> 'Remove Foo from the list of English aliases for the entity with id Q1 while adding Bar to it',
		);
	}

}
