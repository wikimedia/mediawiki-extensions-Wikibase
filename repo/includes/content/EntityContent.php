<?php

namespace Wikibase;

use AbstractContent;
use Content;
use DataUpdate;
use Diff\DiffOp\Diff\Diff;
use ParserOptions;
use ParserOutput;
use Status;
use Title;
use User;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\EntitySearchTextGenerator;
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
		if ( is_null( $this->getEntity()->getId() ) ) {
			return false;
		}

		return true;
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
	 * @return null|EntityId
	 *
	 * @todo: Force an ID to be present; Entity objects without an ID may sense, EntityContent
	 * objects with no entity ID don't.
	 */
	public function getEntityId() {
		return $this->getEntity()->getId();
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
		/* @var EntityHandler $handler */
		$handler = $this->getContentHandler();
		$entityView = $handler->getEntityView( null, $options, null );
		$editable = !$options? true : $options->getEditSection();

		if ( $revId === null || $revId === 0 ) {
			$revId = $title->getLatestRevID();
		}

		$revision = new EntityRevision( $this->getEntity(), $revId );

		// generate HTML
		$output = $entityView->getParserOutput( $revision, $editable, $generateHtml );

		// Since the output depends on the user language, we must make sure
		// ParserCache::getKey() includes it in the cache key.
		$output->recordOption( 'userlang' );

		// register page properties
		$this->applyEntityPageProperties( $output );

		return $output;
	}

	/**
	 * @return String a string representing the content in a way useful for building a full text
	 *         search index.
	 */
	public function getTextForSearchIndex() {
		wfProfileIn( __METHOD__ );

		$entity = $this->getEntity();

		$searchTextGenerator = new EntitySearchTextGenerator();
		$text = $searchTextGenerator->generate( $entity );

		wfProfileOut( __METHOD__ );
		return $text;
	}

	/**
	 * @return String a string representing the content in a way useful for content filtering as
	 *         performed by extensions like AbuseFilter.
	 */
	public function getTextForFilters() {
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
		return $this->getEntity()->getDescription( $GLOBALS['wgLang']->getCode() );
	}

	/**
	 * @see Content::getNativeData
	 *
	 * @return array
	 */
	public function getNativeData() {
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

		$thisEntity = $this->getEntity();
		$thatEntity = $that->getEntity();

		$thisId = $thisEntity->getId();
		$thatId = $thatEntity->getId();

		if ( $thisId !== null && $thatId !== null
			&& !$thisEntity->getId()->equals( $thatId )
		) {
			return false;
		}

		return $thisEntity->equals( $thatEntity );
	}

	/**
	 * Returns a diff between this EntityContent and $other.
	 *
	 * @param EntityContent $other
	 *
	 * @return EntityContentDiff
	 */
	public function getDiff( EntityContent $other ) {
		$entityDiff = $this->getEntity()->getDiff( $other->getEntity() );
		return new EntityContentDiff( $entityDiff, new Diff() );
	}

	/**
	 * Returns a patched copy of this Content object
	 *
	 * @param EntityContentDiff $patch
	 *
	 * @return EntityContent
	 */
	public function getPatchedCopy( EntityContentDiff $patch ) {
		$patched = $this->copy();
		$patched->getEntity()->patch( $patch->getEntityDiff() );
		return $patched;
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
		return !$this->getEntity()->isEmpty();
	}

	/**
	 * @since 0.1
	 *
	 * @return bool
	 */
	public function isEmpty() {
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

		$entity = $this->getEntity()->copy();
		return $handler->makeEntityContent( $entity );
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
		if ( !$status->isOK() ) {
			wfProfileOut( __METHOD__ );
			return $status;
		}

		/* @var EntityHandler $handler */
		$handler = $this->getContentHandler();
		$status = $handler->applyOnSaveValidators( $this );

		wfProfileOut( __METHOD__ );
		return $status;
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
