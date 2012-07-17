<?php

namespace Wikibase;
use MWException, WikiPage, Title;

/**
 * Base handler class for Wikibase\Entity content classes.
 * TODO: interface for enforcing singleton
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

	/**
	 * @since 0.1
	 *
	 * @return string
	 */
	public abstract function getEntityPrefix();

	/**
	 * @since 0.1
	 *
	 * @return integer
	 */
	public abstract function getEntityNamespace();

	/**
	 * Returns the Title object for the item with provided id.
	 *
	 * @since 0.1
	 *
	 * @param integer $entityId
	 *
	 * @throws MWException
	 * @return Title
	 */
	public function getTitleForId( $entityId ) {
		$id = intval( $entityId );

		if ( $id <= 0 ) {
			throw new MWException( 'itemId must be a positive integer, not ' . var_export( $entityId , true ) );
		}

		return Title::newFromText( $this->getEntityPrefix() . $id, $this->getEntityNamespace() );
	}

	/**
	 * Returns the WikiPage object for the item with provided id.
	 *
	 * @since 0.1
	 *
	 * @param integer $entityId
	 *
	 * @return WikiPage
	 */
	public function getWikiPageForId( $entityId ) {
		return new WikiPage( $this->getTitleForId( $entityId ) );
	}

	/**
	 * Get the item with the provided id, or null if there is no such item.
	 *
	 * @since 0.1
	 *
	 * @param integer $entityId
	 *
	 * @return ItemContent|null
	 */
	public function getFromId( $entityId ) {
		// TODO: since we already did the trouble of getting a WikiPage here,
		// we probably want to keep a copy of it in the Content object.
		return $this->getWikiPageForId( $entityId )->getContent();
	}

	/**
	 * Get the items corresponding to the provided language and label pair.
	 * A description can also be provided, in which case only the item with
	 * that description will be returned (as only element in the array).
	 *
	 * @since 0.1
	 *
	 * @param string $language
	 * @param string $label
	 * @param string|null $description
	 *
	 * @return array of ItemContent
	 */
	public function getFromLabel( $language, $label, $description = null ) {
		$ids = $this->getIdsForLabel( $language, $label, $description );
		$items = array();

		foreach ( $ids as $id ) {
			$item = self::getFromId( $id );

			if ( !is_null( $item ) ) {
				$items[] = $item;
			}
		}

		return $items;
	}

	/**
	 * Get the ids of the items corresponding to the provided language and label pair.
	 * A description can also be provided, in which case only the id of the item with
	 * that description will be returned (as only element in the array).
	 *
	 * @since 0.1
	 *
	 * @param string $language
	 * @param string $label
	 * @param string|null $description
	 *
	 * @return array of integer
	 */
	public function getIdsForLabel( $language, $label, $description = null ) {
		$dbr = wfGetDB( DB_SLAVE );

		$conds = array(
			'tpl_language' => $language,
			'tpl_label' => $label
		);

		if ( !is_null( $description ) ) {
			$conds['tpl_description'] = $description;
		}

		$items = $dbr->select(
			'wb_texts_per_lang',
			array( 'tpl_item_id' ),
			$conds,
			__METHOD__
		);

		return array_map( function( $item ) { return $item->tpl_item_id; }, iterator_to_array( $items ) );
	}

	/**
	 * Returns false to indicate that the parser cache should not be used for data items.
	 * The html representation of Items depends on the user language, splitting the parser
	 * cache by user language is currently problematic and would need some core changes.
	 *
	 * @see ContentHandler::isParserCacheSupported
	 *
	 * @since 0.1
	 *
	 * @return bool false
	 */
	public function isParserCacheSupported() {
		return false;
	}

}

