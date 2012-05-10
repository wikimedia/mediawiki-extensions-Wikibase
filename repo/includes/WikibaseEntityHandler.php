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
abstract class WikibaseEntityHandler extends ContentHandler {

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

