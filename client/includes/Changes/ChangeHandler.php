<?php

namespace Wikibase\Client\Changes;

use InvalidArgumentException;
use MWException;
use Title;
use Wikibase\Change;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\DataModel\Entity\Diff\EntityDiff;
use Wikibase\DataModel\Entity\Diff\ItemDiff;
use Wikibase\EntityChange;
use Wikibase\ItemChange;
use Wikibase\SiteLinkCommentCreator;
use Wikibase\Lib\Store\StorageException;

/**
 * Interface for change handling. Whenever a change is detected,
 * it should be fed to this service which then takes care handling
 * it.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ChangeHandler {

	/**
	 * The change requites any rendered version of the page to be purged from the parser cache.
	 */
	const PARSER_PURGE_ACTION = 1;

	/**
	 * The change requites a LinksUpdate job to be scheduled to update any links
	 * associated with the page.
	 */
	const LINKS_UPDATE_ACTION = 2;

	/**
	 * The change requites any HTML output generated from the page to be purged from web cached.
	 */
	const WEB_PURGE_ACTION = 4;

	/**
	 * The change requites an entry to be injected into the recentchanges table.
	 */
	const RC_ENTRY_ACTION = 8;

	/**
	 * The change requites an entry to be injected into the revision table.
	 */
	const HISTORY_ENTRY_ACTION = 16;

	/**
	 * @var EntityChangeFactory
	 */
	private $changeFactory;

	/**
	 * @var AffectedPagesFinder
	 */
	private $affectedPagesFinder;

	/**
	 * @var Client\Store\TitleFactory
	 */
	private $titleFactory;

	/**
	 * @var PageUpdater $updater
	 */
	private $updater;

	/**
	 * @var ChangeListTransformer
	 */
	private $changeListTransformer;

	/**
	 * @var string
	 */
	private $localSiteId;

	public function __construct(
		AffectedPagesFinder $affectedPagesFinder,
		TitleFactory $titleFactory,
		PageUpdater $updater,
		ChangeListTransformer $changeListTransformer,
		$localSiteId,
		$injectRC,
		$allowDataTransclusion
	) {
		$this->changeListTransformer = $changeListTransformer;
		$this->affectedPagesFinder = $affectedPagesFinder;
		$this->titleFactory = $titleFactory;
		$this->updater = $updater;

		if ( !is_string( $localSiteId ) ) {
			throw new InvalidArgumentException( '$localSiteId must be a string' );
		}

		if ( !is_bool( $injectRC ) ) {
			throw new InvalidArgumentException( '$injectRC must be a bool' );
		}

		if ( !is_bool( $allowDataTransclusion ) ) {
			throw new InvalidArgumentException( '$allowDataTransclusion must be a bool' );
		}

		$this->localSiteId = $localSiteId;
		$this->injectRC = (bool)$injectRC;
		$this->dataTransclusionAllowed = $allowDataTransclusion;
	}

	/**
	 * Handle the provided changes.
	 *
	 * @since 0.1
	 *
	 * @param Change[] $changes
	 */
	public function handleChanges( array $changes ) {
		wfProfileIn( __METHOD__ );

		$changes = $this->changeListTransformer->transformChangeList( $changes );

		if ( !wfRunHooks( 'WikibaseHandleChanges', array( $changes ) ) ) {
			wfProfileOut( __METHOD__ );
			return;
		}

		foreach ( $changes as $change ) {
			if ( !wfRunHooks( 'WikibaseHandleChange', array( $change ) ) ) {
				continue;
			}

			$this->handleChange( $change );
		}

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Main entry point for handling changes
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/WikibasePollHandle
	 *
	 * @since 0.1
	 *
	 * @param Change $change
	 *
	 * @throws MWException
	 * @return bool
	 */
	public function handleChange( Change $change ) {
		wfProfileIn( __METHOD__ );

		$chid = self::getChangeIdForLog( $change );
		wfDebugLog( __CLASS__, __FUNCTION__ . ": handling change #$chid"
			. " (" . $change->getType() . ")" );

		//TODO: Actions may be per-title, depending on how the change applies to that page.
		//      We'll need on list of titles per action.
		$actions = $this->getActions( $change );

		if ( $actions === 0 ) {
			// nothing to do
			wfDebugLog( __CLASS__, __FUNCTION__ . ": No actions to take for change #$chid." );
			wfProfileOut( __METHOD__ );
			return false;
		}

		$titlesToUpdate = $this->getPagesToUpdate( $change );

		if ( empty( $titlesToUpdate ) ) {
			// nothing to do
			wfDebugLog( __CLASS__, __FUNCTION__ . ": No pages to update for change #$chid." );
			wfProfileOut( __METHOD__ );
			return false;
		}

		wfDebugLog( __CLASS__, __FUNCTION__ . ": updating " . count( $titlesToUpdate )
			. " pages (actions: " . dechex( $actions ). ") for change #$chid." );

		$this->updatePages( $change, $actions, $titlesToUpdate );

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * Returns the pages that need some kind of updating given the change.
	 *
	 * @param Change $change
	 *
	 * @return Title[] the titles of the pages to update
	 */
	public function getPagesToUpdate( Change $change ) {
		wfProfileIn( __METHOD__ );

		$usages = $this->affectedPagesFinder->getPagesToUpdate( $change );
		$pagesToUpdate = $this->getTitlesFromPageEntityUsages( $usages );

		wfProfileOut( __METHOD__ );

		return $pagesToUpdate;
	}

	/**
	 * @param PageEntityUsages[]|Iterator<PageEntityUsages> $pageIds
	 *
	 * @return Title[]
	 */
	private function getTitlesFromPageEntityUsages( $usages ) {
		$titles = array();

		foreach ( $usages as $pageEntityUsages ) {
			try {
				$pid = $pageEntityUsages->getPageId();
				$titles[] = $this->titleFactory->newFromID( $pid );
			} catch ( StorageException $ex ) {
				// Page probably got deleted just now. Skip it.
			}
		}

		return $titles;
	}

	/**
	 * Main entry point for handling changes
	 *
	 * @since    0.4
	 *
	 * @param Change   $change         the change to apply to the pages
	 * @param int      $actions        a bit field of actions to take, as returned by getActions()
	 * @param Title[] $titlesToUpdate the pages to update
	 */
	public function updatePages( Change $change, $actions, array $titlesToUpdate ) {
		wfProfileIn( __METHOD__ );

		if ( ( $actions & self::PARSER_PURGE_ACTION ) > 0 ) {
			$this->updater->purgeParserCache( $titlesToUpdate );
		}

		if ( ( $actions & self::WEB_PURGE_ACTION ) > 0 ) {
			$this->updater->purgeWebCache( $titlesToUpdate );
		}

		if ( ( $actions & self::LINKS_UPDATE_ACTION ) > 0 ) {
			$this->updater->scheduleRefreshLinks( $titlesToUpdate );
		}

		/* @var Title $title */
		foreach ( $titlesToUpdate as $title ) {
			if ( $this->injectRC && ( $actions & self::RC_ENTRY_ACTION ) > 0 ) {
				$rcAttribs = $this->getRCAttributes( $change );

				if ( $rcAttribs !== false ) {
					$this->updater->injectRCRecord( $title, $rcAttribs );
				} else {
					trigger_error( "change #" . self::getChangeIdForLog( $change )
						. " did not provide RC info", E_USER_WARNING );
				}
			}

			//TODO: handling for self::HISTORY_ENTRY_ACTION goes here.
			//      should probably be $this->updater->injectHistoryRecords() or some such.
		}

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Returns a human readable change ID, containing multiple IDs in case of a
	 * coalesced change.
	 *
	 * @param Change $change
	 *
	 * @return string
	 */
	private static function getChangeIdForLog( Change $change ) {
		$fields = $change->getFields(); //@todo: add getFields() to the interface, or provide getters!

		if ( isset( $fields['info']['change-ids'] ) ) {
			$chid = implode( '|', $fields['info']['change-ids'] );
		} else {
			$chid = $change->getId();
		}

		return $chid;
	}

	/**
	 * Constructs RC attributes for the given change
	 *
	 * @since 0.4
	 *
	 * @param EntityChange $change The Change that caused the update
	 *
	 * @return array|boolean an array of RC attributes,
	 *         or false if the change does not provide edit meta data
	 */
	private function getRCAttributes( EntityChange $change ) {
		wfProfileIn( __METHOD__ );

		$rcinfo = $change->getMetadata();

		if ( !is_array( $rcinfo ) ) {
			wfProfileOut( __METHOD__ );
			return false;
		}

		$fields = $change->getFields(); //@todo: add getFields() to the interface, or provide getters!
		$fields['entity_type'] = $change->getEntityType();

		if ( $change instanceof ItemChange ) {
			$rcinfo['comment'] = $this->getEditComment( $change );

			if ( isset( $fields['info']['changes'] ) ) {
				$rcinfo['composite-comment'] = array();

				foreach ( $fields['info']['changes'] as $part ) {
					$rcinfo['composite-comment'][] = $this->getEditComment( $part );
				}
			}
		}

		unset( $fields['info'] );

		$rcinfo = array_merge( $fields, $rcinfo );

		$params = array(
			'wikibase-repo-change' => array_merge( $fields, $rcinfo )
		);

		wfProfileOut( __METHOD__ );
		return $params;
	}

	/**
	 * Determine which actions to take for the given change.
	 *
	 * @since 0.4
	 *
	 * @param Change $change the change to get the action for
	 *
	 * @return int actions to take, as a bit field using the XXX_ACTION flags
	 */
	public function getActions( Change $change ) {
		wfProfileIn( __METHOD__ );

		$actions = 0;

		if ( $change instanceof ItemChange ) {
			$diff = $change->getDiff();

			if ( $diff instanceof ItemDiff && !$diff->getSiteLinkDiff()->isEmpty() ) {
				//TODO: make it so we don't have to re-render
				//      if only the site links changed (see bug 45534)
				$actions |= self::PARSER_PURGE_ACTION | self::WEB_PURGE_ACTION | self::LINKS_UPDATE_ACTION
					| self::RC_ENTRY_ACTION | self::HISTORY_ENTRY_ACTION;
			}

			if ( $this->dataTransclusionAllowed ) {
				if ( $diff instanceof EntityDiff && !$diff->getClaimsDiff()->isEmpty() ) {
					$actions |= self::PARSER_PURGE_ACTION | self::WEB_PURGE_ACTION | self::LINKS_UPDATE_ACTION
						| self::RC_ENTRY_ACTION | self::HISTORY_ENTRY_ACTION;
				}

				if ( $diff instanceof EntityDiff && !$diff->getLabelsDiff()->isEmpty() ) {
					$actions |= self::PARSER_PURGE_ACTION | self::WEB_PURGE_ACTION | self::LINKS_UPDATE_ACTION
						| self::RC_ENTRY_ACTION | self::HISTORY_ENTRY_ACTION;
				}
			}
		}

		wfProfileOut( __METHOD__ );
		return $actions;
	}

	/**
	 * Returns the comment as structured array of information, to be
	 * stored in recent change entries and used to display formatted
	 * comments for wikibase changes in recent changes, watchlist, etc.
	 *
	 * @since 0.4
	 *
	 * @param EntityChange $change the change to get a comment for
	 *
	 * @throws MWException
	 * @return array
	 */
	public function getEditComment( EntityChange $change ) {
		$commentCreator = new SiteLinkCommentCreator(
			$this->localSiteId
		);

		//FIXME: this will only work for instances of ItemChange
		$siteLinkDiff = $change->getSiteLinkDiff();
		$action = $change->getAction();
		$comment = $change->getComment();

		$editComment = $commentCreator->getEditComment( $siteLinkDiff, $action, $comment );
		if( is_array( $editComment ) && !isset( $editComment['message'] ) ) {
			throw new MWException( 'getEditComment returned an empty comment' );
		}

		return $editComment;
	}

}
