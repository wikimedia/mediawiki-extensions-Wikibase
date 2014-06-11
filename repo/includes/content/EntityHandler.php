<?php

namespace Wikibase;

use Content;
use ContentHandler;
use DataUpdate;
use IContextSource;
use InvalidArgumentException;
use Language;
use MWContentSerializationException;
use ParserOptions;
use RequestContext;
use Revision;
use Title;
use User;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Updates\DataUpdateClosure;
use Wikibase\Validators\EntityValidator;
use Wikibase\Validators\ValidatorErrorLocalizer;

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
	 * @var EntityContentDataCodec
	 */
	protected $contentCodec;

	/**
	 * @var EntityPerPage
	 */
	private $entityPerPage;

	/**
	 * @var TermIndex
	 */
	private $termIndex;

	/**
	 * @var ValidatorErrorLocalizer
	 */
	private $errorLocalizer;

	/**
	 * @param string $modelId
	 * @param EntityPerPage $entityPerPage
	 * @param TermIndex $termIndex
	 * @param EntityContentDataCodec $contentCodec
	 * @param EntityValidator[] $preSaveValidators
	 * @param ValidatorErrorLocalizer $errorLocalizer
	 */
	public function __construct(
		$modelId,
		EntityPerPage $entityPerPage,
		TermIndex $termIndex,
		EntityContentDataCodec $contentCodec,
		array $preSaveValidators,
		ValidatorErrorLocalizer $errorLocalizer
	) {
		$formats = $contentCodec->getSupportedFormats();

		parent::__construct( $modelId, $formats );

		$this->contentCodec = $contentCodec;
		$this->preSaveValidators = $preSaveValidators;
		$this->entityPerPage = $entityPerPage;
		$this->termIndex = $termIndex;
		$this->errorLocalizer = $errorLocalizer;
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
	 * Returns the concrete EntityView implementation to use.
	 *
	 * @since 0.5
	 *
	 * @return string The class name.
	 */
	protected abstract function getEntityViewClass();

	/**
	 * Apply all EntityValidators registered for on-save validation
	 *
	 * @param EntityContent $content
	 *
	 * @return \Status
	 */
	public function applyOnSaveValidators( EntityContent $content ) {
		$entity = $content->getEntity();
		$result = Result::newSuccess();

		/* @var EntityValidator $validator */
		foreach ( $this->preSaveValidators as $validator ) {
			$result = $validator->validateEntity( $entity );

			if ( !$result->isValid() ) {
				break;
			}
		}

		return $this->errorLocalizer->getResultStatus( $result );
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
	 * @return EntityContent|null
	 */
	public function makeRedirectContent( Title $title, $text = '' ) {
		$contentClass = $this->getContentClass();

		if ( !defined( 'WB_EXPERIMENTAL_FEATURES' ) || !WB_EXPERIMENTAL_FEATURES ) {
			// For now, we only support redirects in experimental mode.E
			return null;
		} elseif ( method_exists( $contentClass, 'newRedirect' ) ) {
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
	 * Returns the ID of the entity contained by the page of the given title.
	 *
	 * @since 0.5
	 *
	 * @param Title $target
	 *
	 * @return EntityId
	 */
	public function getIdForTitle( Title $target ) {
		$parser = new BasicEntityIdParser();
		$id = $parser->parse( $target->getText() );
		return $id;
	}

	/**
	 * Returns the appropriate page Title for the given EntityId.
	 *
	 * @since 0.5
	 * @see EntityTitleLookup::getTitleForId
	 *
	 * @param EntityId $id
	 *
	 * @throws \InvalidArgumentException if $id refers to an entity of the wrong type.
	 * @return Title $target
	 */
	public function getTitleForId( EntityId $id ) {
		if ( $id->getEntityType() !== $this->getEntityType() ) {
			throw new InvalidArgumentException( 'The given ID does not refer to an entity of type '
				. $this->getEntityType() );
		}

		$title = Title::makeTitle( $this->getEntityNamespace(), $id->getSerialization() );
		return $title;
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

	/**
	 * Returns deletion updates for the given EntityContent.
	 *
	 * @see Content::getDeletionUpdates
	 *
	 * @since 0.5
	 *
	 * @param EntityContent $content
	 * @param Title $title
	 *
	 * @return DataUpdate[]
	 */
	public function getEntityDeletionUpdates( EntityContent $content, Title $title ) {
		$updates = array();

		// Call the WikibaseEntityDeletionUpdate hook.
		// Do this before doing any well-known updates.
		$updates[] = new DataUpdateClosure(
			'wfRunHooks',
			'WikibaseEntityDeletionUpdate',
			array( $content, $title ) );

		// Unregister the entity from the terms table.
		$updates[] = new DataUpdateClosure(
			array( $this->termIndex, 'deleteTermsOfEntity' ),
			$content->getEntity()->getId()
		);

		// Unregister the entity from the EntityPerPage table.
		$updates[] = new DataUpdateClosure(
			array( $this->entityPerPage, 'deleteEntityPage' ),
			$content->getEntity()->getId(),
			$title->getArticleID()
		);

		return $updates;
	}

	/**
	 * Returns modification updates for the given EntityContent.
	 *
	 * @see Content::getSecondaryDataUpdates
	 *
	 * @since 0.5
	 *
	 * @param EntityContent $content
	 * @param Title $title
	 *
	 * @return DataUpdate[]
	 */
	public function getEntityModificationUpdates( EntityContent $content, Title $title ) {
		$updates = array();

		// Register the entity in the EntityPerPage table.
		// @todo: Only do this if the entity is new.
		// Note that $title->exists() will already return true at this point
		// even if we are just now creating the entity.
		$updates[] = new DataUpdateClosure(
			array( $this->entityPerPage, 'addEntityPage' ),
			$content->getEntity()->getId(),
			$title->getArticleID()
		);

		// Register the entity in the terms table.
		$updates[] = new DataUpdateClosure(
			array( $this->termIndex, 'saveTermsOfEntity' ),
			$content->getEntity()
		);

		// Call the WikibaseEntityModificationUpdate hook.
		// Do this after doing all well-known updates.
		$updates[] = new DataUpdateClosure(
			'wfRunHooks',
			'WikibaseEntityModificationUpdate',
			array( $content, $title )
		);

		return $updates;
	}


	/**
	 * Creates an EntityView suitable for rendering the entity.
	 *
	 * @note: this uses global state to access the services needed for
	 * displaying the entity.
	 *
	 * @since 0.5
	 *
	 * @param IContextSource|null $context
	 * @param ParserOptions|null $options
	 * @param LanguageFallbackChain|null $uiLanguageFallbackChain
	 *
	 * @return EntityView
	 *
	 * @todo Factor out into a EntityViewFactory class, and inject that!
	 */
	public function getEntityView( IContextSource $context = null, ParserOptions $options = null,
		LanguageFallbackChain $uiLanguageFallbackChain = null
	) {
		if ( $context === null ) {
			$context = RequestContext::getMain();
		}

		// determine output language ----
		$langCode = $context->getLanguage()->getCode();

		if ( $options !== null ) {
			// NOTE: Parser Options language overrides context language!
			$langCode = $options->getUserLang();
		}

		// make formatter options ----
		$formatterOptions = new FormatterOptions();
		$formatterOptions->setOption( ValueFormatter::OPT_LANG, $langCode );

		// Force the context's language to be the one specified by the parser options.
		if ( $context && $context->getLanguage()->getCode() !== $langCode ) {
			$context = clone $context;
			$context->setLanguage( $langCode );
		}

		// apply language fallback chain ----
		if ( !$uiLanguageFallbackChain ) {
			$factory = WikibaseRepo::getDefaultInstance()->getLanguageFallbackChainFactory();
			$uiLanguageFallbackChain = $factory->newFromContextForPageView( $context );
		}

		$formatterOptions->setOption( 'languages', $uiLanguageFallbackChain );

		// get all the necessary services ----
		$snakFormatter = WikibaseRepo::getDefaultInstance()->getSnakFormatterFactory()
			->getSnakFormatter( SnakFormatter::FORMAT_HTML_WIDGET, $formatterOptions );

		$dataTypeLookup = WikibaseRepo::getDefaultInstance()->getPropertyDataTypeLookup();
		$entityInfoBuilder = WikibaseRepo::getDefaultInstance()->getStore()->getEntityInfoBuilder();
		$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();
		$entityTitleLookup = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();
		$idParser = new BasicEntityIdParser();

		$langCodes = Utils::getLanguageCodes() + array( $langCode => $uiLanguageFallbackChain );

		$options = new SerializationOptions();
		$options->setLanguages( $langCodes );

		$configBuilder = new ParserOutputJsConfigBuilder(
			$entityInfoBuilder,
			$idParser,
			$entityContentFactory,
			new ReferencedEntitiesFinder(),
			$context->getLanguage()->getCode()
		);

		// construct the instance ----
		$viewClass = $this->getEntityViewClass();

		$entityView = new $viewClass(
			$context,
			$snakFormatter,
			$dataTypeLookup,
			$entityInfoBuilder,
			$entityTitleLookup,
			$options,
			$configBuilder
		);

		return $entityView;
	}

}
