<?php

namespace Wikibase;

use AbstractContent;
use Article;
use Content;
use DataUpdate;
use Diff\DiffOp\Diff\Diff;
use Diff\Differ\MapDiffer;
use Diff\Patcher\MapPatcher;
use Diff\Patcher\PatcherException;
use IContextSource;
use LogicException;
use ParserOptions;
use ParserOutput;
use RequestContext;
use RuntimeException;
use Status;
use Title;
use User;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\EntitySearchTextGenerator;
use Wikibase\Repo\WikibaseRepo;
use WikiPage;

/**
 * Abstract content object for articles representing Wikibase entities.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
abstract class EntityContent extends AbstractContent {

	/**
	 * For use in the wb-status page property to indicate that the entity has no special
	 * status, to indicate. STATUS_NONE will not be recorded in the database.
	 *
	 * @see getEntityStatus()
	 */
	const STATUS_NONE = 0;

	/**
	 * For use in the wb-status page property to indicate that the entity is a stub,
	 * i.e. it's empty except for terms (labels, descriptions, and aliases).
	 *
	 * @see getEntityStatus()
	 */
	const STATUS_STUB = 100;

	/**
	 * For use in the wb-status page property to indicate that the entity is empty.
	 * @see getEntityStatus()
	 */
	const STATUS_EMPTY = 200;

	/**
	 * Checks if this EntityContent is valid for saving.
	 *
	 * Returns false if the entity does not have an ID set.
	 *
	 * @see Content::isValid()
	 */
	public function isValid() {
		if ( $this->isRedirect() ) {
			return true;
		}

		if ( is_null( $this->getEntity()->getId() ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns the EntityRedirect represented by this EntityContent, or null if this
	 * EntityContent is not a redirect.
	 *
	 * @note This default implementation will fail if isRedirect() is true.
	 * Subclasses that support redirects must override getEntityRedirect().
	 *
	 * @return EntityRedirect|null
	 * @throws \LogicException
	 */
	public function getEntityRedirect() {
		if ( $this->isRedirect() ) {
			throw new LogicException( 'EntityContent subclasses that support redirects must override getEntityRedirect()' );
		}

		return null;
	}

	/**
	 * Returns the entity contained by this entity content.
	 * Deriving classes typically have a more specific get method as
	 * for greater clarity and type hinting.
	 *
	 * @since 0.1
	 *
	 * @return Entity
	 */
	abstract public function getEntity();

	/**
	 * Returns the ID of the entity represented by this EntityContent;
	 *
	 * @throws RuntimeException
	 * @return EntityId
	 */
	public function getEntityId() {
		if ( $this->isRedirect() ) {
			return $this->getEntityRedirect()->getEntityId();
		} else {
			if ( ! $this->getEntity()->getId() ) {
				// @todo: Force an ID to be present; Entity objects without an ID make sense,
				// EntityContent objects with no entity ID don't.
				throw new RuntimeException( 'EntityContent was constructed without an EntityId!' );
			}

			return $this->getEntity()->getId();
		}
	}

	/**
	 * @see Content::getDeletionUpdates
	 * @see EntityHandler::getEntityDeletionUpdates
	 *
	 * @param \WikiPage $page
	 * @param null|\ParserOutput $parserOutput
	 *
	 * @since 0.1
	 *
	 * @return DataUpdate[]
	 */
	public function getDeletionUpdates( WikiPage $page, ParserOutput $parserOutput = null ) {
		/* @var EntityHandler $handler */
		$handler = $this->getContentHandler();
		$updates = $handler->getEntityDeletionUpdates( $this, $page->getTitle() );

		return array_merge(
			parent::getDeletionUpdates( $page, $parserOutput ),
			$updates
		);
	}

	/**
	 * @see Content::getSecondaryDataUpdates
	 * @see EntityHandler::getEntityModificationUpdates
	 *
	 * @since 0.1
	 *
	 * @param Title              $title
	 * @param Content|null       $old
	 * @param bool               $recursive
	 * @param null|ParserOutput  $parserOutput
	 *
	 * @return DataUpdate[]
	 */
	public function getSecondaryDataUpdates( Title $title, Content $old = null,
		$recursive = false, ParserOutput $parserOutput = null ) {

		/* @var EntityHandler $handler */
		$handler = $this->getContentHandler();
		$updates = $handler->getEntityModificationUpdates( $this, $title );

		return array_merge(
			parent::getSecondaryDataUpdates( $title, $old, $recursive, $parserOutput ),
			$updates
		);
	}

	/**
	 * Returns a ParserOutput object containing the HTML.
	 * The actual work of generating a ParserOutput object is done by calling
	 * EntityView::getParserOutput().
	 *
	 * @note: this calls ParserOutput::recordOption( 'userlang' ) to split the cache
	 * by user language.
	 *
	 * @see Content::getParserOutput
	 *
	 * @since 0.1
	 *
	 * @param Title $title
	 * @param int|null $revId
	 * @param ParserOptions|null $options
	 * @param bool $generateHtml
	 *
	 * @return ParserOutput
	 */
	public function getParserOutput( Title $title, $revId = null, ParserOptions $options = null,
		$generateHtml = true
	) {
		if ( $this->isRedirect() ) {
			return $this->getParserOutputForRedirect( $this->getEntityRedirect(), $this->getRedirectTarget(), $generateHtml );
		} else {
			return $this->getParserOutputFromEntityView( $title, $revId, $options, $generateHtml );
		}
	}

	/**
	 * @since 0.5
	 *
	 * @note Will fail if this EntityContent does not represent a redirect.
	 *
	 * @param EntityRedirect $redirect
	 * @param Title $target
	 * @param $generateHtml
	 *
	 * @return ParserOutput
	 */
	protected function getParserOutputForRedirect( EntityRedirect $redirect, Title $target, $generateHtml ) {
		$output = new ParserOutput();

		// Make sure to include the redirect link in pagelinks
		$output->addLink( $target );
		if ( $generateHtml ) {
			$chain = $this->getRedirectChain();
			$html = Article::getRedirectHeaderHtml( $target->getPageLanguage(), $chain, false );
			$output->setText( $html );
		}

		return $output;
	}

	/**
	 * @since 0.5
	 *
	 * @note Will fail if this EntityContent represents a redirect.
	 *
	 * @param Title $title
	 * @param null $revId
	 * @param ParserOptions $options
	 * @param bool $generateHtml
	 *
	 * @return ParserOutput
	 */
	protected function getParserOutputFromEntityView( Title $title, $revId = null,
		ParserOptions $options = null, $generateHtml = true
	) {
		$editable = !$options? true : $options->getEditSection();

		if ( $revId === null || $revId === 0 ) {
			$revId = $title->getLatestRevID();
		}

		$revision = new EntityRevision( $this->getEntity(), $revId );

		// generate HTML
		$entityView = $this->getEntityView( null, $options, null );
		$output = $entityView->getParserOutput( $revision, $editable, $generateHtml );

		// Since the output depends on the user language, we must make sure
		// ParserCache::getKey() includes it in the cache key.
		$output->recordOption( 'userlang' );

		// register page properties
		$this->applyEntityPageProperties( $output );

		return $output;
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
	 */
	public function getEntityView( IContextSource $context = null, ParserOptions $options = null,
		LanguageFallbackChain $uiLanguageFallbackChain = null
	) {
		//TODO: cache last used entity view

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
		$idParser = new BasicEntityIdParser();

		$options = $this->makeSerializationOptions( $langCode, $uiLanguageFallbackChain );

		// construct the instance ----
		$entityView = $this->newEntityView(
			$context,
			$snakFormatter,
			$dataTypeLookup,
			$entityInfoBuilder,
			$entityContentFactory,
			$idParser,
			$options
		);

		return $entityView;
	}

	/**
	 * Instantiates an EntityView.
	 *
	 * @see getEntityView()
	 *
	 * @param IContextSource $context
	 * @param SnakFormatter $snakFormatter
	 * @param Lib\PropertyDataTypeLookup $dataTypeLookup
	 * @param EntityInfoBuilder $entityInfoBuilder
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param EntityIdParser $idParser
	 * @param SerializationOptions $options
	 *
	 * @return EntityView
	 */
	protected abstract function newEntityView(
		IContextSource $context,
		SnakFormatter $snakFormatter,
		PropertyDataTypeLookup $dataTypeLookup,
		EntityInfoBuilder $entityInfoBuilder,
		EntityTitleLookup $entityTitleLookup,
		EntityIdParser $idParser,
		SerializationOptions $options
	);

	/**
	 * @return String a string representing the content in a way useful for building a full text
	 *         search index.
	 */
	public function getTextForSearchIndex() {
		if ( $this->isRedirect() ) {
			return '';
		}

		wfProfileIn( __METHOD__ );

		$entity = $this->getEntity();

		$searchTextGenerator = new EntitySearchTextGenerator();
		$text = $searchTextGenerator->generate( $entity );

		wfProfileOut( __METHOD__ );
		return $text;
	}

	/**
	 * @return string Returns the string representation of the redirect
	 * represented by this EntityContent (if any).
	 *
	 * @note Will fail if this EntityContent is not a redirect.
	 */
	protected function getRedirectText() {
		return '#REDIRECT [[' . $this->getRedirectTarget()->getFullText() . ']]';
	}

	/**
	 * @return String a string representing the content in a way useful for content filtering as
	 *         performed by extensions like AbuseFilter.
	 */
	public function getTextForFilters() {
		if ( $this->isRedirect() ) {
			return $this->getRedirectText();
		}

		wfProfileIn( __METHOD__ );

		//XXX: $ignore contains knowledge about the Entity's internal representation.
		//     This list should therefore rather be maintained in the Entity class.
		static $ignore = array(
			'language',
			'site',
			'type',
		);

		$data = $this->getEntity()->toArray();

		$values = self::collectValues( $data, $ignore );

		$text = implode( "\n", $values );

		wfProfileOut( __METHOD__ );
		return $text;
	}

	/**
	 * Recursively collects values from nested arrays.
	 *
	 * @param array $data The array structure to process.
	 * @param array $ignore A list of keys to skip.
	 *
	 * @return array The values found in the array structure.
	 * @todo needs unit test
	 */
	protected static function collectValues( $data, $ignore = array() ) {
		$values = array();

		$erongi = array_flip( $ignore );
		foreach ( $data as $key => $value ) {
			if ( isset( $erongi[$key] ) ) {
				continue;
			}

			if ( is_array( $value ) ) {
				$values = array_merge( $values, self::collectValues( $value, $ignore ) );
			} else {
				$values[] = $value;
			}
		}

		return $values;
	}

	/**
	 * @return String the wikitext to include when another page includes this  content, or false if
	 *         the content is not includable in a wikitext page.
	 */
	public function getWikitextForTransclusion() {
		return false;
	}

	/**
	 * Returns a textual representation of the content suitable for use in edit summaries and log
	 * messages.
	 *
	 * @param int $maxlength maximum length of the summary text
	 * @return String the summary text
	 */
	public function getTextForSummary( $maxlength = 250 ) {
		if ( $this->isRedirect() ) {
			return $this->getRedirectText();
		} else {
			$lang = $GLOBALS['wgLang']->getCode();
			$text = $this->getEntity()->getDescription( $lang );
			return substr( $text, 0, $maxlength );
		}
	}

	/**
	 * Returns an array structure for the redirect represented by this EntityContent, if any.
	 *
	 * @note This may or may not be consistent with what EntityContentCodec does.
	 *       It it intended to be used primarily for diffing.
	 */
	private function getRedirectData() {
		// NOTE: keep in sync with getPatchedRedirect
		$data = array(
			'entity' => $this->getEntityId()->getSerialization(),
		);

		if ( $this->isRedirect() ) {
			$data['redirect'] = $this->getEntityRedirect()->getTargetId()->getSerialization();
		}

		return $data;
	}

	/**
	 * @see Content::getNativeData
	 *
	 * @note Avoid relying on this method! It bypasses EntityContentCodec, and does
	 *       not make any guarantees about the structure of the array returned.
	 *
	 * @return array An undefined data structure representing the content. This is not guaranteed
	 *         to conform to any serialization structure used in the database or externally.
	 */
	public function getNativeData() {
		if ( $this->isRedirect() ) {
			return $this->getRedirectData();
		}

		// NOTE: this may or may not be consistent with what EntityContentCodec does!
		return $this->getEntity()->toArray();
	}

	/**
	 * returns the content's nominal size in bogo-bytes.
	 *
	 * @return int
	 */
	public function getSize() {
		return strlen( serialize( $this->getNativeData() ) );
	}

	/**
	 * Both contents will be considered equal if they have the same ID and equal Entity data. If
	 * one of the contents is considered "new", then matching IDs is not a criteria for them to be
	 * considered equal.
	 *
	 * @see Content::equals
	 */
	public function equals( Content $that = null ) {
		if ( is_null( $that ) ) {
			return false;
		}

		if ( $that === $this ) {
			return true;
		}

		if ( $that->getModel() !== $this->getModel() ) {
			return false;
		}

		if ( !( $that instanceof EntityContent ) ) {
			return false;
		}

		$thisRedirect = $this->getRedirectTarget();
		$thatRedirect = $that->getRedirectTarget();

		if ( $thisRedirect !== null ) {
			if ( $thatRedirect === null ) {
				return false;
			} else {
				return $thisRedirect->equals( $thatRedirect )
					&& $this->getEntityRedirect()->equals( $that->getEntityRedirect() );
			}
		} elseif ( $thatRedirect !== null ) {
			return false;
		}

		$thisEntity = $this->getEntity();
		$thatEntity = $that->getEntity();

		$thisId = $thisEntity->getId();
		$thatId = $thatEntity->getId();

		if ( $thisId !== null && $thatId !== null
			&& !$thisId->equals( $thatId )
		) {
			return false;
		}

		return $thisEntity->equals( $thatEntity );
	}

	/**
	 * Returns an empty entity.
	 *
	 * @return Entity
	 */
	protected function getEmptyEntity() {
		return $this->getContentHandler()->makeEmptyEntity();
	}

	/**
	 * Returns a diff between this EntityContent and the given EntityContent.
	 *
	 * @param EntityContent $toContent
	 *
	 * @return Diff
	 */
	public function getDiff( EntityContent $toContent ) {
		$fromContent = $this;

		$fromRedirectData = $fromContent->getRedirectData();
		$toRedirectData = $toContent->getRedirectData();

		$differ = new MapDiffer();
		$redirectDiffOps = $differ->doDiff( $fromRedirectData, $toRedirectData );
		$redirectDiff = new Diff( $redirectDiffOps, true );

		$fromEntity = $fromContent->isRedirect() ? $this->getEmptyEntity() : $fromContent->getEntity();
		$toEntity = $toContent->isRedirect() ? $this->getEmptyEntity() : $toContent->getEntity();

		$entityDiff = $fromEntity->getDiff( $toEntity );

		return new EntityContentDiff( $entityDiff, $redirectDiff );
	}

	/**
	 * Returns a patched copy of this Content object.
	 *
	 * @param EntityContentDiff $patch
	 *
	 * @throws PatcherException
	 * @return EntityContent
	 */
	public function getPatchedCopy( EntityContentDiff $patch ) {
		/* @var EntityHandler $handler */
		$handler = $this->getContentHandler();

		if ( $this->isRedirect() ) {
			$entityAfterPatch = $this->getEmptyEntity();
		} else {
			$entityAfterPatch = $this->getEntity()->copy();
		}

		$entityAfterPatch->patch( $patch->getEntityDiff() );

		$redirAfterPatch = $this->getPatchedRedirect( $patch->getRedirectDiff() );

		if ( $redirAfterPatch !== null && !$entityAfterPatch->isEmpty() ) {
			throw new PatcherException( 'EntityContent must not contain Entity data as well as'
				. ' a redirect after applying the patch!' );
		} elseif ( $redirAfterPatch ) {
			$patched = $handler->makeEntityRedirectContent( $redirAfterPatch );

			if ( !$patched ) {
				throw new PatcherException( 'Cannot create a redirect using content model '
					. $this->getModel() . '!' );
			}
		} else {
			$patched = $handler->makeEntityContent( $entityAfterPatch );
		}

		return $patched;
	}

	/**
	 * @param Diff $redirectPatch
	 *
	 * @return null|EntityRedirect
	 */
	private function getPatchedRedirect( Diff $redirectPatch ) {
		// See getRedirectData() for the structure of the data array.
		$redirData = $this->getRedirectData();

		if ( !$redirectPatch->isEmpty() ) {
			$patcher = new MapPatcher();
			$redirData = $patcher->patch( $redirData, $redirectPatch );
		}

		if ( isset( $redirData['redirect'] ) ) {
			/* @var EntityHandler $handler */
			$handler = $this->getContentHandler();

			$entityId = $this->getEntityId();
			$targetId = $handler->makeEntityId( $redirData['redirect'] );

			return new EntityRedirect( $entityId, $targetId );
		} else {
			return null;
		}
	}

	/**
	 * Returns true if this content is countable as a "real" wiki page, provided
	 * that it's also in a countable location (e.g. a current revision in the main namespace).
	 *
	 * @param bool $hasLinks: if it is known whether this content contains links, provide this
	 *        information here, to avoid redundant parsing to find out.
	 *
	 * @return bool
	 */
	public function isCountable( $hasLinks = null ) {
		if ( $this->isRedirect() ) {
			return false;
		}

		return !$this->getEntity()->isEmpty();
	}

	/**
	 * @since 0.1
	 *
	 * @return bool
	 */
	public function isEmpty() {
		if ( $this->isRedirect() ) {
			return false;
		}

		return $this->getEntity()->isEmpty();
	}

	/**
	 * @see Content::copy
	 *
	 * @since 0.1
	 *
	 * @return ItemContent
	 */
	public function copy() {
		/* @var EntityHandler $handler */
		$handler = $this->getContentHandler();
		if ( $this->isRedirect() ) {
			return $handler->makeEntityRedirectContent( $this->getEntityRedirect() );
		} else {
			$entity = $this->getEntity()->copy();
			return $handler->makeEntityContent( $entity );
		}
	}

	/**
	 * @see Content::prepareSave
	 *
	 * @param WikiPage $page
	 * @param int $flags
	 * @param int $baseRevId
	 * @param User $user
	 *
	 * @return Status
	 */
	public function prepareSave( WikiPage $page, $flags, $baseRevId, User $user ) {
		wfProfileIn( __METHOD__ );

		// Chain to parent
		$status = parent::prepareSave( $page, $flags, $baseRevId, $user );
		if ( $status->isOK() ) {
			if ( !$this->isRedirect() ) {
				/* @var EntityHandler $handler */
				$handler = $this->getContentHandler();
				$status = $handler->applyOnSaveValidators( $this );
			}
		}

		wfProfileOut( __METHOD__ );
		return $status;
	}

	/**
	 * @param string $langCode
	 * @param LanguageFallbackChain $fallbackChain
	 *
	 * @return SerializationOptions
	 */
	private function makeSerializationOptions( $langCode, LanguageFallbackChain $fallbackChain ) {
		$langCodes = Utils::getLanguageCodes() + array( $langCode => $fallbackChain );

		$options = new SerializationOptions();
		$options->setLanguages( $langCodes );

		return $options;
	}

	/**
	 * Registers any properties returned by getEntityPageProperties()
	 * in $output.
	 *
	 * @param ParserOutput $output
	 */
	private function applyEntityPageProperties( ParserOutput $output ) {
		$properties = $this->getEntityPageProperties();

		foreach ( $properties as $name => $value ) {
			$output->setProperty( $name, $value );
		}
	}

	/**
	 * Returns a map of properties about the entity, to be recorded in
	 * MediaWiki's page_props table. The idea is to allow efficient lookups
	 * of entities based on such properties.
	 *
	 * @see getEntityStatus()
	 *
	 * Keys used:
	 * - wb-status: the entity's status, according to getEntityStatus()
	 * - wb-claims: the number of claims in the entity
	 *
	 * @return array A map from property names to property values.
	 */
	public function getEntityPageProperties() {
		if ( $this->isRedirect() ) {
			return array();
		}

		$entity = $this->getEntity();

		$properties = array(
			'wb-claims' => count( $entity->getClaims() ),
		);

		$status = $this->getEntityStatus();

		if ( $status !== self::STATUS_NONE ) {
			$properties['wb-status'] = $status;
		}

		return $properties;
	}

	/**
	 * Returns an identifier representing the status of the entity,
	 * e.g. STATUS_EMPTY or STATUS_NONE.
	 * Used by getEntityPageProperties().
	 *
	 * @note Will fail if this ItemContent is a redirect.
	 *
	 * @see getEntityPageProperties()
	 * @see STATUS_NONE
	 * @see STATUS_EMPTY
	 * @see STATUS_STUB
	 *
	 * @return int
	 */
	public function getEntityStatus() {
		if ( $this->isEmpty() ) {
			return self::STATUS_EMPTY;
		} elseif ( !$this->getEntity()->hasClaims() ) {
			return self::STATUS_STUB;
		} else {
			return self::STATUS_NONE;
		}
	}

}
