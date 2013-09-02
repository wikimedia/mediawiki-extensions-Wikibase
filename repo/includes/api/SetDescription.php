<?php

namespace Wikibase\Api;

use ApiBase;

use Wikibase\Entity;
use Wikibase\EntityContent;
use Wikibase\Utils;
use Wikibase\ChangeOpDescription;

/**
 * API module for the language attributes for a Wikibase entity.
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
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class SetDescription extends ModifyLangAttribute {

	/**
	 * @see  \Wikibase\Api\ModifyEntity::getRequiredPermissions()
	 */
	protected function getRequiredPermissions( Entity $entity, array $params ) {
		$permissions = parent::getRequiredPermissions( $entity, $params );

		$permissions[] = ( isset( $params['value'] ) && 0<strlen( $params['value'] ) )
			? 'description-update'
			: 'description-remove';
		return $permissions;
	}

	/**
	 * @see \Wikibase\Api\ModifyEntity::modifyEntity()
	 */
	protected function modifyEntity( EntityContent &$entityContent, array $params ) {
		wfProfileIn( __METHOD__ );
		$summary = $this->createSummary( $params );
		$entity = $entityContent->getEntity();
		$language = $params['language'];

		$this->getChangeOp( $params )->apply( $entity, $summary );
		$descriptions = array( $language => ( $entity->getDescription( $language ) !== false ) ? $entity->getDescription( $language ) : "" );
		$this->addDescriptionsToResult( $descriptions, 'entity' );

		wfProfileOut( __METHOD__ );
		return $summary;
	}

	/**
	 * @since 0.4
	 *
	 * @param array $params
	 * @return ChangeOpDescription
	 */
	protected function getChangeOp( array $params ) {
		wfProfileIn( __METHOD__ );
		$description = "";
		$language = $params['language'];

		if ( isset( $params['value'] ) ) {
			$description = $this->stringNormalizer->trimToNFC( $params['value'] );
		}

		if ( $description === "" ) {
			wfProfileOut( __METHOD__ );
			return new ChangeOpDescription( $language, null );
		} else {
			wfProfileOut( __METHOD__ );
			return new ChangeOpDescription( $language, $description );
		}
	}

	/**
	 * @see \ApiBase::getParamDescription()
	 */
	public function getParamDescription() {
		return array_merge(
			parent::getParamDescription(),
			array(
				'language' => 'Language of the description',
				'value' => 'The value to set for the description',
			)
		);
	}

	/**
	 * @see \ApiBase::getDescription()
	 */
	public function getDescription() {
		return array(
			'API module to set a description for a single Wikibase entity.'
		);
	}

	/**
	 * @see \ApiBase::getExamples()
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbsetdescription&id=Q42&language=en&value=An%20encyclopedia%20that%20everyone%20can%20edit'
				=> 'Set the string "An encyclopedia that everyone can edit" for page with id "Q42" as a description in English language',
			'api.php?action=wbsetdescription&site=enwiki&title=Wikipedia&language=en&value=An%20encyclopedia%20that%20everyone%20can%20edit'
				=> 'Set the string "An encyclopedia that everyone can edit" as a description in English language for page with a sitelink to enwiki:Wikipedia',
		);
	}

}
