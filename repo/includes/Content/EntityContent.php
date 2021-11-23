<?php

namespace Wikibase\Repo\Content;

use AbstractContent;
use Content;
use Diff\Differ\MapDiffer;
use Diff\DiffOp\Diff\Diff;
use Diff\Patcher\MapPatcher;
use Diff\Patcher\PatcherException;
use Hooks;
use LogicException;
use MediaWiki\MediaWikiServices;
use MWException;
use RuntimeException;
use Serializers\Exceptions\SerializationException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\Repo\ArrayValueCollector;
use Wikibase\Repo\FingerprintSearchTextGenerator;
use Wikibase\Repo\WikibaseRepo;

/**
 * Abstract content object for articles representing Wikibase entities.
 *
 * For more information on the relationship between entities and wiki pages, see
 * docs/entity-storage.wiki.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Bene* < benestar.wikimedia@gmail.com >
 *
 * @method \Wikibase\Repo\Content\EntityHandler getContentHandler()
 */
abstract class EntityContent extends AbstractContent {

	/**
	 * Flag for use with EntityHandler::validateSave(), indicating that no pre-save validation should be applied.
	 * Can be passed in via EditEntity::attemptSave, EntityStore::saveEntity,
	 * as well as WikiPage::doUserEditContent()
	 *
	 * @note: must not collide with the EDIT_XXX flags defined by MediaWiki core in Defines.php.
	 */
	public const EDIT_IGNORE_CONSTRAINTS = 1024;

	/**
	 * @see Content::isValid()
	 *
	 * @return bool True if this content object is valid for saving. False if there is no entity, or
	 *  the entity does not have an ID set.
	 */
	public function isValid() {
		if ( $this->isRedirect() ) {
			// Under some circumstances, the handler will not support redirects,
			// but it's still possible to construct Content objects that represent
			// redirects. In such a case, make sure such Content objects are considered
			// invalid and do not get saved.
			return $this->getContentHandler()->supportsRedirects();
		}

		$holder = $this->getEntityHolder();
		return $holder !== null && $holder->getEntityId() !== null;
	}

