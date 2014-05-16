<?php

namespace Wikibase;

use Content;
use ContentHandler;
use IContextSource;
use InvalidArgumentException;
use Language;
use MWContentSerializationException;
use ParserOptions;
use RequestContext;
use Revision;
use Title;
use User;
use Wikibase\Store\EntityContentDataCodec;
use Wikibase\Validators\EntityValidator;

/**
 * Base handler class for Wikibase\Entity content classes.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
abstract class EntityHandler extends ContentHandler {

	/**
	 * @var EntityValidator[]
	 */
	protected $preSaveValidators;

	/**
	 * @var \Wikibase\Store\EntityContentDataCodec
	 */
	protected $contentCodec;

	/**
	 * @param string $modelId
	 * @param \Wikibase\Store\EntityContentDataCodec $contentCodec
	 * @param EntityValidator[] $preSaveValidators
	 */
	public function __construct( $modelId, EntityContentDataCodec $contentCodec, $preSaveValidators ) {
		$formats = $contentCodec->getSupportedFormats();

		parent::__construct( $modelId, $formats );

		$this->contentCodec = $contentCodec;
		$this->preSaveValidators = $preSaveValidators;
	}

	/**
	 * Returns the name of the EntityContent deriving class.
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	abstract protected function getContentClass();

	/**
	 * @see ContentHandler::getDiffEngineClass
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	protected function getDiffEngineClass() {
		return '\Wikibase\EntityContentDiffView';
	}

	/**
	 * Returns a set of validators for enforcing hard constraints on the content
	 * before saving. For soft constraints, see the TermValidatorFactory.
	 *
	 * @return EntityValidator[]
	 */
	public function getOnSaveValidators() {
		return $this->preSaveValidators;
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
	 * @see ContentHandler::makeRedirectContent
	 *
	 * Will return a new EntityContent representing a redirect to the given title,
	 * or null if the Content class does not support redirects (that is, if it does
	 * not have a static newRedirect() function).
	 *
	 * @since 0.5
	 *
	 * @param \Title $title
	 * @param string $text
	 *
	 * @return EntityContent
	 */
	public function makeRedirectContent( Title $title, $text = '' ) {
		$contentClass = $this->getContentClass();

		if ( method_exists( $contentClass, 'newRedirect' ) ) {
			return $contentClass::newRedirect( $title );
		} else {
			return null;
		}
	}

	/**
	 * @see ContentHandler::makeParserOptions
	 *
	 * @since 0.5
	 *
	 * @param IContextSource|User|string $context
	 *
	 * @return ParserOptions
	 */
	public function makeParserOptions( $context ) {
		if ( $context === 'canonical' ) {
			// There are no "canonical" ParserOptions for Wikibase,
			// as everything is User-language dependent
			$context = RequestContext::getMain();
		}

		$options = parent::makeParserOptions( $context );

		// The html representation of entities depends on the user language, so we
		// have to call ParserOptions::getUserLangObj to split the cache by user language.
		$options->getUserLangObj();
		return $options;
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
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getDefaultFormat() {
		return $this->contentCodec->getDefaultFormat();
	}

	/**
	 * @param Content $content
	 * @param string|null $format
	 *
	 * @throws InvalidArgumentException
	 * @return string
	 */
	public function serializeContent( Content $content, $format = null ) {
		if ( ! $content instanceof EntityContent ) {
			throw new \InvalidArgumentException( '$content mist be an instance of EntityContent' );
		}

		if ( $content->isRedirect() ) {
			$data = $this->contentCodec->redirectTitleToArray( $content->getRedirectTarget() );
			return $this->contentCodec->encodeBlob( $data, $format );
		} else {
			$data = $content->getEntity()->toArray();
			return $this->contentCodec->encodeBlob( $data, $format );
		}
	}

	/**
	 * @see ContentHandler::unserializeContent
	 *
	 * @since 0.1
	 *
	 * @param string $blob
	 * @param null|string $format
	 *
	 * @throws \MWContentSerializationException
	 * @return EntityContent
	 */
	public function unserializeContent( $blob, $format = null ) {
		$data = $this->contentCodec->decodeBlob( $blob, $format );

		if ( $this->contentCodec->isRedirectData( $data ) ) {
			$redirect = $this->contentCodec->extractRedirectTarget( $data );
			return $this->makeRedirectContent( $redirect );
		} else {
			$entity = $this->newEntityFromArray( $data );
			return $this->newContent( $entity );
		}
	}

	/**
	 * Creates a new EntityContent object wrapping the given Entity object.
	 *
	 * @since 0.5
	 *
	 * @param Entity $entity An Entity object. The type of $entity must match
	 * the kind concrete subclass of EntityContent that this handler supports.
	 *
	 * @throws InvalidArgumentException If $entity has the wrong type.
	 * @return EntityContent
	 */
	protected abstract function newContent( Entity $entity );

	/**
	 * Calls the static function newFromArray() on the content class,
	 * to create a new Entity based on the array data.
	 *
	 * @param array $data
	 *
	 * @return EntityContent
	 */
	private function newEntityFromArray( array $data ) {
		$contentClass = $this->getContentClass();
		return $contentClass::newFromArray( $data );
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
	 * @param Title $title
	 *
	 * @return bool true if $title represents a page in the appropriate entity namespace.
	 */
	public function canBeUsedOn( Title $title ) {
		if ( !parent::canBeUsedOn( $title ) ) {
			return false;
		}

		$namespace = $this->getEntityNamespace();
		return $namespace === $title->getNamespace();
	}

	/**
	 * Returns true to indicate that the parser cache can be used for data items.
	 *
	 * @note: The html representation of entities depends on the user language, so
	 * EntityContent::getParserOutput needs to make sure ParserOutput::recordOption( 'userlang' )
	 * is called to split the cache by user language.
	 *
	 * @see ContentHandler::isParserCacheSupported
	 *
	 * @since 0.1
	 *
	 * @return bool true
	 */
	public function isParserCacheSupported() {
		return true;
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
	 * @return Language The page's language
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
	 * is used in places where getPageViewLanguage would be more appropriate.
	 *
	 * @param Title        $title the page to determine the language for.
	 * @param Content|null $content the page's content, if you have it handy, to avoid reloading it.
	 *
	 * @return Language The page's language
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

		// FIXME: handle diff/patch for redirects, see bug 65585.
		if ( $newerContent->isRedirect() || $olderContent->isRedirect() ) {
			return false;
		}

		// diff from new to base
		$patch = $newerContent->getDiff( $olderContent );

		// apply the patch( new -> old ) to the current revision.
		$patchedCurrent = $latestContent->getPatchedCopy( $patch );

		// detect conflicts against current revision
		$cleanPatch = $latestContent->getDiff( $patchedCurrent );
		$conflicts = $patch->count() - $cleanPatch->count();

		if ( $conflicts > 0 ) {
			return false;
		} else {
			return $patchedCurrent;
		}
	}

	/**
	 * Returns the entity type ID for the kind of entity managed by this EntityContent implementation.
	 *
	 * @return string
	 */
	abstract public function getEntityType();
}
