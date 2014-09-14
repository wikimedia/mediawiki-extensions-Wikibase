<?php

namespace Wikibase;

use AbstractContent;
use Article;
use Content;
use DataUpdate;
use Diff\Differ\MapDiffer;
use Diff\DiffOp\Diff\Diff;
use Diff\Patcher\MapPatcher;
use Diff\Patcher\PatcherException;
use Language;
use LogicException;
use MWException;
use ParserOptions;
use ParserOutput;
use RequestContext;
use RuntimeException;
use Status;
use Title;
use User;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\Content\EntityHandler;
use Wikibase\Repo\EntitySearchTextGenerator;
use Wikibase\Repo\ParserOutput\HtmlParserOutputGeneratorFactory;
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
	 *
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
			// Under some circumstances, the handler will not support redirects,
			// but it's still possible to construct Content objects that represent
			// redirects. In such a case, make sure such Content objects are considered
			// invalid and do not get saved.
			return $this->getContentHandler()->supportsRedirects();
		}

		return $this->getEntity()->getId() !== null;
	}

	/**
	 * Returns the EntityRedirect represented by this EntityContent, or null if this
	 * EntityContent is not a redirect.
	 *
	 * @note This default implementation will fail if isRedirect() is true.
	 * Subclasses that support redirects must override getEntityRedirect().
	 *
	 * @throws LogicException
	 * @return EntityRedirect|null
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
	 * @throws MWException when it's a redirect (targets will never be resolved)
	 * @return Entity
	 */
	abstract public function getEntity();

	/**
	 * Returns the ID of the entity represented by this EntityContent;
	 *
	 * @throws RuntimeException if no entity ID is set
	 * @return EntityId
	 */
	public function getEntityId() {
		if ( $this->isRedirect() ) {
			return $this->getEntityRedirect()->getEntityId();
		} else {
			if ( !$this->getEntity()->getId() ) {
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
	 * @param WikiPage $page
	 * @param ParserOutput|null $parserOutput
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
	 * @param Title $title
	 * @param Content|null $oldContent
	 * @param bool $recursive
	 * @param ParserOutput|null $parserOutput
	 *
	 * @return DataUpdate[]
	 */
	public function getSecondaryDataUpdates( Title $title, Content $oldContent = null,
		$recursive = false, ParserOutput $parserOutput = null ) {

		/** @var EntityHandler $handler */
		$handler = $this->getContentHandler();
		$updates = $handler->getEntityModificationUpdates( $this, $title );

		return array_merge(
			parent::getSecondaryDataUpdates( $title, $oldContent, $recursive, $parserOutput ),
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
			return $this->getParserOutputForRedirect( $generateHtml );
		} else {
			return $this->getParserOutputForEntity( $title, $revId, $options, $generateHtml );
		}
	}

	/**
	 * @since 0.5
	 *
	 * @note Will fail if this EntityContent does not represent a redirect.
	 *
	 * @param $generateHtml
	 *
	 * @return ParserOutput
	 */
	protected function getParserOutputForRedirect( $generateHtml ) {
		$output = new ParserOutput();
		/** @var Title $target */
		$target = $this->getRedirectTarget();

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
	protected function getParserOutputForEntity(
		Title $title,
		$revId = null,
		ParserOptions $options = null,
		$generateHtml = true
	) {
		$context = $this->getContextFromParserOptions( $options );
		$editable = !$options? true : $options->getEditSection();

		if ( $revId === null || $revId === 0 ) {
			$revId = $title->getLatestRevID();
		}

		$revision = new EntityRevision( $this->getEntity(), $revId );
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$language = $context->getLanguage();
		$languageFallbackChain = $wikibaseRepo->getLanguageFallbackChainFactory()->newFromContextForPageView( $context );

		$pout = new ParserOutput();

		if ( $generateHtml ) {
			$entityView = $this->getEntityView( $language, $languageFallbackChain );
			$htmlParserOutputGeneratorFactory = new HtmlParserOutputGeneratorFactory(
				$wikibaseRepo->getStore()->getEntityInfoBuilderFactory(),
				$wikibaseRepo->getEntityTitleLookup(),
				$wikibaseRepo->getPropertyDataTypeLookup()
			);
			$htmlParserOutputGenerator = $htmlParserOutputGeneratorFactory->createHtmlParserOutputGenerator(
				$language,
				$languageFallbackChain,
				$entityView
			);
			$htmlParserOutputGenerator->assignToParserOutput( $pout, $revision, $editable );
		}

		$this->addDataToParserOutput( $pout );

		return $pout;
	}

	private function getContextFromParserOptions( ParserOptions $options = null ) {
		$context = RequestContext::getMain();

		if ( $options !== null ) {
			// Parser Options language overrides context language
			$context = clone $context;
			$context->setLanguage( $options->getUserLang() );
		}

		return $context;
	}

	/**
	 * Creates a new EntityView for this EntityContent.
	 *
	 * @param Language $language
	 * @param LanguageFallbackChain $languageFallbackChain
	 * @return EntityView
	 */
	protected abstract function getEntityView( Language $language, LanguageFallbackChain $languageFallbackChain );

	/**
	 * Assigns some further information to the ParserOutput.
	 *
	 * @param ParserOutput $pout
	 */
	protected function addDataToParserOutput( ParserOutput $pout ) {
		// Since the output depends on the user language, we must make sure
		// ParserCache::getKey() includes it in the cache key.
		$pout->recordOption( 'userlang' );

		// register page properties
		$status = $this->getEntityStatus();

		if ( $status !== self::STATUS_NONE ) {
			$pout->setProperty( 'wb-status', $status );
		}
	}

	/**
	 * @return String a string representing the content in a way useful for building a full text
	 *         search index.
	 */
	public function getTextForSearchIndex() {
		if ( $this->isRedirect() ) {
			return '';
		}

		wfProfileIn( __METHOD__ );
		$searchTextGenerator = new EntitySearchTextGenerator();
		$text = $searchTextGenerator->generate( $this->getEntity() );

		if ( !wfRunHooks( 'WikibaseTextForSearchIndex', array( $this, &$text ) ) ) {
			return '';
		}

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
		/** @var Title $target */
		$target = $this->getRedirectTarget();
		return '#REDIRECT [[' . $target->getFullText() . ']]';
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

		// @todo this text for filters stuff should be it's own class with test coverage!
		$codec = WikibaseRepo::getDefaultInstance()->getEntityContentDataCodec();
		$json = $codec->encodeEntity( $this->getEntity(), CONTENT_FORMAT_JSON );
		$data = json_decode( $json, true );

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
	 * @param int $maxLength maximum length of the summary text
	 * @return String the summary text
	 */
	public function getTextForSummary( $maxLength = 250 ) {
		if ( $this->isRedirect() ) {
			return $this->getRedirectText();
		}

		/* @var Language $language */
		$language = $GLOBALS['wgLang'];
		$description = $this->getEntity()->getDescription( $language->getCode() );
		return substr( $description, 0, $maxLength );
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
		$serializer = WikibaseRepo::getDefaultInstance()->getInternalEntitySerializer();
		return $serializer->serialize( $this->getEntity() );
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
		if ( $that === $this ) {
			return true;
		}

		if ( !( $that instanceof self ) || $that->getModel() !== $this->getModel() ) {
			return false;
		}

		/** @var Title $thisRedirect */
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
	 * @return Entity
	 */
	private function makeEmptyEntity() {
		/** @var EntityHandler $handler */
		$handler = $this->getContentHandler();
		return $handler->makeEmptyEntity();
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

		$differ = new MapDiffer();
		$redirectDiffOps = $differ->doDiff(
			$fromContent->getRedirectData(),
			$toContent->getRedirectData()
		);

		$redirectDiff = new Diff( $redirectDiffOps, true );

		$fromEntity = $fromContent->isRedirect() ? $this->makeEmptyEntity() : $fromContent->getEntity();
		$toEntity = $toContent->isRedirect() ? $this->makeEmptyEntity() : $toContent->getEntity();

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
			$entityAfterPatch = $this->makeEmptyEntity();
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
	 * @return EntityRedirect|null
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
	 * @return ItemContent
	 */
	public function copy() {
		/* @var EntityHandler $handler */
		$handler = $this->getContentHandler();
		if ( $this->isRedirect() ) {
			return $handler->makeEntityRedirectContent( $this->getEntityRedirect() );
		} else {
			return $handler->makeEntityContent( $this->getEntity()->copy() );
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
	 * Returns an identifier representing the status of the entity,
	 * e.g. STATUS_EMPTY or STATUS_NONE.
	 *
	 * @note Will fail if this ItemContent is a redirect.
	 *
	 * @see EntityContent::STATUS_NONE
	 * @see EntityContent::STATUS_STUB
	 * @see EntityContent::STATUS_EMPTY
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