	/**
	 * @see EntityContent::isCountable
	 *
	 * @param bool|null $hasLinks
	 *
	 * @return bool True if this is not a redirect and the item is not empty.
	 */
	public function isCountable( $hasLinks = null ) {
		return !$this->isRedirect() && !$this->isEmpty();
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
	 * @throws LogicException if the content object is empty and does not contain an entity.
	 * @return EntityDocument
	 */
	abstract public function getEntity();

	/**
	 * Returns a holder for the entity contained in this EntityContent object.
	 *
	 * @throws MWException when it's a redirect (targets will never be resolved)
	 * @return EntityHolder|null
	 */
	abstract public function getEntityHolder();

	/**
	 * @throws RuntimeException if the content object is empty or no entity ID is set
	 * @return EntityId
	 */
	public function getEntityId(): EntityId {
		if ( $this->isRedirect() ) {
			return $this->getEntityRedirect()->getEntityId();
		}

		$holder = $this->getEntityHolder();
		if ( $holder !== null ) {
			$id = $holder->getEntityId();
			if ( $id !== null ) {
				return $id;
			}
		}

		throw new RuntimeException( 'EntityContent was constructed without an EntityId!' );
	}

	/**
	 * @return string A string representing the content in a way useful for building a full text
	 *         search index.
	 */
	public function getTextForSearchIndex() {
		if ( $this->isRedirect() ) {
			return '';
		}

		$searchTextGenerator = new FingerprintSearchTextGenerator();
		$text = $searchTextGenerator->generate( $this->getEntity() );

		if ( !Hooks::run( 'WikibaseTextForSearchIndex', [ $this, &$text ] ) ) {
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
		$target = $this->getRedirectTarget();
		return '#REDIRECT [[' . $target->getFullText() . ']]';
	}

	/**
	 * Get the keys within this Contents Entity JSON that should be removed for
	 * text passed to edit filters.
	 *
	 * @return string[] Keys to ignore
	 */
	abstract protected function getIgnoreKeysForFilters();

	/**
	 * @return string A string representing the content in a way useful for content filtering as
	 *         performed by extensions like AbuseFilter.
	 */
	public function getTextForFilters() {
		if ( $this->isRedirect() ) {
			return $this->getRedirectText();
		}

		// @todo this text for filters stuff should be it's own class with test coverage!
		$codec = WikibaseRepo::getEntityContentDataCodec();
		$json = $codec->encodeEntity( $this->getEntity(), CONTENT_FORMAT_JSON );
		$data = json_decode( $json, true );

		$values = ArrayValueCollector::collectValues( $data, $this->getIgnoreKeysForFilters() );

		return implode( "\n", $values );
	}

	/**
	 * @return string The wikitext to include when another page includes this  content, or false if
	 *         the content is not includable in a wikitext page.
	 */
	public function getWikitextForTransclusion() {
		return false;
	}

	/**
	 * Returns a textual representation of the content suitable for use in edit summaries and log messages.
	 *
	 * @param int $maxLength maximum length of the summary text
	 * @return string
	 * @throws MWException
	 */
	public function getTextForSummary( $maxLength = 250 ) {
		if ( $this->isRedirect() ) {
			return $this->getRedirectText();
		}

		$entity = $this->getEntity();

		// TODO: This assumes all entities not implementing their own getTextForSummary are LabelsProvider. Fix it.
		if ( !( $entity instanceof LabelsProvider ) ) {
			throw new LogicException(
				"Entity type {$entity->getType()} must implement its own getTextForSummary method."
			);
		}

		$labels = $entity->getLabels();
		if ( $labels->isEmpty() ) {
			return '';
		}

		$language = MediaWikiServices::getInstance()->getContentLanguage();

		if ( $labels->hasTermForLanguage( $language->getCode() ) ) {
			$label = $labels->getByLanguage( $language->getCode() )->getText();
			return $language->truncateForDatabase( $label, $maxLength );
		}

		// Return first term it can find
		$term = $labels->getIterator()->current();
		return $language->truncateForDatabase( $term->getText(), $maxLength );
	}

	/**
	 * Returns an array structure for the redirect represented by this EntityContent, if any.
	 *
	 * @note This may or may not be consistent with what EntityContentCodec does.
	 *       It it intended to be used primarily for diffing.
	 */
	private function getRedirectData() {
		// NOTE: keep in sync with getPatchedRedirect
		$data = [];

		if ( $this->isValid() ) {
			$data['entity'] = $this->getEntityId()->getSerialization();
		}

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
	 * @return array|EntityDocument An undefined data structure representing the content. This is
	 *  not guaranteed to conform to any serialization structure used in the database or externally.
	 */
	public function getNativeData() {
		if ( $this->isRedirect() ) {
			return $this->getRedirectData();
		}

		// NOTE: this may or may not be consistent with what EntityContentCodec does!
		$serializer = WikibaseRepo::getAllTypesEntitySerializer();
		try {
			return $serializer->serialize( $this->getEntity() );
		} catch ( SerializationException $ex ) {
			return $this->getEntity();
		}
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
	 *
	 * @param Content|null $that
	 *
	 * @return bool
	 */
	public function equals( Content $that = null ) {
		if ( $that === $this ) {
			return true;
		}

		if ( !( $that instanceof self ) || $that->getModel() !== $this->getModel() ) {
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

		$thisHolder = $this->getEntityHolder();
		$thatHolder = $that->getEntityHolder();
		if ( !$thisHolder && !$thatHolder ) {
			return true;
		} elseif ( !$thisHolder || !$thatHolder ) {
			return false;
		}

		$thisId = $thisHolder->getEntityId();
		$thatId = $thatHolder->getEntityId();
		if ( $thisId && $thatId && !$thisId->equals( $thatId ) ) {
			return false;
		}

		return $thisHolder->getEntity()->equals( $thatHolder->getEntity() );
	}

	/**
	 * @return EntityDocument
	 */
	private function makeEmptyEntity() {
		$handler = $this->getContentHandler();
		return $handler->makeEmptyEntity();
	}

	/**
	 * Returns a diff between this EntityContent and the given EntityContent.
	 *
	 * @param self $toContent
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

		$fromEntity = ( $fromContent->isRedirect() || $fromContent->getEntityHolder() === null ) ?
			$this->makeEmptyEntity() : $fromContent->getEntity();
		$toEntity = ( $toContent->isRedirect() || $toContent->getEntityHolder() === null ) ?
			$this->makeEmptyEntity() : $toContent->getEntity();

		$entityDiffer = WikibaseRepo::getEntityDiffer();
		$entityDiff = $entityDiffer->diffEntities( $fromEntity, $toEntity );

		return new EntityContentDiff( $entityDiff, $redirectDiff, $fromEntity->getType() );
	}

	/**
	 * Returns a patched copy of this Content object.
	 *
	 * @param EntityContentDiff $patch
	 *
	 * @throws PatcherException
	 * @return self
	 */
	public function getPatchedCopy( EntityContentDiff $patch ) {
		$handler = $this->getContentHandler();

		$redirAfterPatch = $this->getPatchedRedirect( $patch->getRedirectDiff() );

		if ( $redirAfterPatch ) {
			$patched = $handler->makeEntityRedirectContent( $redirAfterPatch );

			if ( !$patched ) {
				throw new PatcherException( 'Cannot create a redirect using content model '
					. $this->getModel() . '!' );
			}
		} else {
			if ( $this->isRedirect() ) {
				$entityAfterPatch = $this->makeEmptyEntity();
				$entityAfterPatch->setId( $this->getEntityId() );
			} else {
				$entityAfterPatch = $this->getEntity()->copy();
			}

			$patcher = WikibaseRepo::getEntityPatcher();
			$patcher->patchEntity( $entityAfterPatch, $patch->getEntityDiff() );

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
		if ( $this->isRedirect() ) {
			return false;
		}

		$holder = $this->getEntityHolder();
		return $holder === null || $holder->getEntity()->isEmpty();
	}

	/**
	 * @see Content::copy
	 *
	 * @return self
	 */
	public function copy() {
		$handler = $this->getContentHandler();

		if ( $this->isRedirect() ) {
			return $handler->makeEntityRedirectContent( $this->getEntityRedirect() );
		}

		$holder = $this->getEntityHolder();
		if ( $holder !== null ) {
			return $handler->makeEntityContent( new DeferredCopyEntityHolder( $holder ) );
		}

		// There is nothing mutable on an entirely empty content object.
		return $this;
	}

	/**
	 * Returns a map of properties about the entity, to be recorded in
	 * MediaWiki's page_props table. The idea is to allow efficient lookups
	 * of entities based on such properties.
	 *
	 * @return array A map from property names to property values.
	 */
	public function getEntityPageProperties() {
		return [];
	}

}
