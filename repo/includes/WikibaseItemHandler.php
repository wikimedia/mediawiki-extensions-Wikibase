<?php

/**
 *
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
class WikibaseItemHandler extends WikibaseEntityHandler {

	/**
	 * FIXME: bad method name
	 *
	 * @return WikibaseItem
	 */
	public function makeEmptyContent() {
		return WikibaseItem::newEmpty();
	}

	public function __construct() {
		parent::__construct( CONTENT_MODEL_WIKIBASE_ITEM );
	}

	/**
	 * @return array
	 */
	public function getActionOverrides() {
		return array(
			'view' => 'WikibaseViewItemAction',
			'edit' => 'WikibaseEditItemAction',
		);
	}

	/**
	 * @param string $blob
	 * @param null|string $format
	 *
	 * @return WikibaseItem
	 */
	public function unserializeContent( $blob, $format = null ) {
		return WikibaseItem::newFromArray( $this->unserializedData( $blob, $format ) );
	}

}

