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
 */
class WikibaseContentHandler extends ContentHandler {

	/**
	 * FIXME: bad method name
	 *
	 * @return WikibaseItem
	 */
	public function emptyContent() {
		return WikibaseItem::newEmpty();
	}

	public function __construct() {
		$formats = array(
			'application/json',
			'application/vnd.php.serialized' #FIXME: find out what mime type the api uses for serialized php objects
		);

		parent::__construct( CONTENT_MODEL_WIKIBASE, $formats );
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
	public function serialize( Content $content, $format = null ) {

		if ( is_null( $format ) ) {
			$format = WBSettings::get( 'serializationFormat' );
		}

		#FIXME: assert $content is a WikibaseContent instance
		$data = $content->getNativeData();

		switch ( $format ) {
			case 'application/vnd.php.serialized':
				$blob = serialize( $data );
				break;
			case 'application/json':
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
	public function unserialize( $blob, $format = null ) {
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

	public static function flattenArray( $a, $prefix = '', &$into = null ) {
		if ( is_null( $into ) ) {
			$into = array();
		}

		foreach ( $a as $k => $v ) {
			if ( is_object( $v ) ) {
				$v = get_object_vars( $v );
			}

			if ( is_array( $v ) ) {
				WikibaseContentHandler::flattenArray( $v, "$prefix$k | ", $into );
			} else {
				$into[ "$prefix$k" ] = $v;
			}
		}

		return $into;
	}
}

