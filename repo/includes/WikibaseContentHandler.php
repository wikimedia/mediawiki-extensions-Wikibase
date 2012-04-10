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

	public function getDifferenceEngine( IContextSource $context, $old = 0, $new = 0, $rcid = 0, $refreshCache = false, $unhide = false 	) {
		return new WikibaseDifferenceEngine( $context, $old, $new, $rcid, $refreshCache, $unhide );
	}

	public function __construct() {
		$formats = array(
			'application/json',
			'application/vnd.php.serialized' #FIXME: find out what mime type the api uses for serialized php objects
		);

		parent::__construct( CONTENT_MODEL_WIKIDATA, $formats );
	}

	public function createArticle( Title $title ) {
		//$this->checkModelName( $title->getContentModelName() );

		$article = new WikibasePage( $title );
		return $article;
	}

	public function getDefaultFormat() {
		return WDRSettings::get( 'serializationFormat' );
	}

	/**
	 * @param Content $content
	 * @param null|String $format
	 *
	 * @return String
	 */
	public function serialize( Content $content, $format = null ) {

		if ( is_null( $format ) ) {
			$format = WDRSettings::get( 'serializationFormat' );
		}

		#FIXME: assert $content is a WikibaseContent instance
		$data = $content->getNativeData();

		if ( $format == 'application/vnd.php.serialized' ) $blob = serialize( $data );
		else if ( $format == 'application/json' ) $blob = json_encode( $data );
		else throw new MWException( "serialization format $format is not supported for Wikibase content model" );

		return $blob;
	}

	/**
	 * @param $blob String
	 * @param null|String $format
	 *
	 * @return WikibaseContent
	 */
	public function unserialize( $blob, $format = null ) {
		if ( is_null( $format ) ) {
			$format = WDRSettings::get( 'serializationFormat' );
		}

		if ( $format == 'application/vnd.php.serialized' ) $data = unserialize( $blob ); #FIXME: suppress notice on failed serialization!
		else if ( $format == 'application/json' ) $data = json_decode( $blob, true ); #FIXME: suppress notice on failed serialization!
		else throw new MWException( "serialization format $format is not supported for Wikibase content model" );

		if ( $data === false || $data === null ) throw new MWContentSerializationException("failed to deserialize");

		return new WikibaseContent( $data );
	}

	/**
	 * @return WikibaseContent
	 */
	public function emptyContent() {
		return new WikibaseContent( array() );
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

