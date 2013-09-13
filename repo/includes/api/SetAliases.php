<?php

namespace Wikibase\Api;

use Wikibase\ChangeOps;
use ApiBase, User, Language;
use Wikibase\Entity;
use Wikibase\EntityContent;
use Wikibase\Utils;
use Wikibase\ChangeOpAliases;

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
	 * @see  \Wikibase\Api\ModifyEntity::getRequiredPermissions()
	 */
	protected function getRequiredPermissions( EntityContent $entityContent, array $params ) {
		$permissions = parent::getRequiredPermissions( $entityContent, $params );

		if ( !empty( $params['add'] ) || isset( $params['set'] ) ) {
			// add and set has a common permission due to the special page SetAliases
			$permissions[] = 'alias-update';
		}
		if ( !empty( $params['remove'] ) ) {
			$permissions[] = 'alias-remove';
		}
		return $permissions;
	}

	/**
	 * @see \Wikibase\Api\ModifyEntity::validateParameters()
	 */
	protected function validateParameters( array $params ) {
		parent::validateParameters( $params );

		if ( !( ( !empty( $params['add'] ) || !empty( $params['remove'] ) ) xor isset( $params['set'] ) ) ) {
			$this->dieUsage( "Parameters 'add' and 'remove' are not allowed to be set when parameter 'set' is provided" , 'invalid-list' );
		}
	}

	/**
	 * @see ApiModifyEntity::createEntity()
	 */
	protected function createEntity( array $params ) {
		$this->dieUsage( 'Could not find an existing entity' , 'no-such-entity' );
	}

	/**
	 * @see \Wikibase\Api\ModifyEntity::modifyEntity()
	 */
	protected function modifyEntity( EntityContent &$entityContent, array $params ) {
		wfProfileIn( __METHOD__ );

		$summary = $this->createSummary( $params );
		$entity = $entityContent->getEntity();
		$language = $params['language'];

		$aliasesChangeOps = $this->getChangeOps( $params );

		if ( count( $aliasesChangeOps ) == 1 ) {
			$aliasesChangeOps[0]->apply( $entity, $summary );
		} else {
			$changeOps = new ChangeOps();
			$changeOps->add( $aliasesChangeOps );
			$changeOps->apply( $entity );

			// Set the action to 'set' in case we add and remove aliases in a single edit
			$summary->setAction( 'set' );
			$summary->setLanguage( $language );

			// Get the full list of current aliases
			$summary->addAutoSummaryArgs( $entity->getAliases( $language ) );
		}

		$aliases = $entity->getAliases( $language );
		if ( count( $aliases ) ) {
			$this->addAliasesToResult( array( $language => $aliases ), 'entity' );
		}

		wfProfileOut( __METHOD__ );
		return $summary;
	}

	/**
	 * @since 0.4
	 *
	 * @param array $params
	 * @return ChangeOpAliases
	 */
	protected function getChangeOps( array $params ) {
		$stringNormalizer = $this->stringNormalizer; // hack for PHP fail.

		wfProfileIn( __METHOD__ );
		$changeOps = array();
		$language = $params['language'];

		// Set the list of aliases to a user given one OR add/ remove certain entries
		if ( isset( $params['set'] ) ) {
			$changeOps[] =
				new ChangeOpAliases(
					$language,
					array_map(
						function( $str ) use ( $stringNormalizer ) {
							return $stringNormalizer->trimToNFC( $str );
						},
						$params['set']
					),
					'set',
					$this->createSummary( $params )
				);
		} else {
			// FIXME: if we have ADD and REMOVE operations in the same call,
			// we will also have two ChangeOps updating the same edit summary.
			// This will cause the edit summary to be overwritten by the last ChangeOp beeing applied.
			if ( !empty( $params['add'] ) ) {
				$changeOps[] =
					new ChangeOpAliases(
						$language,
						array_map(
							function( $str ) use ( $stringNormalizer ) {
								return $stringNormalizer->trimToNFC( $str );
							},
							$params['add']
						),
						'add',
						$this->createSummary( $params )
					);
			}

			if ( !empty( $params['remove'] ) ) {
				$changeOps[] =
					new ChangeOpAliases(
						$language,
						array_map(
							function( $str ) use ( $stringNormalizer ) {
								return $stringNormalizer->trimToNFC( $str );
							},
							$params['remove']
						),
						'remove',
						$this->createSummary( $params )
					);
			}
		}

		wfProfileOut( __METHOD__ );
		return $changeOps;
	}

	/**
	 * @see ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'invalid-list', 'info' => $this->msg( 'wikibase-api-invalid-list' )->text() ),
			array( 'code' => 'no-such-entity', 'info' => $this->msg( 'wikibase-api-no-such-entity' )->text() ),
		) );
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
