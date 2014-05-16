<?php

namespace Wikibase;

use Wikibase\Store\EntityContentDataCodec;
use Wikibase\Validators\EntityValidator;

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
	 * @param \Wikibase\Store\EntityContentDataCodec $contentCodec
	 * @param EntityValidator[] $preSaveValidators
	 */
	public function __construct( EntityContentDataCodec $contentCodec, $preSaveValidators ) {
		parent::__construct( CONTENT_MODEL_WIKIBASE_PROPERTY, $contentCodec, $preSaveValidators );
	}

	/**
	 * @see EntityHandler::newContent
	 *
	 * @since 0.5
	 *
	 * @param Entity $property An Property object
	 *
	 * @throws InvalidArgumentException
	 * @return PropertyContent
	 */
	protected function newContent( Entity $property ) {
		if ( ! $property instanceof Property ) {
			throw new \InvalidArgumentException( '$property must be an instance of Property' );
		}

		return PropertyContent::newFromProperty( $property );
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

