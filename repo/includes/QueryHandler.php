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
//			'view' => '\Wikibase\ViewQueryAction',
//			'edit' => '\Wikibase\EditQueryAction',
		);
	}

	/**
	 * Returns a ParserOutput object containing the HTML.
	 *
	 * @since 0.1
	 *
	 * @param Title $title
	 * @param null $revId
	 * @param null|ParserOptions $options
	 * @param bool $generateHtml
	 *
	 * @return ParserOutput
	 */
	public function getParserOutput( Content $content, Title $title, $revId = null, ParserOptions $options = null, $generateHtml = true )  {
		$parserOutput = new ParserOutput();

		$parserOutput->setText( 'TODO' ); // TODO

		return $parserOutput;
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
	 * @see ContentHandler::getDeletionUpdates
	 *
	 * @param Content $content
	 * @param Title $title
	 * @param null|ParserOutput $parserOutput
	 *
	 * @since 0.1
	 *
	 * @return array of \DataUpdate
	 */
	public function getDeletionUpdates( Content $content, Title $title, ParserOutput $parserOutput = null ) {
		return array_merge(
			parent::getDeletionUpdates( $content, $title, $parserOutput ),
			array( /* new QueryDeletionUpdate( $content ) */ )
		);
	}

	/**
	 * @see ContentHandler::getSecondaryDataUpdates
	 *
	 * @since 0.1
	 *
	 * @param Content $content
	 * @param Title $title
	 * @param Content|null $old
	 * @param boolean $recursive
	 *
	 * @param null|ParserOutput $parserOutput
	 *
	 * @return array of \DataUpdate
	 */
	public function getSecondaryDataUpdates( Content $content, Title $title, Content $old = null,
											 $recursive = false, ParserOutput $parserOutput = null ) {

		return array_merge(
			parent::getSecondaryDataUpdates( $content, $title, $old, $recursive, $parserOutput ),
			array( /* new QueryStructuredSave( $content ) */ )
		);
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

