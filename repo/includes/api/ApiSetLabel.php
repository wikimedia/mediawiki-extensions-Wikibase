<?php

namespace Wikibase;
use ApiBase;

/**
 * API module to set the label for a Wikibase entity.
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
class ApiSetLabel extends ApiModifyLangAttribute {

	/**
	 * @see ApiModifyEntity::getRequiredPermissions()
	 */
	protected function getRequiredPermissions( Entity $entity, array $params ) {
		$permissions = parent::getRequiredPermissions( $entity, $params );

		$permissions[] = ( isset( $params['value'] ) && 0<strlen( $params['value'] ) )
			? 'label-update'
			: 'label-remove';
		return $permissions;
	}

	/**
	 * @see ApiModifyEntity::modifyEntity()
	 */
	protected function modifyEntity( EntityContent &$entityContent, array $params ) {
		if ( isset( $params['value'] ) ) {
			$label = Utils::squashToNFC( $params['value'] );
			$language = $params['language'];
			if ( 0 < strlen( $label ) ) {
				$labels = array( $language => $entityContent->getEntity()->setLabel( $language, $label ) );
			}
			else {
				$entityContent->getEntity()->removeLabel( $language );
				$labels = array( $language => '' );
			}

			$this->addLabelsToResult( $labels, 'entity' );
		}

		return true;
	}

	/**
	 * @see ApiBase::getDescription()
	 */
	public function getDescription() {
		return array(
			'API module to set a label for a single Wikibase entity.'
		);
	}

	/**
	 * @see ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
		) );
	}

	/**
	 * @see ApiBase::getExamples()
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbsetlabel&id=42&language=en&value=Wikimedia&format=jsonfm'
				=> 'Set the string "Wikimedia" for page with id "42" as a label in English language and report it as pretty printed json',
		);
	}

	/**
	 * @see ApiBase::getHelpUrls()
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbsetlabel';
	}

	/**
	 * @see ApiBase::getVersion()
	 */
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
