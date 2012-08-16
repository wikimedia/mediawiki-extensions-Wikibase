<?php

namespace Wikibase;

use WikiPage, Title, User;

/**
 * Abstract content object for articles representing Wikibase entities.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 * @ingroup Content
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class EntityContent extends \AbstractContent {

	/**
	 * @since 0.1
	 * @var WikiPage|false
	 */
	protected $wikiPage = false;

	/**
	 * Returns the WikiPage for the item or false if there is none.
	 *
	 * @since 0.1
	 *
	 * @return WikiPage|false
	 */
	public function getWikiPage() {
		if ( $this->wikiPage === false ) {
			$this->wikiPage = $this->isNew() ? false : $this->getContentHandler()->getWikiPageForId( $this->getEntity()->getId() );
		}

		return $this->wikiPage;
	}

	/**
	 * Returns the Title for the item or false if there is none.
	 *
	 * @since 0.1
	 *
	 * @return Title|false
	 */
	public function getTitle() {
		$wikiPage = $this->getWikiPage();
		return $wikiPage === false ? false : $wikiPage->getTitle();
	}

	/**
	 * Returns if the item has an ID set or not.
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isNew() {
		return is_null( $this->getEntity()->getId() );
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
	public abstract function getEntity();

	/**
	 * @return String a string representing the content in a way useful for building a full text search index.
	 */
	public function getTextForSearchIndex() {
		$text = implode( "\n", $this->getEntity()->getLabels() );

		foreach ( $this->getEntity()->getAllAliases() as $aliases ) {
			$text .= "\n" . implode( "\n", $aliases );
		}

		return $text;
	}

	/**
	 * @return String the wikitext to include when another page includes this  content, or false if the content is not
	 *		 includable in a wikitext page.
	 */
	public function getWikitextForTransclusion() {
		return false;
	}

	/**
	 * Returns a textual representation of the content suitable for use in edit summaries and log messages.
	 *
	 * @param int $maxlength maximum length of the summary text
	 * @return String the summary text
	 */
	public function getTextForSummary( $maxlength = 250 ) {
		return $this->getEntity()->getDescription( $GLOBALS['wgLang']->getCode() );
	}

	/**
	 * Returns native representation of the data. Interpretation depends on the data model used,
	 * as given by getDataModel().
	 *
	 * @return mixed the native representation of the content. Could be a string, a nested array
	 *		 structure, an object, a binary blob... anything, really.
	 */
	public function getNativeData() {
		return $this->getEntity()->toArray();
	}

	/**
	 * returns the content's nominal size in bogo-bytes.
	 *
	 * @return int
	 */
	public function getSize()  {
		return strlen( serialize( $this->getNativeData() ) );
	}

	/**
	 * Returns true if this content is countable as a "real" wiki page, provided
	 * that it's also in a countable location (e.g. a current revision in the main namespace).
	 *
	 * @param boolean $hasLinks: if it is known whether this content contains links, provide this information here,
	 *						to avoid redundant parsing to find out.
	 * @return boolean
	 */
	public function isCountable( $hasLinks = null ) {
		// TODO: implement
		return false;
	}

	/**
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isEmpty()  {
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
		$array = array();

		foreach ( $this->getEntity()->toArray() as $key => $value ) {
			$array[$key] = is_object( $value ) ? clone $value : $value;
		}

		return static::newFromArray( $array );
	}

	/**
	 * Determin whether the given user can edit this item. Also works for items that do not yet exist in the database.
	 * In that case, the create permission is also checked.
	 *
	 * @param \User $user the user to check for (default: $wgUser)
	 * @param bool  $doExpensiveQueries whether to perform expensive checks (default: true). Should be set to false for
	 *              quick checks for the UI, but always be true for authoritative checks.
	 *
	 * @return bool whether the user is allowed to edit this item.
	 */
	public function userCanEdit( \User $user = null, $doExpensiveQueries = true ) {
		$title = $this->getTitle();

		if ( !$title ) {
			$title = Title::newFromText( "DUMMY", $this->getContentHandler()->getEntityNamespace() );

			if ( !$title->userCan( 'create', $user, $doExpensiveQueries ) ) {
				return false;
			}
		}

		if ( !$title->userCan( 'read', $user, $doExpensiveQueries ) ) {
			return false;
		}

		if ( !$title->userCan( 'edit', $user, $doExpensiveQueries ) ) {
			return false;
		}

		//@todo: check entity-specific permissions
		return true;
	}

	/*
	 * Saves the primary fields to a database table and determines this entity's id.
	 * If this entity does not exist yet (ie the id is null), it will be inserted, and the id will be set.
	 *
	 * @since 0.1
	 *
	 * @return boolean Success indicator
	 */
	protected abstract function relationalSave();

	/**
	 * Saves this item.
	 * If this item does not exist yet, it will be created (ie a new ID will be determined and a new page in the
	 * data NS created).
	 *
	 * @since 0.1
	 *
	 * @param string $summary
	 * @param null|User $user
	 * @param integer $flags
	 *
	 * @return \Status Success indicator
	 */
	public function save( $summary = '', User $user = null, $flags = 0, $baseRevId = false ) {
		$status = \Status::newGood();
		//if ( $user !== null && $baseRevId !== false ) {
		//	if ( !$this->userWasLastToEdit( $user->getId(), $baseRevId ) ) {
				// at this point we can switch to patching
		//		$status->fatal( "wikibase-error-pending-edit-conflict" );
		//		return $status;
		//	}
		//}
		// if everything is okey do an ordinary save (and yeah, with the previous code everything is ok!)
		if ( $status->isOk() ) {
			$success = $this->relationalSave();
			if ( !$success ) {
				$status->fatal( "wikibase-error-relational-save-failed" );
			}
			else {
				$status->merge(
					$this->getWikiPage()->doEditContent(
						$this,
						$summary,
						$flags | EDIT_AUTOSUMMARY,
						$baseRevId,
						$user
					)
				);
			}
		}
		return $status;
	}

	/**
	 * Check if no edits were made by other users since the given revision. Limit to 50 revisions for the
	 * sake of performance.
	 *
	 * Note that this makes the assumption that revision ids are monotonically increasing.
	 *
	 * Note that this is a variation over the same idea that is used in EditPage::userWasLastToEdit() but
	 * with the difference that this one is using the revision and not the timestamp.
	 *
	 * @param int $userId the users numeric identifier
	 * @param int $lastRevId the revision the user supplied
	 *
	 * @return bool
	 */
	public function userWasLastToEdit( $userId, $lastRevId ) {
		if ( !$userId ) return false;
		if ( !$lastRevId ) return false;
		$title = $this->getTitle();
		if ( $title === false ) return false;
		$dbw = wfGetDB( DB_MASTER );
		$res = $dbw->select( 'revision',
			'rev_user',
			array(
				'rev_page' => $title->getArticleID(),
				'rev_id > ' . intval( $lastRevId )
			),
			__METHOD__,
			array( 'ORDER BY' => 'rev_id ASC', 'LIMIT' => 50 ) );
		foreach ( $res as $row ) {
			if ( $row->rev_user != $userId ) {
				return false;
			}
		}
		return true;
	}
}