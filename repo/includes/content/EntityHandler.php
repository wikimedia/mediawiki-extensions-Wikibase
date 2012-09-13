<?php

namespace Wikibase;
use MWException, WikiPage, Title, Content;

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

		parent::__construct( $modelId, $formats );
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
	 * @see ContentHandler::canBeUsedOn();
	 *
	 * This implementation returns true if and only if the given title's namespace
	 * is the same as the one returned by $this->getEntityNamespace().
	 *
	 * @return bool true if $title represents a page in the appropriate entity namespace.
	 */
	public function canBeUsedOn( Title $title ) {
		$ns = $this->getEntityNamespace();
		return $ns === $title->getNamespace();
	}


	/**
	 * Returns the Title object for the item with provided id.
	 *
	 * TODO: refactor to work for all entities
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
	 * TODO: refactor to work for all entities
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
	 * TODO: refactor to work for all entities
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
	 * Get the item with the provided revision id, or null if there is no such item.
	 *
	 * Note that this returns an old entity that may not be valid anymore.
	 *
	 * @since 0.1
	 *
	 * @param integer $entityId
	 *
	 * @return ItemContent|null
	 */
	public function getFromRevision( $revisionId ) {
		$revision = \Revision::newFromId( intval( $revisionId ) );

		if ( $revision === null ) {
			return null;
		}

		return $revision->getContent();
	}

	/**
	 * Get the items corresponding to the provided language and label pair.
	 * A description can also be provided, in which case only the item with
	 * that description will be returned (as only element in the array).
	 *
	 * TODO: refactor to work for all entities
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
		$ids = StoreFactory::getStore()->newTermLookup()->getItemIdsForLabel( $label, $language, $description );
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

	/**
	 * @see Content::getPageViewLanguage
	 *
	 * This implementation returns the user language which is same as content language here
	 *
	 * @param Title        $title the page to determine the language for.
	 * @param Content|null $content the page's content, if you have it handy, to avoid reloading it.
	 *
	 * @return \Language the page's language
	 */
	public function getPageViewLanguage( Title $title, Content $content = null ) {
		global $wgLang;
		return $wgLang;
	}

}
