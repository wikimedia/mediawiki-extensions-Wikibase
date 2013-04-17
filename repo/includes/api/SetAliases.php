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

		if ( isset( $params['add'] ) || isset( $params['set'] ) ) {
			// add and set has a common permission due to the special page SetAliases
			$permissions[] = 'alias-update';
		}
		if ( isset( $params['remove'] ) ) {
			$permissions[] = 'alias-remove';
		}
		return $permissions;
	}

	/**
	 * @see \Wikibase\Api\ModifyEntity::validateParameters()
	 */
	protected function validateParameters( array $params ) {
		parent::validateParameters( $params );

		if ( !( ( isset( $params['add'] ) || isset( $params['remove'] ) ) XOR isset( $params['set'] ) ) ) {
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
					function( $str ) { return Utils::trimToNFC( $str ); },
					$params['set']
				)
			);

		} else {

			// Set the action to set in case we add and remove entries in a single edit.
			// The new list of aliases will be added to the summary after the changes happend.
			if (
				isset( $params['add'] ) && count( $params['add'] ) &&isset( $params['remove'] ) && count( $params['remove'] )
			) {
				$summary->setAction( 'set' );
			}

			if ( isset( $params['add'] ) && count( $params['add'] ) ) {
				if ( $summary->getActionName() === null ) {
					$summary->setAction( 'add' );
					$summary->addAutoSummaryArgs( $params['add'] );
				}
				$entityContent->getEntity()->addAliases(
					$params['language'],
					array_map(
						function( $str ) { return Utils::trimToNFC( $str ); },
						$params['add']
					)
				);
			}

			if ( isset( $params['remove'] ) && count( $params['remove'] ) ) {
				if ( $summary->getActionName() === null ) {
					$summary->setAction( 'remove' );
					$summary->addAutoSummaryArgs( $params['remove'] );
				}
				$entityContent->getEntity()->removeAliases(
					$params['language'],
					array_map(
						function( $str ) { return Utils::trimToNFC( $str ); },
						$params['remove']
					)
				);
			}

			if ( $summary->getActionName() === 'set' ) {
				$summary->addAutoSummaryArgs(
					$entityContent->getEntity()->getAliases( $params['language'] )
				);
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
				'add' => 'List of aliases to add',
				'remove' => 'List of aliases to remove',
				'set' => 'A list of aliases that will replace the current list (can not be combined with either add or remove)',
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
				=> 'Set the English labels for the entity with id q1 to Foo and Bar',

			'api.php?action=wbsetaliases&language=en&id=q1&add=Foo|Bar'
				=> 'Add Foo and Bar to the list of English labels for the entity with id q1',

			'api.php?action=wbsetaliases&language=en&id=q1&remove=Foo|Bar'
				=> 'Remove Foo and Bar from the list of English labels for the entity with id q1',
		);
	}

	/**
	 * @see ApiBase::getHelpUrls()
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbsetaliases';
	}

	/**
	 * @see ApiBase::getVersion
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . '-' . WB_VERSION;
	}

}
