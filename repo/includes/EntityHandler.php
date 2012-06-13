<?php

namespace Wikibase;
use MWException;

/**
 * Base handler class for Wikibase\Entity content classes.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class EntityHandler extends \ContentHandler {

	public function __construct( $modelId ) {
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
		return Settings::get( 'serializationFormat' );
	}

	/**
	 * @param \Content $content
	 * @param null|string $format
	 *
	 * @throws MWException
	 * @return string
	 */
	public function serializeContent( \Content $content, $format = null ) {

		if ( is_null( $format ) ) {
			$format = $this->getDefaultFormat();
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

	/**
	 * @param $blob
	 * @param null $format
	 * @return mixed
	 *
	 * @throws MWException
	 * @throws \MWContentSerializationException
	 */
	protected function unserializedData( $blob, $format = null ) {
		if ( is_null( $format ) ) {
			$format = $this->getDefaultFormat();
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
			throw new \MWContentSerializationException( 'failed to deserialize' );
		}

		return $data;
	}

}

