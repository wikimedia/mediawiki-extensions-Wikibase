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
class QueryHandler extends EntityHandler {

	/**
	 * Returns an instance of the PropertyHandler.
	 *
	 * @since 0.1
	 *
	 * @return QueryHandler
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
	 * @return QueryContent
	 */
	public function makeEmptyContent() {
		return QueryContent::newEmpty();
	}

	public function __construct() {
		parent::__construct( CONTENT_MODEL_WIKIBASE_QUERY );
	}

	/**
	 * @return array
	 */
	public function getActionOverrides() {
		return array(
			'view' => '\Wikibase\ViewQueryAction',
			'edit' => '\Wikibase\EditQueryAction',
			'submit' => '\Wikibase\SubmitQueryAction',
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
	 * @return QueryContent
	 */
	public function unserializeContent( $blob, $format = null ) {
		return QueryContent::newFromArray( $this->unserializedData( $blob, $format ) );
	}

	/**
	 * @see ContentHandler::getDiffEngineClass
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
//	protected function getDiffEngineClass() {
//		return '\Wikibase\QueryDiffView';
//	}

	/**
	 * @see EntityHandler::getEntityPrefix
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getEntityPrefix() {
		return Settings::get( 'queryPrefix' );
	}

	/**
	 * @see EntityHandler::getEntityNamespace
	 *
	 * @since 0.1
	 *
	 * @return integer
	 */
	public function getEntityNamespace() {
		return WB_NS_QUERY;
	}

}

