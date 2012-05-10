<?php

/**
 * Class representing a Wikibase page.
 *
 * @since 0.1
 *
 * @file WikibaseContentHandler.php
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
		$formats = array(
			'application/json',
			'application/vnd.php.serialized' #FIXME: find out what mime type the api uses for serialized php objects
		);

		parent::__construct( CONTENT_MODEL_WIKIBASE_ITEM, $formats );
	}

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
		if ( is_null( $format ) ) {
			$format = WBSettings::get( 'serializationFormat' );
		}

		switch ( $format ) {
			case 'application/vnd.php.serialized':
				$data = unserialize( $blob ); #FIXME: suppress notice on failed serialization!
				break;
			case 'application/json':
				$data = json_decode( $blob, true ); #FIXME: suppress notice on failed serialization!
				break;
			default:
				throw new MWException( "serialization format $format is not supported for Wikibase content model" );
				break;
		}

		if ( $data === false || $data === null ) {
			throw new MWContentSerializationException( 'failed to deserialize' );
		}

		return WikibaseItem::newFromArray( $data );
	}

}

