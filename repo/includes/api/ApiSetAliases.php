<?php

namespace Wikibase;
use ApiBase, User, Language;

/**
 * API module to set the aliases for a Wikibase entity.
 * Requires API write mode to be enabled.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ApiSetAliases extends ApiModifyEntity {

	/**
	 * @see  ApiModifyEntity::getRequiredPermissions()
	 */
	protected function getRequiredPermissions( Entity $entity, array $params ) {
		$permissions = parent::getRequiredPermissions( $entity, $params );

		if ( isset( $params['add'] ) ) {
			$permissions[] = 'alias-add';
		}
		if ( isset( $params['set'] ) ) {
			$permissions[] = 'alias-set';
		}
		if ( isset( $params['remove'] ) ) {
			$permissions[] = 'alias-remove';
		}
		return $permissions;
	}

	/**
	 * @see ApiModifyEntity::validateParameters()
	 */
	protected function validateParameters( array $params ) {
		parent::validateParameters( $params );

		if ( !( ( isset( $params['add'] ) || isset( $params['remove'] ) ) XOR isset( $params['set'] ) ) ) {
			$this->dieUsage( $this->msg( 'wikibase-api-aliases-invalid-list' )->text(), 'aliases-invalid-list' );
		}
	}

	/**
	 * @see  ApiModifyEntity::getTextForComment()
	 */
	protected function getTextForComment( array $params, $plural = 1 ) {
		return Autocomment::formatAutoComment(
			'wbsetaliases-' . implode( '-', Autocomment::pickKeysFromParams( $params, 'set', 'add', 'remove' ) ),
			array( $plural, $params['language'] )
		);
	}

	/**
	 * @see  ApiModifyEntity::getTextForSummary()
	 */
	protected function getTextForSummary( array $params ) {
		return Autocomment::formatAutoSummary(
			Autocomment::pickValuesFromParams( $params, 'set', 'add', 'remove' )
		);
	}

	/**
	 * @see ApiModifyEntity::createEntity()
	 */
	protected function createEntity( array $params ) {
		$this->dieUsage( $this->msg( 'wikibase-api-no-such-item' )->text(), 'no-such-item' );
	}

	/**
	 * @see ApiModifyEntity::modifyEntity()
	 */
	protected function modifyEntity( EntityContent &$entityContent, array $params ) {
		if ( isset( $params['set'] ) ) {
			$entityContent->getEntity()->setAliases(
				$params['language'],
				array_map(
					function( $str ) { return Utils::squashToNFC( $str ); },
					$params['set']
				)
			);
		}

		if ( isset( $params['remove'] ) ) {
			$entityContent->getEntity()->removeAliases(
				$params['language'],
				array_map(
					function( $str ) { return Utils::squashToNFC( $str ); },
					$params['remove']
				)
			);
		}

		if ( isset( $params['add'] ) ) {
			$entityContent->getEntity()->addAliases(
				$params['language'],
				array_map(
					function( $str ) { return Utils::squashToNFC( $str ); },
					$params['add']
				)
			);
		}

		$aliases = $entityContent->getEntity()->getAliases( $params['language'] );
		if ( count( $aliases ) ) {
			$this->addAliasesToResult( array( $params['language'] => $aliases ), 'entity' );
		}

		return true;
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
				'add' => 'List of aliases to add',
				'remove' => 'List of aliases to remove',
				'set' => 'A list of aliases that will replace the current list',
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
			'api.php?action=wbsetaliases&language=en&id=1&set=Foo|Bar'
				=> 'Set the English labels for the entity with id 1 to Foo and Bar',

			'api.php?action=wbsetaliases&language=en&id=1&add=Foo|Bar'
				=> 'Add Foo and Bar to the list of English labels for the entity with id 1',

			'api.php?action=wbsetaliases&language=en&id=1&remove=Foo|Bar'
				=> 'Remove Foo and Bar from the list of English labels for the entity with id 1',
		);
	}

	/**
	 * @see ApiBase::getHelpUrls()
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbsetaliases';
	}


	/**
	 * @see ApiBase::getVersion()
	 */
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
