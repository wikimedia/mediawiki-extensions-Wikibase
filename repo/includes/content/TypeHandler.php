<?php

namespace Wikibase;
use Title, Content, ParserOptions, ParserOutput;

/**
 * Content handler for Wikibase items.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TypeHandler extends EntityHandler {

	/**
	 * Returns an instance of the TypeHandler.
	 *
	 * @since 0.1
	 *
	 * @return TypeHandler
	 */
	public static function singleton() {
		static $instance = false;

		if ( $instance === false ) {
			$instance = new static();
		}

		return $instance;
	}

	/**
	 * @see ContentHandler::makeEmptyContent
	 *
	 * @since 0.1
	 *
	 * @return TypeContent
	 */
	public function makeEmptyContent() {
		return TypeContent::newEmpty();
	}

	public function __construct() {
		parent::__construct( CONTENT_MODEL_WIKIBASE_TYPE );
	}

	/**
	 * @return array
	 */
	public function getActionOverrides() {
		return array(
			'view' => '\Wikibase\ViewTypeAction',
			'edit' => '\Wikibase\EditTypeAction',
			'submit' => '\Wikibase\SubmitTypeAction',
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
	 * @return TypeContent
	 */
	public function unserializeContent( $blob, $format = null ) {
		return TypeContent::newFromArray( $this->unserializedData( $blob, $format ) );
	}

	/**
	 * @see ContentHandler::getDiffEngineClass
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
//	protected function getDiffEngineClass() {
//		return '\Wikibase\TypeDiffView';
//	}

	/**
	 * @see EntityHandler::getEntityPrefix
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getEntityPrefix() {
		return Settings::get( 'typePrefix' );
	}

	/**
	 * @see EntityHandler::getEntityNamespace
	 *
	 * @since 0.1
	 *
	 * @return integer
	 */
	public function getEntityNamespace() {
		return WB_NS_TYPE;
	}

}

