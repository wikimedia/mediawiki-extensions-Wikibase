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
use Hooks;
use LogicException;
use MWException;
use ParserOptions;
use ParserOutput;
use RuntimeException;
use Status;
use Title;
use User;
use ValueValidators\Result;
use Wikibase\Content\DeferredCopyEntityHolder;
use Wikibase\Content\EntityHolder;
use Wikibase\Content\EntityInstanceHolder;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Services\Diff\EntityPatcher;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\Content\EntityHandler;
use Wikibase\Repo\FingerprintSearchTextGenerator;
use Wikibase\Repo\Validators\EntityValidator;
use Wikibase\Repo\WikibaseRepo;
use WikiPage;

/**
 * Abstract content object for articles representing Wikibase entities.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Bene* < benestar.wikimedia@gmail.com >
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
	 * Flag for use with prepareSave(), indicating that no pre-save validation should be applied.
	 * Can be passed in via EditEntity::attemptSave, EntityStore::saveEntity,
	 * as well as WikiPage::doEditContent()
	 *
	 * @note: must not collide with the EDIT_XXX flags defined by MediaWiki core in Defines.php.
	 */
	const EDIT_IGNORE_CONSTRAINTS = 1024;

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

		return $this->getEntityId() !== null;
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
	 * @return EntityDocument
	 */
	abstract public function getEntity();

	/**
	 * Returns a holder for the entity contained in this EntityContent object.
	 *
	 * @throws MWException when it's a redirect (targets will never be resolved)
	 * @return EntityHolder
	 */
	abstract protected function getEntityHolder();

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
			$id = $this->getEntityHolder()->getEntityId();
			if ( !$id ) {
				// @todo: Force an ID to be present; Entity objects without an ID make sense,
				// EntityContent objects with no entity ID don't.
				throw new RuntimeException( 'EntityContent was constructed without an EntityId!' );
			}

			return $id;
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
	public function getSecondaryDataUpdates(
		Title $title,
		Content $oldContent = null,
		$recursive = false,
		ParserOutput $parserOutput = null
	) {
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
	 *
	 * @note: this calls ParserOutput::recordOption( 'userlang' ) to split the cache
	 * by user language.
	 *
	 * @see Content::getParserOutput
	 *
	 * @param Title $title
	 * @param int|null $revisionId
	 * @param ParserOptions|null $options
	 * @param bool $generateHtml
	 *
	 * @return ParserOutput
	 */
	public function getParserOutput(
		Title $title,
		$revisionId = null,
		ParserOptions $options = null,
		$generateHtml = true
	) {
		if ( $this->isRedirect() ) {
			return $this->getParserOutputForRedirect( $generateHtml );
		} else {
			if ( $options === null ) {
				$options = $this->getContentHandler()->makeParserOptions( 'canonical' );
			}

			return $this->getParserOutputFromEntityView( $title, $revisionId, $options, $generateHtml );
		}
	}

	/**
	 * @since 0.5
	 *
	 * @note Will fail if this EntityContent does not represent a redirect.
	 *
	 * @param bool $generateHtml
	 *
	 * @return ParserOutput
	 */
	protected function getParserOutputForRedirect( $generateHtml ) {
		$output = new ParserOutput();
		/** @var Title $target */
		$target = $this->getRedirectTarget();

		// Make sure to include the redirect link in pagelinks
		$output->addLink( $target );

		// Since the output depends on the user language, we must make sure
		// ParserCache::getKey() includes it in the cache key.
		$output->recordOption( 'userlang' );
		if ( $generateHtml ) {
			$chain = $this->getRedirectChain();
			$language = $this->getContentHandler()->getPageViewLanguage( $target );
			$html = Article::getRedirectHeaderHtml( $language, $chain, false );
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
	 * @param int|null $revisionId
	 * @param ParserOptions $options
	 * @param bool $generateHtml
	 *
	 * @return ParserOutput
	 */
	protected function getParserOutputFromEntityView(
		Title $title,
		$revisionId = null,
		ParserOptions $options,
		$generateHtml = true
	) {
		// @todo: move this to the ContentHandler
		$entityParserOutputGeneratorFactory = WikibaseRepo::getDefaultInstance()->getEntityParserOutputGeneratorFactory();

		$outputGenerator = $entityParserOutputGeneratorFactory->getEntityParserOutputGenerator(
			$options->getUserLang(),
			$options->getEditSection()
		);

		$entityRevision = $this->getEntityRevision( $revisionId );

		$output = $outputGenerator->getParserOutput( $entityRevision->getEntity(), $generateHtml );

		// Force parser cache split by whether edit links are show.
		// MediaWiki core has the ability to split on editsection, but does not trigger it
		// automatically when $parserOptions->getEditSection() is called. Presumably this
		// is because core uses <mw:editsection> tags that are substituted by ParserOutput::getText
		// using the info from ParserOutput::getEditSectionTokens.
		$output->recordOption( 'editsection' );

		// Since the output depends on the user language, we must make sure
		// ParserCache::getKey() includes it in the cache key.
		$output->recordOption( 'userlang' );

		$this->applyEntityPageProperties( $output );

		return $output;
	}

	/**
	 * @param int|null $revisionId
	 *
	 * @return EntityRevision
	 */
	private function getEntityRevision( $revisionId = null ) {
		$entity = $this->getEntity();

		if ( $revisionId !== null ) {
			return new EntityRevision( $entity, $revisionId );
		}

		// Revision defaults to 0 (latest), which is desired and suitable in cases where
		// getParserOutput specifies no revision. (e.g. is called during save process
		// when revision id is unknown or not assigned yet)
		return new EntityRevision( $entity );
	}

	/**
	 * @return String a string representing the content in a way useful for building a full text
	 *         search index.
	 */
	public function getTextForSearchIndex() {
		if ( $this->isRedirect() ) {
			return '';
		}

		$text = '';
		$entity = $this->getEntity();

		if ( $entity instanceof FingerprintProvider ) {
			$searchTextGenerator = new FingerprintSearchTextGenerator();
			$text = $searchTextGenerator->generate( $entity->getFingerprint() );
		}

		if ( !Hooks::run( 'WikibaseTextForSearchIndex', array( $this, &$text ) ) ) {
			return '';
		}

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

		return implode( "\n", $values );
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
	protected static function collectValues( array $data, array $ignore = array() ) {
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

		global $wgLang;
		$entity = $this->getEntity();

		// TODO use DescriptionProvider
		if ( $entity instanceof FingerprintProvider ) {
			$fingerprint = $entity->getFingerprint();
			$languageCode = $wgLang->getCode();

			if ( $fingerprint->hasDescription( $languageCode ) ) {
				$description = $fingerprint->getDescription( $languageCode )->getText();
				return substr( $description, 0, $maxLength );
			}
		}

		return '';
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
		$serializer = WikibaseRepo::getDefaultInstance()->getEntitySerializer();
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

		$thisId = $this->getEntityHolder()->getEntityId();
		$thatId = $that->getEntityHolder()->getEntityId();

		if ( $thisId !== null && $thatId !== null
			&& !$thisId->equals( $thatId )
		) {
			return false;
		}

		$thisEntity = $this->getEntity();
		$thatEntity = $that->getEntity();

		return $thisEntity->equals( $thatEntity );
	}

	/**
	 * @return EntityDocument
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
	 * @return EntityContentDiff
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

		$entityDiffer = WikibaseRepo::getDefaultInstance()->getEntityDiffer();
		$entityDiff = $entityDiffer->diffEntities( $fromEntity, $toEntity );

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

		// FIXME: this should either be done in the derivatives, or the patcher
		// should be injected, so the application can add support for additional entity types.
		$patcher = WikibaseRepo::getDefaultInstance()->getEntityPatcher();
		$patcher->patchEntity( $entityAfterPatch, $patch->getEntityDiff() );

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
			$patched = $handler->makeEntityContent( new EntityInstanceHolder( $entityAfterPatch ) );
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
	 * @return bool True if this is not a redirect and the page is empty.
	 */
	public function isEmpty() {
		return !$this->isRedirect() && parent::isEmpty();
	}

	/**
	 * @return bool
	 */
	abstract public function isStub();

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
			return $handler->makeEntityContent( new DeferredCopyEntityHolder( $this->getEntityHolder() ) );
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
		// Chain to parent
		$status = parent::prepareSave( $page, $flags, $baseRevId, $user );

		if ( $status->isOK() ) {
			if ( !$this->isRedirect() && ( $flags & self::EDIT_IGNORE_CONSTRAINTS ) === 0 ) {
				/* @var EntityHandler $handler */
				$handler = $this->getContentHandler();
				$validators = $handler->getOnSaveValidators( ( $flags & EDIT_NEW ) > 0 );
				$status = $this->applyValidators( $validators );
			}
		}

		return $status;
	}

	/**
	 * Apply the given validators.
	 *
	 * @param EntityValidator[] $validators
	 *
	 * @return Result
	 */
	private function applyValidators( array $validators ) {
		$result = Result::newSuccess();

		/* @var EntityValidator $validator */
		foreach ( $validators as $validator ) {
			$result = $validator->validateEntity( $this->getEntity() );

			if ( !$result->isValid() ) {
				break;
			}
		}

		/* @var EntityHandler $handler */
		$handler = $this->getContentHandler();
		$status = $handler->getValidationErrorLocalizer()->getResultStatus( $result );
		return $status;
	}

	/**
	 * Registers any properties returned by getEntityPageProperties()
	 * in $output.
	 *
	 * @param ParserOutput $output
	 */
	private function applyEntityPageProperties( ParserOutput $output ) {
		if ( $this->isRedirect() ) {
			return;
		}

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
	 * Records the entity's status in the 'wb-status' key.
	 *
	 * @return array A map from property names to property values.
	 */
	public function getEntityPageProperties() {
		$properties = array();

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
	 * @note Will fail if this EntityContent is a redirect.
	 *
	 * @see getEntityPageProperties()
	 * @see EntityContent::STATUS_NONE
	 * @see EntityContent::STATUS_STUB
	 * @see EntityContent::STATUS_EMPTY
	 *
	 * @return int
	 */
	public function getEntityStatus() {
		if ( $this->isEmpty() ) {
			return self::STATUS_EMPTY;
		} elseif ( $this->isStub() ) {
			return self::STATUS_STUB;
		} else {
			return self::STATUS_NONE;
		}
	}

}
