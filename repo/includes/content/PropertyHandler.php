<?php

namespace Wikibase;

/**
 * Content handler for Wikibase items.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyHandler extends EntityHandler {

	/**
	 * @see EntityHandler::getContentClass
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	protected function getContentClass() {
		return '\Wikibase\PropertyContent';
	}

	/**
	 * @param PreSaveValidator[] $preSaveValidators
	 */
	public function __construct( $preSaveValidators ) {
		parent::__construct( CONTENT_MODEL_WIKIBASE_PROPERTY, $preSaveValidators );
	}

	/**
	 * @return array
	 */
	public function getActionOverrides() {
		return array(
			'history' => '\Wikibase\HistoryPropertyAction',
			'view' => '\Wikibase\ViewPropertyAction',
			'edit' => '\Wikibase\EditPropertyAction',
			'submit' => '\Wikibase\SubmitPropertyAction',
		);
	}

	/**
	 * @see ContentHandler::unserializeContent
	 *
	 * @since 0.1
	 *
	 * @param string $blob
	 * @param null|string $format
	 *
	 * @return PropertyContent
	 */
	public function unserializeContent( $blob, $format = null ) {
		$entity = EntityFactory::singleton()->newFromBlob( Property::ENTITY_TYPE, $blob, $format );
		return PropertyContent::newFromProperty( $entity );
	}

	/**
	 * @see EntityHandler::getSpecialPageForCreation
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getSpecialPageForCreation() {
		return 'NewProperty';
	}

	/**
	 * Returns Property::ENTITY_TYPE
	 *
	 * @return string
	 */
	public function getEntityType() {
		return Property::ENTITY_TYPE;
	}
}

