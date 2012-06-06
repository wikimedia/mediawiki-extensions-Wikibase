<?php

namespace Wikibase;
use User, Title, WikiPage, Content, ParserOptions, ParserOutput, RequestContext;

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
		global $wgRequest;

		# construct dummy context for the dummy view
		$context = new RequestContext( $wgRequest );
		$context->setTitle( $title );

		$itemView = new ItemView( $this, $context );
		return $itemView->render();
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
	 * @param \WikiPage $page
	 *
	 * @return array of \DataUpdate
	 */
	public function getDeletionUpdates( \WikiPage $page ) {
		return array_merge(
			parent::getDeletionUpdates( $page ),
			array( new ItemDeletionUpdate( $page->getContent() ) )
		);
	}

}

