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

		$data = $content->getEntity()->toArray();
		return $this->contentCodec->encodeEntityContentData( $data, $format );
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
		$data = $this->contentCodec->decodeEntityContentData( $blob, $format );

		$entityContent = $this->newContentFromArray( $data );
		return $entityContent;
	}


	/**
	 * Calls the static function newFromArray() on the content class,
	 * to create a new EntityContent object based on the array data.
	 *
	 * @param array $data
	 *
	 * @return EntityContent
	 */
	protected function newContentFromArray( array $data ) {
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

		// diff from new to base
		$patch = $newerContent->getEntity()->getDiff( $olderContent->getEntity() );

		// apply the patch( new -> old ) to the current revision.
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
