<?php

namespace Wikibase\Repo\Content;

use Content;
use ContentHandler;
use DataUpdate;
use DeferrableUpdate;
use Diff\Patcher\PatcherException;
use Hooks;
use Html;
use IContextSource;
use InvalidArgumentException;
use Language;
use MWContentSerializationException;
use MWException;
use ParserOptions;
use ParserOutput;
use RequestContext;
use Revision;
use SearchEngine;
use Title;
use User;
use Wikibase\Content\DeferredDecodingEntityHolder;
use Wikibase\Content\EntityHolder;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\EntityContent;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Repo\Diff\EntityContentDiffView;
use Wikibase\Repo\Search\Elastic\Fields\FieldDefinitions;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\EntityValidator;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\TermIndex;
use WikiPage;

/**
 * Base handler class for Entity content classes.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
abstract class EntityHandler extends ContentHandler {

	/**
	 * Added to parser options for EntityContent.
	 *
	 * Bump the version when making incompatible changes
	 * to parser output.
	 */
	const PARSER_VERSION = 3;

	/**
	 * @var FieldDefinitions
	 */
	protected $fieldDefinitions;

	/**
	 * @var TermIndex
	 */
	private $termIndex;

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
	 * @param TermIndex $termIndex
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
		TermIndex $termIndex,
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

		$this->termIndex = $termIndex;
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
		$outputPage->addWikiText( Html::openElement( 'div', [
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
	public function getOnSaveValidators( $forCreation ) {
		if ( $forCreation ) {
			$validators = $this->constraintProvider->getCreationValidators( $this->getEntityType() );
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
	 * @see ContentHandler::getAutosummary
	 *
	 * We never want to use MediaWiki's autosummaries, used e.g. for new page creation. Override this
	 * to make sure they never overwrite our autosummaries (which look like the automatic summary
	 * prefixes with a section title, and so could be overwritten).
	 *
	 * @param Content $oldContent
	 * @param Content $newContent
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
	 * @warn Always throws an MWException, since an EntityRedirects needs to know it's own
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
	 * @see ContentHandler::makeParserOptions
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

		// bump PARSER VERSION when making breaking changes to parser output (e.g. entity view).
		$options->addExtraKey( 'wb' . self::PARSER_VERSION );

		return $options;
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
	 * @warn This should not really be needed and may just go away!
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
	 * @warn This should not really be needed and may just go away!
	 *
	 * @see EntityTitleStoreLookup::getTitleForId
	 *
	 * @param EntityId $id
	 *
	 * @throws InvalidArgumentException if $id refers to an entity of the wrong type.
	 * @return Title
	 */
	public function getTitleForId( EntityId $id ) {
		if ( $id->getEntityType() !== $this->getEntityType() ) {
			throw new InvalidArgumentException( 'The given ID does not refer to an entity of type '
				. $this->getEntityType() );
		}

		return Title::makeTitle( $this->getEntityNamespace(), $id->getSerialization() );
	}

	/**
	 * @see EntityHandler::getEntityNamespace
	 *
	 * @return int
	 */
	final public function getEntityNamespace() {
		$entityNamespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();

		return $entityNamespaceLookup->getEntityNamespace( $this->getEntityType() );
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
	 * @return bool Always true in this default implementation.
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
	 * @return string|null Always null in this default implementation.
	 */
	public function getSpecialPageForCreation() {
		return null;
	}

	/**
	 * @see ContentHandler::getUndoContent
	 *
	 * @param Revision $latestRevision The current text
	 * @param Revision $newerRevision The revision to undo
	 * @param Revision $olderRevision Must be an earlier revision than $undo
	 *
	 * @return Content|bool Content on success, false on failure
	 */
	public function getUndoContent(
		Revision $latestRevision,
		Revision $newerRevision,
		Revision $olderRevision
	) {
		/**
		 * @var EntityContent $latestContent
		 * @var EntityContent $newerContent
		 * @var EntityContent $olderContent
		 */
		$latestContent = $latestRevision->getContent();
		$newerContent = $newerRevision->getContent();
		$olderContent = $olderRevision->getContent();

		if ( $latestRevision->getId() === $newerRevision->getId() ) {
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
	 * Returns deletion updates for the given EntityContent.
	 *
	 * @see Content::getDeletionUpdates
	 *
	 * @param EntityContent $content
	 * @param Title $title
	 *
	 * @return DeferrableUpdate[]
	 */
	public function getEntityDeletionUpdates( EntityContent $content, Title $title ) {
		$updates = [];

		$entityId = $content->getEntityId();

		// Call the WikibaseEntityDeletionUpdate hook.
		// Do this before doing any well-known updates.
		$updates[] = new DataUpdateAdapter(
			[ Hooks::class, 'run' ],
			'WikibaseEntityDeletionUpdate',
			[ $content, $title ]
		);

		// Unregister the entity from the terms table.
		$updates[] = new DataUpdateAdapter(
			[ $this->termIndex, 'deleteTermsOfEntity' ],
			$entityId
		);

		return $updates;
	}

	/**
	 * Returns modification updates for the given EntityContent.
	 *
	 * @see Content::getSecondaryDataUpdates
	 *
	 * @param EntityContent $content
	 * @param Title $title
	 *
	 * @return DataUpdate[]
	 */
	public function getEntityModificationUpdates( EntityContent $content, Title $title ) {
		$updates = [];
		$entityId = $content->getEntityId();

		//FIXME: we should not need this!
		if ( $entityId === null ) {
			$entityId = $this->getIdForTitle( $title );
		}

		if ( $content->isRedirect() ) {
			// Remove the entity from the terms table since it's now a redirect.
			$updates[] = new DataUpdateAdapter(
				[ $this->termIndex, 'deleteTermsOfEntity' ],
				$entityId
			);
		} else {
			// Register the entity in the terms table.
			$updates[] = new DataUpdateAdapter(
				[ $this->termIndex, 'saveTermsOfEntity' ],
				$content->getEntity()
			);
		}

		// Call the WikibaseEntityModificationUpdate hook.
		// Do this after doing all well-known updates.
		$updates[] = new DataUpdateAdapter(
			[ Hooks::class, 'run' ],
			'WikibaseEntityModificationUpdate',
			[ $content, $title ]
		);

		return $updates;
	}

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
	 * @param WikiPage $page
	 * @param ParserOutput $output
	 * @param SearchEngine $engine
	 *
	 * @return array Wikibase fields data, map of name=>value for fields
	 */
	public function getDataForSearchIndex(
		WikiPage $page,
		ParserOutput $output,
		SearchEngine $engine
	) {
		$fieldsData = parent::getDataForSearchIndex( $page, $output, $engine );

		$content = $page->getContent();
		if ( ( $content instanceof EntityContent ) && !$content->isRedirect() ) {
			$entity = $content->getEntity();
			$fields = $this->fieldDefinitions->getFields();

			foreach ( $fields as $fieldName => $field ) {
				$fieldsData[$fieldName] = $field->getFieldData( $entity );
			}
		}

		return $fieldsData;
	}

}
