<?php

namespace Wikibase\Api;

use ApiBase, User, Language;

use Wikibase\Entity;
use Wikibase\EntityContent;
use Wikibase\Autocomment;
use Wikibase\Utils;

/**
 * API module to set the aliases for a Wikibase entity.
 * Requires API write mode to be enabled.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseRepo
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class SetAliases extends ModifyEntity {

	/**
	 * @see  \Wikibase\Api\ModifyEntity::getRequiredPermissions()
	 */
	protected function getRequiredPermissions( Entity $entity, array $params ) {
		$permissions = parent::getRequiredPermissions( $entity, $params );

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
			$this->dieUsage( $this->msg( 'wikibase-api-aliases-invalid-list' )->text(), 'aliases-invalid-list' );
		}
	}

	/**
	 * @see ApiModifyEntity::createEntity()
	 */
	protected function createEntity( array $params ) {
		$this->dieUsage( $this->msg( 'wikibase-api-no-such-entity' )->text(), 'no-such-item' );
	}

	/**
	 * @see \Wikibase\Api\ModifyEntity::modifyEntity()
	 */
	protected function modifyEntity( EntityContent &$entityContent, array $params ) {
		wfProfileIn( __METHOD__ );

		$summary = $this->createSummary( $params );
		$summary->setLanguage( $params['language'] );

		// Set the list of aliases to a user given one OR add/ remove certain entries
		if ( isset( $params['set'] ) ) {
			$summary->setAction( 'set' );
			$summary->addAutoSummaryArgs( $params['set'] );
			$entityContent->getEntity()->setAliases(
				$params['language'],
				array_map(
					function( $str ) { return $this->stringNormalizer->trimToNFC( $str ); },
					$params['set']
				)
			);

		} else {

			if ( !empty( $params['add'] ) ) {
				$entityContent->getEntity()->addAliases(
					$params['language'],
					array_map(
						function( $str ) { return $this->stringNormalizer->trimToNFC( $str ); },
						$params['add']
					)
				);
			}

			if ( !empty( $params['remove'] ) ) {
				$entityContent->getEntity()->removeAliases(
					$params['language'],
					array_map(
						function( $str ) { return $this->stringNormalizer->trimToNFC( $str ); },
						$params['remove']
					)
				);
			}

			// Set the action to set in case we add and remove entries in a single edit.
			if ( !empty( $params['add'] ) && !empty( $params['remove'] ) ) {
				$summary->setAction( 'set' );
				// Get the full list of current aliases
				$summary->addAutoSummaryArgs(
					$entityContent->getEntity()->getAliases( $params['language'] )
				);
			} elseif ( !empty( $params['add'] ) ) {
				$summary->setAction( 'add' );
				$summary->addAutoSummaryArgs( $params['add'] );
			} elseif ( !empty( $params['remove'] ) ) {
				$summary->setAction( 'remove' );
				$summary->addAutoSummaryArgs( $params['remove'] );
			}

		}

		$aliases = $entityContent->getEntity()->getAliases( $params['language'] );
		if ( count( $aliases ) ) {
			$this->addAliasesToResult( array( $params['language'] => $aliases ), 'entity' );
		}

		wfProfileOut( __METHOD__ );
		return $summary;
	}

	/**
	 * @see ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'aliases-invalid-list', 'info' => $this->msg( 'wikibase-api-aliases-invalid-list' )->text() ),
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
			'api.php?action=wbsetaliases&language=en&id=q1&set=Foo|Bar'
				=> 'Set the English aliases for the entity with id q1 to Foo and Bar',

			'api.php?action=wbsetaliases&language=en&id=q1&add=Foo|Bar'
				=> 'Add Foo and Bar to the list of English aliases for the entity with id q1',

			'api.php?action=wbsetaliases&language=en&id=q1&remove=Foo|Bar'
				=> 'Remove Foo and Bar from the list of English aliases for the entity with id q1',

			'api.php?action=wbsetaliases&language=en&id=q1&remove=Foo&add=Bar'
				=> 'Remove Foo from the list of English aliases for the entity with id q1 while adding Bar to it',
		);
	}

}
