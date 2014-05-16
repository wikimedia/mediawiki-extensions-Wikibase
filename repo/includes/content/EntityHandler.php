<?php

namespace Wikibase;

use Content;
use ContentHandler;
use DataUpdate;
use Diff\Patcher\PatcherException;
use IContextSource;
use InvalidArgumentException;
use Language;
use MWContentSerializationException;
use MWException;
use ParserOptions;
use RequestContext;
use Revision;
use Title;
use User;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityRedirect;
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
 * @author Daniel Kinzler
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
	 * @throws \MWException Always. EntityContent cannot be empty.
	 * @return EntityContent
	 */
	public function makeEmptyContent() {
		throw new MWException( 'Can not make an empty EntityContent, since we require at least an ID to be set.' );
	}

	/**
	 * Returns an empty Entity object of the type supported by this handler.
	 * This is intended to provide a baseline for diffing and related operations.
	 *
	 * @note The Entity returned here will not have an ID set, and is thus not
	 * suitable for use in an EntityContent object.
	 *
	 * @since 0.5
	 *
	 * @return EntityContent
	 */
	public abstract function makeEmptyEntity();

	/**
	 * Will return a new EntityContent representing the given EntityRedirect,
	 * or null if the Content class does not support redirects (that is, if it does
	 * not have a static newFromRedirect() function).
	 *
	 * @see makeRedirectContent()
	 *
	 * @since 0.5
	 *
	 * @param EntityRedirect $redirect
	 *
	 * @return EntityContent|null
	 */
	public function makeEntityRedirectContent( EntityRedirect $redirect ) {
		$contentClass = $this->getContentClass();

		if ( !defined( 'WB_EXPERIMENTAL_FEATURES' ) || !WB_EXPERIMENTAL_FEATURES ) {
			// For now, we only support redirects in experimental mode.
			return null;
		} elseif ( method_exists( $contentClass, 'newFromRedirect' ) ) {
			$title = $this->getTitleForId( $redirect->getTargetId() );
			return $contentClass::newFromRedirect( $redirect, $title );
		} else {
			return null;
		}
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
		throw new MWException( 'EntityContent does not support plain title based redirects. Use makeEntityRedirectContent() instead.' );
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
	 * @throws InvalidArgumentException
	 * @return EntityContent
	 */
	public function makeEntityContent( Entity $entity ) {
		$contentClass = $this->getContentClass();

		/* EntityContent $content */
		$content = new $contentClass( $entity );

		//TODO: make sure the entity is valid/complete!

		return $content;
	}

	/**
	 * Parses the given ID string into an EntityId for the type of entity
	 * supported by this EntityHandler. If the string is not a valid
	 * serialization of the correct type of entity ID, an exception is thrown.
	 *
	 * @param string $id String representation the entity ID
	 *
	 * @return EntityId
	 *
	 * @throws InvalidArgumentException
	 */
	public abstract function makeEntityId( $id );

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
			$redirect = $content->getEntityRedirect();
			return $this->contentCodec->encodeRedirect( $redirect, $format );
		} else {
			$entity = $content->getEntity();
			return $this->contentCodec->encodeEntity( $entity, $format );
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
		$entity = $this->contentCodec->decodeEntity( $blob, $format );

		if ( !$entity ) {
			// Must be a redirect then
			$redirect = $this->contentCodec->decodeRedirect( $blob, $format );

			if ( $redirect === null ) {
				throw new MWContentSerializationException(
					'The serialized data contains neither an Entity nor an EntityRedirect!'
				);
			}

			return $this->makeEntityRedirectContent( $redirect );
		} else {
			$entityContent = $this->makeEntityContent( $entity );
			return $entityContent;
		}
	}

	/**
	 * Returns the ID of the entity contained by the page of the given title.
	 *
	 * @warn This should not really be needed and may just go away!
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
	 * @warn This should not really be needed and may just go away!
	 *
	 * @since 0.5
	 *
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
	 * @since 0.5
	 *
	 * @param EntityContent $content
	 * @param Title $title
	 *
	 * @return DataUpdate[]
	 */
	public function getEntityDeletionUpdates( EntityContent $content, Title $title ) {
		$updates = array();

		$entityId = $content->getEntityId();

		//FIXME: we should not need this!
		if ( $entityId === null ) {
			$entityId = $this->getIdForTitle( $title );
		}

		// Call the WikibaseEntityDeletionUpdate hook.
		// Do this before doing any well-known updates.
		$updates[] = new DataUpdateClosure(
			'wfRunHooks',
			'WikibaseEntityDeletionUpdate',
			array( $content, $title ) );

		// Unregister the entity from the terms table.
		$updates[] = new DataUpdateClosure(
			array( $this->termIndex, 'deleteTermsOfEntity' ),
			$entityId
		);

		// Unregister the entity from the EntityPerPage table.
		$updates[] = new DataUpdateClosure(
			array( $this->entityPerPage, 'deleteEntityPage' ),
			$entityId,
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

		$entityId = $content->getEntityId();

		//FIXME: we should not need this!
		if ( $entityId === null ) {
			$entityId = $this->getIdForTitle( $title );
		}

		// Register the entity in the EntityPerPage table.
		// @todo: Only do this if the entity is new.
		// Note that $title->exists() will already return true at this point
		// even if we are just now creating the entity.
		// @todo: if this is a redirect, record redirect target
		$updates[] = new DataUpdateClosure(
			array( $this->entityPerPage, 'addEntityPage' ),
			$entityId,
			$title->getArticleID()
		);

		if ( $content->isRedirect() ) {
			// Remove the entity from the terms table since it's now a redirect.
			$updates[] = new DataUpdateClosure(
				array( $this->termIndex, 'deleteTermsOfEntity' ),
				$entityId
			);
		} else {
			// Register the entity in the terms table.
			$updates[] = new DataUpdateClosure(
				array( $this->termIndex, 'saveTermsOfEntity' ),
				$content->getEntity()
			);
		}

		// Call the WikibaseEntityModificationUpdate hook.
		// Do this after doing all well-known updates.
		$updates[] = new DataUpdateClosure(
			'wfRunHooks',
			'WikibaseEntityModificationUpdate',
			array( $content, $title )
		);

		return $updates;
	}

}
