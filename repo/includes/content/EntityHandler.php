<?php

namespace Wikibase;

use Content;
use ContentHandler;
use MWContentSerializationException;
use MWException;
use Revision;
use Title;
use WikiPage;

/**
 * Base handler class for Wikibase\Entity handler classes.
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class EntityHandler extends ContentHandler {

	/**
	 * Returns the name of the EntityContent deriving class.
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	abstract protected function getContentClass();

	public function __construct( $modelId ) {
		$formats = ArrayStructureCodec::getSupportedFormats();
		parent::__construct( $modelId, $formats );
	}

	/**
	 * @see ContentHandler::makeEmptyContent
	 *
	 * @since 0.1
	 *
	 * @return EntityContent
	 */
	public function makeEmptyContent() {
		$contentClass = $this->getContentClass();
		return $contentClass::newEmpty();
	}

	/**
	 * Creates a Content object for the given Entity object.
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 *
	 * @return EntityContent
	 */
	public function makeEntityContent( Entity $entity ) {
		$contentClass = $this->getContentClass();
		return new $contentClass( $entity );
	}

	/**
	 * Returns the default serialization format for entities, as defined by the
	 * 'serializationFormat' setting.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getDefaultFormat() {
		return Settings::get( 'serializationFormat' );
	}

	/**
	 * @param \Content    $content
	 * @param null|string $format
	 *
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public function serializeContent( Content $content, $format = null ) {
		if ( !( $content instanceof EntityContent ) ) {
			throw new \InvalidArgumentException( '$content must be an instance of EntityContent.' );
		}

		$data = $content->getNativeData();
		$blob = $this->serializeData( $data, $format );

		return $blob;
	}

	/**
	 * Encodes the given array structure into a blog using the
	 * given serialization format.
	 *
	 * @param array $data
	 * @param null|string $format
	 *
	 * @throws MWException
	 * @return string
	 */
	protected function serializeData( array $data, $format = null ) {
		if ( is_null( $format ) ) {
			$format = $this->getDefaultFormat();
		}

		$codec = new ArrayStructureCodec();
		$blob = $codec->serializeData( $data, $format );

		return $blob;
	}

	/**
	 * Decodes the given blob into a structure of nested arrays using the
	 * given serialization format.
	 *
	 * Currently, two formats are supported: CONTENT_FORMAT_SERIALIZED, CONTENT_FORMAT_JSON.
	 *
	 * @param String $blob The data to decode
	 * @param String|null $format The data format (if null, getDefaultFormat()
	 * is used to determine it).
	 *
	 * @return array The deserialized data structure
	 *
	 * @throws MWException if an unsupported format is requested
	 * @throws \MWContentSerializationException If serialization fails.
	 */
	protected function unserializedData( $blob, $format = null ) {
		if ( is_null( $format ) ) {
			$format = $this->getDefaultFormat();
		}

		$codec = new ArrayStructureCodec();
		$data = $codec->unserializeData( $blob, $format );

		return $data;
	}

	/**
	 * @see EntityHandler::getEntityNamespace
	 *
	 * @since 0.1
	 *
	 * @return integer
	 */
	final public function getEntityNamespace() {
		return NamespaceUtils::getEntityNamespace( $this->getModelID() );
	}

	/**
	 * @see ContentHandler::canBeUsedOn();
	 *
	 * This implementation returns true if and only if the given title's namespace
	 * is the same as the one returned by $this->getEntityNamespace().
	 *
	 * @param \Title $title
	 * @return bool true if $title represents a page in the appropriate entity namespace.
	 */
	public function canBeUsedOn( Title $title ) {
		$namespace = $this->getEntityNamespace();
		return $namespace === $title->getNamespace();
	}

	/**
	 * Returns false to indicate that the parser cache should not be used for data items.
	 * The html representation of Items depends on the user language, splitting the parser
	 * cache by user language is currently problematic and would need some core changes.
	 *
	 * @note: see also note on getPageLanguage()
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
	 * This implementation returns the user language, because entities get rendered in
	 * the user's language. The PageContentLanguage hook is bypassed.
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

	/**
	 * @see Content::getPageLanguage
	 *
	 * This implementation unconditionally returns the wiki's content language.
	 * The PageContentLanguage hook is bypassed.
	 *
	 * @note: Ideally, this would return 'mul' to indicate multilingual content. But MediaWiki
	 * currently doesn't support that.
	 *
	 * @note: in several places in mediawiki, most importantly the parser cache, getPageLanguage
	 * is used in places where getPageViewLanguage would be more appropriate. This is the reason that
	 * isParserCacheSupported() is overridden to return false.
	 *
	 * @param Title        $title the page to determine the language for.
	 * @param Content|null $content the page's content, if you have it handy, to avoid reloading it.
	 *
	 * @return \Language the page's language
	 */
	public function getPageLanguage( Title $title, Content $content = null ) {
		global $wgContLang;
		return $wgContLang;
	}

	/**
	 * Returns the name of the special page responsible for creating a page
	 * for this type of entity content.
	 * Returns null if there is no such special page.
	 *
	 * @since 0.2
	 *
	 * @return string|null
	 */
	public function getSpecialPageForCreation() {
		return null;
	}

	/**
	 * Constructs a new EntityContent from an Entity.
	 *
	 * @since 0.3
	 *
	 * @param Entity $entity
	 *
	 * @return EntityContent
	 */
	public function newContentFromEntity( Entity $entity ) {
		$contentClass = $this->getContentClass();
		return new $contentClass( $entity );
	}

	/**
	 * @see ContentHandler::getUndoContent
	 *
	 * @since 0.4
	 *
	 * @param $latestRevision Revision The current text
	 * @param $newerRevision Revision The revision to undo
	 * @param $olderRevision Revision Must be an earlier revision than $undo
	 *
	 * @return Content|bool Content on success, false on failure
	 */
	public function getUndoContent( Revision $latestRevision, Revision $newerRevision,
		Revision $olderRevision
	) {
		/**
		 * @var EntityContent $latestContent
		 * @var EntityContent $olderContent
		 * @var EntityContent $newerContent
		 */
		$olderContent = $olderRevision->getContent();
		$newerContent = $newerRevision->getContent();
		$latestContent = $latestRevision->getContent();

		if ( $newerRevision->getId() === $latestRevision->getId() ) {
			// no patching needed, just roll back
			return $olderContent;
		}

		// diff from new to base
		$patch = $newerContent->getDiff( $olderContent );

		// apply the patch( new -> old ) to the current revision.
		// FIXME: handle redirects!
		$patchedCurrent = $latestContent->getEntity()->copy();
		$patchedCurrent->patch( $patch );

		// detect conflicts against current revision
		$cleanPatch = $latestContent->getEntity()->getDiff( $patchedCurrent );
		$conflicts = $patch->count() - $cleanPatch->count();

		if ( $conflicts > 0 ) {
			return false;
		} else {
			$undo = $this->makeEntityContent( $patchedCurrent );
			return $undo;
		}
	}

	/**
	 * Returns the entity type ID for the kind of entity managed by this EntityContent implementation.
	 *
	 * @return string
	 */
	abstract public function getEntityType();
}
