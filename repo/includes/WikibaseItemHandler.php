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
			CONTENT_FORMAT_JSON,
			CONTENT_FORMAT_SERIALIZED
		);

		parent::__construct( CONTENT_MODEL_WIKIBASE_ITEM, $formats );
	}

	/**
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getDefaultFormat() {
		return WBSettings::get( 'serializationFormat' );
	}

	/**
	 * @param Content $content
	 * @param null|string $format
	 *
	 * @return string
	 */
	public function serializeContent( Content $content, $format = null ) {

		if ( is_null( $format ) ) {
			$format = WBSettings::get( 'serializationFormat' );
		}

		#FIXME: assert $content is a WikibaseContent instance
		$data = $content->getNativeData();

		switch ( $format ) {
			case CONTENT_FORMAT_SERIALIZED:
				$blob = serialize( $data );
				break;
			case CONTENT_FORMAT_JSON:
				$blob = json_encode( $data );
				break;
			default:
				throw new MWException( "serialization format $format is not supported for Wikibase content model" );
				break;
		}

		return $blob;
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
			case CONTENT_FORMAT_SERIALIZED:
				$data = unserialize( $blob ); #FIXME: suppress notice on failed serialization!
				break;
			case CONTENT_FORMAT_JSON:
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

