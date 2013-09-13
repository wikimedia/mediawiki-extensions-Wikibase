<?php

namespace Wikibase\Api;

use ApiBase;
use Wikibase\Entity;
use Wikibase\EntityContent;
use Wikibase\Utils;
use Wikibase\ChangeOpLabel;

/**
 * API module to set the label for a Wikibase entity.
 * Requires API write mode to be enabled.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class SetLabel extends ModifyLangAttribute {

	/**
	 * @see \Wikibase\Api\ModifyEntity::getRequiredPermissions()
	 */
	protected function getRequiredPermissions( EntityContent $entityContent, array $params ) {
		$permissions = parent::getRequiredPermissions( $entityContent, $params );

		$permissions[] = ( isset( $params['value'] ) && 0 < strlen( $params['value'] ) )
			? 'label-update'
			: 'label-remove';
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
		$labels = array( $language => ( $entity->getLabel( $language ) !== false ) ? $entity->getLabel( $language ) : "" );
		$this->addLabelsToResult( $labels, 'entity' );

		wfProfileOut( __METHOD__ );
		return $summary;
	}

	/**
	 * @since 0.4
	 *
	 * @param array $params
	 * @return ChangeOpLabel
	 */
	protected function getChangeOp( array $params ) {
		wfProfileIn( __METHOD__ );
		$changeOps = array();
		$label = "";
		$language = $params['language'];

		if ( isset( $params['value'] ) ) {
			$label = $this->stringNormalizer->trimToNFC( $params['value'] );
		}

		if ( $label === "" ) {
			wfProfileOut( __METHOD__ );
			return new ChangeOpLabel( $language, null );
		} else {
			wfProfileOut( __METHOD__ );
			return new ChangeOpLabel( $language, $label );
		}
	}

	/**
	 * @see \ApiBase::getParamDescription()
	 */
	public function getParamDescription() {
		return array_merge(
			parent::getParamDescription(),
			array(
				'language' => 'Language of the label',
				'value' => 'The value of the label',
			)
		);
	}

	/**
	 * @see \ApiBase::getDescription()
	 */
	public function getDescription() {
		return array(
			'API module to set a label for a single Wikibase entity.'
		);
	}

	/**
	 * @see \ApiBase::getExamples()
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbsetlabel&id=Q42&language=en&value=Wikimedia&format=jsonfm'
				=> 'Set the string "Wikimedia" for page with id "Q42" as a label in English language and report it as pretty printed json',
			'api.php?action=wbsetlabel&site=enwiki&title=Earth&language=en&value=Earth'
				=> 'Set the English language label to "Earth" for the item with site link enwiki => "Earth".',
		);
	}

}
