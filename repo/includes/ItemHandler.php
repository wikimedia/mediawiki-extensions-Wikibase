<?php

namespace Wikibase;

/**
 * Content handler for Wikibase items.
 *
 * @since 0.1
 *
 * @file WikibaseItemHandler.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemHandler extends EntityHandler {

	/**
	 * @return Item
	 */
	public function makeEmptyContent() {
		return Item::newEmpty();
	}

	public function __construct() {
		parent::__construct( CONTENT_MODEL_WIKIBASE_ITEM );
	}

	/**
	 * @return array
	 */
	public function getActionOverrides() {
		return array(
			'view' => '\Wikibase\ViewItemAction',
			'edit' => '\Wikibase\EditItemAction',
		);
	}

	/**
	 * @param string $blob
	 * @param null|string $format
	 *
	 * @return Item
	 */
	public function unserializeContent( $blob, $format = null ) {
		return Item::newFromArray( $this->unserializedData( $blob, $format ) );
	}

	/**
	 * @see ContentHandler::getDeletionUpdates
	 *
	 * @param \Content           $content
	 * @param \Title             $title
	 * @param null|\ParserOutput $parserOutput
	 *
	 * @return array of \DataUpdate
	 */
	public function getDeletionUpdates( Content $content, Title $title, ParserOutput $parserOutput = null ) {
		return array_merge(
			parent::getDeletionUpdates( $content, $title, $parserOutput ),
			array( new ItemDeletionUpdate( $content ) )
		);
	}

}

