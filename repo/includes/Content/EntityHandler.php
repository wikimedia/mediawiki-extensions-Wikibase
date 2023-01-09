<?php

namespace Wikibase\Repo\Content;

use Article;
use Content;
use ContentHandler;
use Diff\Patcher\PatcherException;
use Html;
use IContextSource;
use InvalidArgumentException;
use Language;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\Content\ValidationParams;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MWContentSerializationException;
use MWException;
use ParserCache;
use ParserOptions;
use ParserOutput;
use RequestContext;
use SearchEngine;
use Status;
use Title;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\Diff\EntityContentDiffView;
use Wikibase\Repo\Search\Fields\FieldDefinitions;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\EntityValidator;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Assert\Assert;
use WikiPage;

/**
 * Base handler class for Entity content classes.
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class EntityHandler extends ContentHandler {

	/**
	 * Added to parser options for EntityContent.
	 *
	 * Bump the version when making incompatible changes
	 * to parser output.
	 */
	public const PARSER_VERSION = 3;

	/**
	 * @var FieldDefinitions
	 */
	protected $fieldDefinitions;

	/**
	 * @var EntityContentDataCodec
	 */
	protected $contentCodec;

	/**
	 * @var EntityConstraintProvider
	 */
	protected $constraintProvider;

	/**
	 * @var ValidatorErrorLocalizer
	 */
	private $errorLocalizer;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var callable|null Callback to determine whether a serialized
	 *        blob needs to be re-serialized on export.
	 */
	private $legacyExportFormatDetector;

	/**
	 * @param string $modelId
	 * @param mixed $unused @todo Get rid of me
	 * @param EntityContentDataCodec $contentCodec
	 * @param EntityConstraintProvider $constraintProvider
	 * @param ValidatorErrorLocalizer $errorLocalizer
	 * @param EntityIdParser $entityIdParser
	 * @param FieldDefinitions $fieldDefinitions
	 * @param callable|null $legacyExportFormatDetector Callback to determine whether a serialized
	 *        blob needs to be re-serialized on export. The callback must take two parameters,
	 *        the blob an the serialization format. It must return true if re-serialization is needed.
	 *        False positives are acceptable, false negatives are not.
	 *
	 */
	public function __construct(
		$modelId,
		$unused,
		EntityContentDataCodec $contentCodec,
		EntityConstraintProvider $constraintProvider,
		ValidatorErrorLocalizer $errorLocalizer,
		EntityIdParser $entityIdParser,
		FieldDefinitions $fieldDefinitions,
		$legacyExportFormatDetector = null
	) {
		$formats = $contentCodec->getSupportedFormats();

		parent::__construct( $modelId, $formats );

		if ( $legacyExportFormatDetector && !is_callable( $legacyExportFormatDetector ) ) {
			throw new InvalidArgumentException( '$legacyExportFormatDetector must be a callable (or null)' );
		}

		$this->contentCodec = $contentCodec;
		$this->constraintProvider = $constraintProvider;
		$this->errorLocalizer = $errorLocalizer;
		$this->entityIdParser = $entityIdParser;
		$this->legacyExportFormatDetector = $legacyExportFormatDetector;
		$this->fieldDefinitions = $fieldDefinitions;
	}

	/**
	 * Returns the callback used to determine whether a serialized blob needs
	 * to be re-serialized on export (or null of re-serialization is disabled).
	 *
	 * @return callable|null
	 */
	public function getLegacyExportFormatDetector() {
		return $this->legacyExportFormatDetector;
	}

	/**
	 * Handle the fact that a given page does not contain an Entity, even though it could.
	 * Per default, this behaves similarly to Article::showMissingArticle: it shows
	 * a message to the user.
	 *
	 * @see Article::showMissingArticle
	 *
	 * @param Title $title The title of the page that potentially could, but does not,
	 *        contain an entity.
	 * @param IContextSource $context Context to use for reporting. In particular, output
	 *        will be written to $context->getOutput().
	 */
	public function showMissingEntity( Title $title, IContextSource $context ) {
		$text = wfMessage( 'wikibase-noentity' )->setContext( $context )->plain();

		$dir = $context->getLanguage()->getDir();
		$lang = $context->getLanguage()->getHtmlCode();

		$outputPage = $context->getOutput();
		$outputPage->addWikiTextAsInterface( Html::openElement( 'div', [
				'class' => "noarticletext mw-content-$dir",
				'dir' => $dir,
				'lang' => $lang,
			] ) . "\n$text\n</div>" );
	}

	/**
	 * @see ContentHandler::getDiffEngineClass
	 *
	 * @return string
	 */
	protected function getDiffEngineClass() {
		return EntityContentDiffView::class;
	}

	/**
	 * Get EntityValidators for on-save validation.
	 *
	 * @see getValidationErrorLocalizer()
	 *
	 * @param bool $forCreation Whether the entity is created (true) or updated (false).
	 *
	 * @return EntityValidator[]
	 */
	public function getOnSaveValidators( $forCreation, EntityId $entityId ) {
		if ( $forCreation ) {
			$validators = $this->constraintProvider->getCreationValidators( $this->getEntityType(), $entityId );
		} else {
			$validators = $this->constraintProvider->getUpdateValidators( $this->getEntityType() );
		}

		return $validators;
	}

	/**
	 * Error localizer for use together with getOnSaveValidators().
	 *
	 * @see getOnSaveValidators()
	 *
	 * @return ValidatorErrorLocalizer
	 */
	public function getValidationErrorLocalizer() {
		return $this->errorLocalizer;
	}

	/**
	 * @see ContentHandler::makeEmptyContent
	 *
	 * @return EntityContent
	 */
	public function makeEmptyContent() {
		return $this->newEntityContent( null );
	}

	/**
	 * Returns an empty Entity object of the type supported by this handler.
	 * This is intended to provide a baseline for diffing and related operations.
	 *
	 * @note The Entity returned here will not have an ID set, and is thus not
	 * suitable for use in an EntityContent object.
	 *
	 * @return EntityDocument
	 */
	abstract public function makeEmptyEntity();

	/**
	 * @param EntityRedirect $redirect Unused in this default implementation.
	 *
	 * @return EntityContent|null Either a new EntityContent representing the given EntityRedirect,
	 *  or null if the entity type does not support redirects. Always null in this default
	 *  implementation.
	 */
	public function makeEntityRedirectContent( EntityRedirect $redirect ) {
		return null;
	}

	/**
	 * None of the Entity content models support categories.
	 *
	 * @return bool Always false.
	 */
	public function supportsCategories() {
		return false;
	}

	/**
	 * Do not render HTML on edit (T285987)
	 */
	public function generateHTMLOnEdit(): bool {
		return false;
	}

	/**
	 * @see ContentHandler::getAutosummary
	 *
	 * We never want to use MediaWiki's autosummaries, used e.g. for new page creation. Override this
	 * to make sure they never overwrite our autosummaries (which look like the automatic summary
	 * prefixes with a section title, and so could be overwritten).
	 *
	 * @param Content|null $oldContent
	 * @param Content|null $newContent
	 * @param int $flags
	 *
	 * @return string Empty string
	 */
	public function getAutosummary(
		Content $oldContent = null,
		Content $newContent = null,
		$flags = 0
	) {
		return '';
	}

	/**
	 * @see ContentHandler::makeRedirectContent
	 *
	 * @warning Always throws an MWException, since an EntityRedirects needs to know it's own
	 * ID in addition to the target ID. We have no way to guess that in makeRedirectContent().
	 * Use makeEntityRedirectContent() instead.
	 *
	 * @see makeEntityRedirectContent()
	 *
	 * @param Title $title
	 * @param string $text
	 *
	 * @throws MWException Always.
	 * @return EntityContent|null
	 */
	public function makeRedirectContent( Title $title, $text = '' ) {
		throw new MWException( 'EntityContent does not support plain title based redirects.'
			. ' Use makeEntityRedirectContent() instead.' );
	}

	/**
	 * @see ContentHandler::exportTransform
	 *
	 * @param string $blob
	 * @param string|null $format
	 *
	 * @return string
	 */
	public function exportTransform( $blob, $format = null ) {
		if ( !$this->legacyExportFormatDetector ) {
			return $blob;
		}

		$needsTransform = call_user_func( $this->legacyExportFormatDetector, $blob, $format );

		if ( $needsTransform ) {
			$format = ( $format === null ) ? $this->getDefaultFormat() : $format;

			$content = $this->unserializeContent( $blob, $format );
			$blob = $this->serializeContent( $content );
		}

		return $blob;
	}

	/**
	 * @param EntityHolder $entityHolder
	 *
	 * @return EntityContent
	 */
	public function makeEntityContent( EntityHolder $entityHolder ) {
		return $this->newEntityContent( $entityHolder );
	}

	/**
	 * @param EntityHolder|null $entityHolder
	 *
	 * @return EntityContent
	 */
	abstract protected function newEntityContent( EntityHolder $entityHolder = null );

	/**
	 * Parses the given ID string into an EntityId for the type of entity
	 * supported by this EntityHandler. If the string is not a valid
	 * serialization of the correct type of entity ID, an exception is thrown.
	 *
	 * @param string $id String representation the entity ID
	 *
	 * @return EntityId
	 * @throws InvalidArgumentException
	 */
	abstract public function makeEntityId( $id );

	/**
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
	 * @throws MWContentSerializationException
	 * @return string
	 */
	public function serializeContent( Content $content, $format = null ) {
		if ( !( $content instanceof EntityContent ) ) {
			throw new InvalidArgumentException( '$content must be an instance of EntityContent' );
		}

		if ( $content->isRedirect() ) {
			$redirect = $content->getEntityRedirect();
			return $this->contentCodec->encodeRedirect( $redirect, $format );
		} else {
			// TODO: If we have an un-decoded Entity in a DeferredDecodingEntityHolder, just re-use
			// the encoded form.
			$entity = $content->getEntity();
			return $this->contentCodec->encodeEntity( $entity, $format );
		}
	}

	/**
	 * @see ContentHandler::unserializeContent
	 *
	 * @param string $blob
	 * @param string|null $format
	 *
	 * @throws MWContentSerializationException
	 * @return EntityContent
	 */
	public function unserializeContent( $blob, $format = null ) {
		$redirect = $this->contentCodec->decodeRedirect( $blob, $format );

		if ( $redirect !== null ) {
			return $this->makeEntityRedirectContent( $redirect );
		} else {
			$holder = new DeferredDecodingEntityHolder(
				$this->contentCodec,
				$blob,
				$format,
				$this->getEntityType()
			);
			$entityContent = $this->makeEntityContent( $holder );

			return $entityContent;
		}
	}

	/**
	 * Returns the ID of the entity contained by the page of the given title.
	 *
	 * @warning This should not really be needed and may just go away!
	 *
	 * @param Title $target
	 *
	 * @throws EntityIdParsingException
	 * @return EntityId
	 */
	public function getIdForTitle( Title $target ) {
		return $this->entityIdParser->parse( $target->getText() );
	}

	/**
	 * Returns the appropriate page Title for the given EntityId.
	 *
	 * @warning This should not really be needed and may just go away!
	 *
	 * @see EntityTitleStoreLookup::getTitleForId
	 *
	 * @param EntityId $id
	 *
	 * @throws InvalidArgumentException if $id refers to an entity of the wrong type.
	 * @return Title|null
	 */
	public function getTitleForId( EntityId $id ) {
		if ( $id->getEntityType() !== $this->getEntityType() ) {
			throw new InvalidArgumentException( 'The given ID does not refer to an entity of type '
				. $this->getEntityType() );
		}

		return Title::makeTitle( $this->getEntityNamespace(), $id->getSerialization() );
	}

	/**
	 * Returns the appropriate page Titles for the given EntityIds
	 *
	 * @param EntityId[] $ids
	 * @return Title[] Array of Title objects indexed by the entity id serializations
	 */
	public function getTitlesForIds( array $ids ) {
		$titles = [];
		foreach ( $ids as $id ) {
			if ( $id->getEntityType() !== $this->getEntityType() ) {
				throw new InvalidArgumentException(
					'The given ID does not refer to an entity of type ' . $this->getEntityType()
				);
			}
			$titles[ $id->getSerialization() ] =
				Title::makeTitle( $this->getEntityNamespace(), $id->getSerialization() );
		}

		return $titles;
	}

	/**
	 * Returns the namespace that is to be used for this kind of entities.
	 *
	 * @return int
	 */
	final public function getEntityNamespace() {
		$entityNamespaceLookup = WikibaseRepo::getEntityNamespaceLookup();

		$ns = $entityNamespaceLookup->getEntityNamespace( $this->getEntityType() );

		Assert::postcondition(
			$ns !== null,
			'Namespace for entity type ' . $this->getEntityType() . ' must be defined!'
		);

		return $ns;
	}

	/**
	 * Returns the slot that is to be used for this kind of entities.
	 *
	 * @return string the role name of the slot
	 */
	final public function getEntitySlotRole() {
		$entityNamespaceLookup = WikibaseRepo::getEntityNamespaceLookup();

		return $entityNamespaceLookup->getEntitySlotRole( $this->getEntityType() );
	}

	/**
	 * @see ContentHandler::canBeUsedOn
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
	 * @note The html representation of entities depends on the user language, so
	 * EntityContent::getParserOutput needs to make sure ParserOutput::recordOption( 'userlang' )
	 * is called to split the cache by user language.
	 *
	 * @see ContentHandler::isParserCacheSupported
	 *
	 * @return bool Always true in this default implementation.
	 */
	public function isParserCacheSupported() {
		return true;
	}

	/**
	 * @see ContentHandler::getPageViewLanguage
	 *
	 * This implementation returns the user language, because entities get rendered in
	 * the user's language. The PageContentLanguage hook is bypassed.
	 *
	 * @param Title $title the page to determine the language for.
	 * @param Content|null $content the page's content, if you have it handy, to avoid reloading it.
	 *
	 * @return Language The page's language
	 */
	public function getPageViewLanguage( Title $title, Content $content = null ) {
		global $wgLang;

		return $wgLang;
	}

	/**
	 * @see ContentHandler::getPageLanguage
	 *
	 * This implementation unconditionally returns the wiki's content language.
	 * The PageContentLanguage hook is bypassed.
	 *
	 * @note Ideally, this would return 'mul' to indicate multilingual content. But MediaWiki
	 * currently doesn't support that.
	 *
	 * @note in several places in mediawiki, most importantly the parser cache, getPageLanguage
	 * is used in places where getPageViewLanguage would be more appropriate.
	 *
	 * @param Title $title the page to determine the language for.
	 * @param Content|null $content the page's content, if you have it handy, to avoid reloading it.
	 *
	 * @return Language The page's language
	 */
	public function getPageLanguage( Title $title, Content $content = null ) {
		return MediaWikiServices::getInstance()->getContentLanguage();
	}

	/**
	 * Returns the name of the special page responsible for creating a page
	 * for this type of entity content.
	 * Returns null if there is no such special page.
	 *
	 * @return string|null Always null in this default implementation.
	 */
	public function getSpecialPageForCreation() {
		return null;
	}

	/**
	 * @see ContentHandler::getUndoContent
	 *
	 * @param Content $latestContent The current text
	 * @param Content $newerContent The revision to undo
	 * @param Content $olderContent Must be an earlier revision than $newer
	 * @param bool $undoIsLatest Set to true if $newer is from the current revision (since 1.32)
	 *
	 * @return EntityContent|bool Content on success, false on failure
	 */
	public function getUndoContent(
		Content $latestContent,
		Content $newerContent,
		Content $olderContent,
		$undoIsLatest = false
	) {
		if (
			!$latestContent instanceof EntityContent
			|| !$newerContent instanceof EntityContent
			|| !$olderContent instanceof EntityContent
		) {
			return false;
		}

		if ( $undoIsLatest ) {
			// no patching needed, just roll back
			return $olderContent;
		}

		// diff from new to base
		$patch = $newerContent->getDiff( $olderContent );

		try {
			// apply the patch( new -> old ) to the current revision.
			$patchedCurrent = $latestContent->getPatchedCopy( $patch );
		} catch ( PatcherException $ex ) {
			return false;
		}

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
	 * Whether IDs can automatically be assigned to entities
	 * of the kind supported by this EntityHandler.
	 *
	 * @return bool
	 */
	public function allowAutomaticIds() {
		return true;
	}

	/**
	 * Whether the given custom ID is valid for creating a new entity
	 * of the kind supported by this EntityHandler.
	 *
	 * Implementations are not required to check if an entity with the given ID already exists.
	 * If this method returns true, this means that an entity with the given ID could be
	 * created (or already existed) at the time the method was called. There is no guarantee
	 * that this continues to be true after the method call returned. Callers must be careful
	 * to handle race conditions.
	 *
	 * @note For entity types that cannot be created with custom IDs (that is,
	 * entity types that are defined to use automatic IDs), this should always
	 * return false.
	 *
	 * @see EntityStore::canCreateWithCustomId()
	 *
	 * @param EntityId $id
	 *
	 * @return bool
	 */
	public function canCreateWithCustomId( EntityId $id ) {
		return false;
	}

	/**
	 * @param SearchEngine $engine
	 * @return \SearchIndexField[] List of fields this content handler can provide.
	 */
	public function getFieldsForSearchIndex( SearchEngine $engine ) {
		$fields = [];

		foreach ( $this->fieldDefinitions->getFields() as $name => $field ) {
			$mappingField = $field->getMappingField( $engine, $name );
			if ( $mappingField ) {
				$fields[$name] = $mappingField;
			}
		}

		return $fields;
	}

	/**
	 * @inheritDoc
	 */
	public function getDataForSearchIndex(
		WikiPage $page,
		ParserOutput $parserOutput,
		SearchEngine $engine,
		RevisionRecord $revision = null
	) {
		$fieldsData = parent::getDataForSearchIndex( $page, $parserOutput, $engine, $revision );

		$content = $revision != null ? $revision->getContent( SlotRecord::MAIN ) : $page->getContent();
		return $this->getContentDataForSearchIndex( $content ) + $fieldsData;
	}

	/**
	 * Extract fields data for the search index but only the fields
	 * related to the slot content.
	 * Useful for EntityHandlers that may work on non-main slot contents.
	 *
	 * @stable to override
	 * @param Content $content the Content to extract search data from
	 * @return array fields to be indexed by the search engine
	 * @throws MWException
	 */
	public function getContentDataForSearchIndex( Content $content ): array {
		$fieldsData = [];
		if ( ( $content instanceof EntityContent ) && !$content->isRedirect() ) {
			$entity = $content->getEntity();
			$fields = $this->fieldDefinitions->getFields();

			foreach ( $fields as $fieldName => $field ) {
				$fieldsData[$fieldName] = $field->getFieldData( $entity );
			}
		}
		return $fieldsData;
	}

	/**
	 * Produce page output suitable for indexing.
	 * Does not include HTML.
	 *
	 * @inheritDoc
	 */
	public function getParserOutputForIndexing( WikiPage $page, ParserCache $cache = null, RevisionRecord $revision = null ) {
		$parserOptions = $page->makeParserOptions( 'canonical' );
		if ( $cache ) {
			$parserOutput = $cache->get( $page, $parserOptions );
			if ( $parserOutput ) {
				return $parserOutput;
			}
		}

		$renderer = MediaWikiServices::getInstance()->getRevisionRenderer();
		$revisionRecord = $this->latestRevision( $page );
		$parserOutput = $renderer->getRenderedRevision( $revisionRecord, $parserOptions )
			// this will call EntityContent::getParserOutput() with $generateHtml = false
			->getRevisionParserOutput( [
				'generate-html' => false,
			] );
		// since we didn’t generate HTML, don’t call $cache->save()
		return $parserOutput;
	}

	/**
	 * @inheritDoc
	 */
	public function validateSave(
		Content $content,
		ValidationParams $validationParams
	) {
		'@phan-var EntityContent $content';
		// Chain to parent
		$status = parent::validateSave( $content, $validationParams );
		$flags = $validationParams->getFlags();
		if ( $status->isOK() ) {
			if ( !$content->isRedirect() && !( $flags & EntityContent::EDIT_IGNORE_CONSTRAINTS ) ) {
				$validators = $this->getOnSaveValidators(
					( $flags & EDIT_NEW ) !== 0,
					$content->getEntity()->getId()
				);
				$status = $this->applyValidators( $content, $validators );
			}
		}

		return $status;
	}

	/**
	 * @note this calls ParserOutput::recordOption( 'userlang' ) to split the cache
	 * by user language, and ParserOutput::recordOption( 'wb' ) to split the cache on
	 * EntityHandler::PARSER_VERSION.
	 *
	 * @inheritDoc
	 */
	protected function fillParserOutput(
		Content $content,
		ContentParseParams $cpoParams,
		ParserOutput &$parserOutput
	) {
		'@phan-var EntityContent $content';
		$generateHtml = $cpoParams->getGenerateHtml();
		$parserOptions = $cpoParams->getParserOptions();
		$revId = $cpoParams->getRevId();

		if ( $content->isRedirect() ) {
			$parserOutput = $this->getParserOutputForRedirect( $content, $generateHtml );
		} elseif ( !$content->getEntityHolder() ) {
			// NOTE: There is no entity to render, but fillParserOutput() must work for all Content objects.
			// NOTE: isEmpty() will return true when there is an entity, but that entity is empty. In
			//       that case, we must not bail out, but call getParserOutputFromEntityView() as normal.
		} else {
			$parserOutput = $this->getParserOutputFromEntityView(
				$content,
				$revId,
				$parserOptions,
				$generateHtml
			);

			if ( !$parserOptions->getUserLangObj()->equals( RequestContext::getMain()->getLanguage() ) ) {
				// HACK: Don't save to parser cache if this is not in the user's lang: T199983.
				$parserOutput->updateCacheExpiry( 0 );
			}
		}
	}

	/**
	 * @note Will fail if this EntityContent does not represent a redirect.
	 *
	 * @param EntityContent $content
	 * @param bool $generateHtml
	 *
	 * @return ParserOutput
	 */
	protected function getParserOutputForRedirect( EntityContent $content, bool $generateHtml ) {
		$parserOutput = new ParserOutput();
		$target = $content->getRedirectTarget();

		// Make sure to include the redirect link in pagelinks
		$parserOutput->addLink( $target );

		// Since the output depends on the user language, we must make sure
		// ParserCache::getKey() includes it in the cache key.
		$parserOutput->recordOption( 'userlang' );
		// And we need to include EntityHandler::PARSER_VERSION in the cache key too
		$parserOutput->recordOption( 'wb' );
		if ( $generateHtml ) {
			$language = $this->getPageViewLanguage( $target );
			$html = Article::getRedirectHeaderHtml( $language, $target, false );
			$parserOutput->setText( $html );
		}

		return $parserOutput;
	}

	/**
	 * @note Will fail if this EntityContent represents a redirect.
	 *
	 * @param EntityContent $content
	 * @param int|null $revisionId
	 * @param ParserOptions $options
	 * @param bool $generateHtml
	 *
	 * @return ParserOutput
	 */
	protected function getParserOutputFromEntityView(
		EntityContent $content,
		$revisionId,
		ParserOptions $options,
		$generateHtml = true
	) {
		$outputGenerator = WikibaseRepo::getEntityParserOutputGeneratorFactory()
			->getEntityParserOutputGenerator(
				$this->getValidUserLanguage( $options->getUserLangObj() )
			);

		$entityRevision = $this->getEntityRevision( $content, $revisionId );

		$parserOutput = $outputGenerator->getParserOutput( $entityRevision, $generateHtml );

		// Since the output depends on the user language, we must make sure
		// ParserCache::getKey() includes it in the cache key.
		$parserOutput->recordOption( 'userlang' );
		// And we need to include EntityHandler::PARSER_VERSION in the cache key too
		$parserOutput->recordOption( 'wb' );

		$this->applyEntityPageProperties( $content, $parserOutput );

		return $parserOutput;
	}

	private function getValidUserLanguage( Language $language ) {
		$services = MediaWikiServices::getInstance();
		if ( !$services->getLanguageNameUtils()->isValidBuiltInCode( $language->getCode() ) ) {
			return $services->getLanguageFactory()->getLanguage( 'und' ); // T204791
		}

		return $language;
	}

	/**
	 * @param EntityContent $content
	 * @param int|null $revisionId
	 *
	 * @return EntityRevision
	 */
	private function getEntityRevision( EntityContent $content, $revisionId = null ) {
		$entity = $content->getEntity();

		if ( $revisionId !== null ) {
			return new EntityRevision( $entity, $revisionId );
		}

		// Revision defaults to 0 (latest), which is desired and suitable in cases where
		// getParserOutput specifies no revision. (e.g. is called during save process
		// when revision id is unknown or not assigned yet)
		return new EntityRevision( $entity );
	}

	/**
	 * Registers any properties returned by getEntityPageProperties()
	 * in $parserOutput.
	 *
	 * @param EntityContent $content
	 * @param ParserOutput $parserOutput
	 */
	private function applyEntityPageProperties( EntityContent $content, ParserOutput $parserOutput ) {
		if ( $content->isRedirect() ) {
			return;
		}

		$properties = $content->getEntityPageProperties();
		foreach ( $properties as $name => $value ) {
			$parserOutput->setPageProperty( $name, $value );
		}
	}

	/**
	 * Apply the given validators.
	 *
	 * @param EntityContent $content
	 * @param EntityValidator[] $validators
	 *
	 * @return Status
	 */
	private function applyValidators( EntityContent $content, array $validators ) {
		$result = Result::newSuccess();

		foreach ( $validators as $validator ) {
			$result = $validator->validateEntity( $content->getEntity() );

			if ( !$result->isValid() ) {
				break;
			}
		}

		$status = $this->getValidationErrorLocalizer()->getResultStatus( $result );
		return $status;
	}
}
