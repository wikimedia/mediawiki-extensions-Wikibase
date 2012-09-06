<?php

namespace Wikibase;

use WikiPage, Title, User, Status;

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
	 * @var EditEntity|false
	 */
	protected $editEntity = null;

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
	 * Returns the EditEntity for the item or false if there is none.
	 *
	 * @since 0.1
	 *
	 * @return EditEntity|false
	 */
	public function getEditEntity() {
		return $this->editEntity;
	}

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
	 * Checks whether the user can perform the given action on this entity.
	 *
	 * Shorthand for $this->checkPermission( $permission )->isOK();
	 *
	 * @param String    $permission         the permission to check
	 * @param null|User $user               the user to check for. If omitted, $wgUser is checked.
	 * @param bool      $doExpensiveQueries whether to perform expensive checks (default: true). May be set to false for
	 *                                      non-critical checks.
	 *
	 * @return bool True if the user has the given permission, false otherwise.
	 */
	public function userCan( $permission, User $user = null, $doExpensiveQueries = true ) {
		$status = $this->checkPermission( $permission, $user, $doExpensiveQueries );
		return $status->isOK();
	}

	/**
	 * Determine whether the given user can edit this entity.
	 *
	 * Shorthand for $this->userCan( 'edit' );
	 *
	 * @param null|User $user               the user to check for. If omitted, $wgUser is checked.
	 * @param bool      $doExpensiveQueries whether to perform expensive checks (default: true). May be set to false for
	 *                                      non-critical checks.
	 *
	 * @return bool whether the user is allowed to edit this item.
	 */
	public function userCanEdit( \User $user = null, $doExpensiveQueries = true ) {
		return $this->userCan( 'edit', $user, $doExpensiveQueries );
	}

	/**
	 * Checks whether the user can perform the given action.
	 *
	 * @param String    $permission         the permission to check
	 * @param null|User $user               the user to check for. If omitted, $wgUser is checked.
	 * @param bool      $doExpensiveQueries whether to perform expensive checks (default: true). May be set to false for
	 *                                      non-critical checks.
	 *
	 * @return \Status a status object representing the check's result.
	 */
	public function checkPermission( $permission, User $user = null, $doExpensiveQueries = true ) {
		global $wgUser;
		static $dummyTitle = null;

		if ( !$user ) {
			$user = $wgUser;
		}

		$title = $this->getTitle();
		$errors = null;

		if ( !$title ) {
			if ( !$dummyTitle ) {
				$dummyTitle = Title::makeTitleSafe( WB_NS_DATA, '/' );
			}

			$title = $dummyTitle;

			if ( $permission == 'edit' ) {
				// when checking for edit rights on an item that doesn't yet exists, check create rights first.

				$errors = $title->getUserPermissionsErrors( 'createpage', $user, $doExpensiveQueries );
			}
		}

		if ( empty( $errors ) ) {
			// only do this if we don't already have errors from an earlier check, to avoid redundant messages
			$errors = $title->getUserPermissionsErrors( $permission, $user, $doExpensiveQueries );
		}

		$status = Status::newGood();

		foreach ( $errors as $error ) {
			call_user_func_array( array( $status, 'error'), $error );
			$status->setResult( false );
		}

		return $status;
	}

	/**
	 * Assigns a fresh ID to this entity.
	 *
	 * @throws \MWException if this entity already has an ID assigned, or something goes wrong while generating a new ID.
	 * @return int the new ID
	 */
	protected function grabFreshId() {
		if ( !$this->isNew() ) {
			throw new \MWException( "This entiy already has an ID!" );
		}

		$idGenerator = StoreFactory::getStore()->newIdGenerator();

		$id = $idGenerator->getNewId( $this->getContentHandler()->getModelID() );

		$this->getEntity()->setId( $id );

		return $id;
	}

	/**
	 * Saves this item.
	 * If this item does not exist yet, it will be created (ie a new ID will be determined and a new page in the
	 * data NS created).
	 *
	 * @note: if the save is triggered by any kind of user interaction, consider using EditEntity::attemptSave(), which
	 *        automatically handles edit conflicts, permission checks, etc.
	 *
	 * @note: this method should not be overloaded, and should not be extended to save additional information to the
	 *        database. Such things should be done in a way that will also be triggered when the save is performed by
	 *        calling WikiPage::doEditContent.
	 *
	 * @since 0.1
	 *
	 * @param string     $summary
	 * @param null|User  $user
	 * @param integer    $flags
	 *
	 * @param int|bool   $baseRevId
	 * @param EditEntity $editEntity
	 *
	 * @see WikiPage::doEditContent
	 *
	 * @return \Status Success indicator
	 */
	public function save( $summary = '', User $user = null, $flags = 0, $baseRevId = false, EditEntity $editEntity = null ) {

		//XXX: really allow creation by default? Or require EDIT_CREATE in flags?
		if ( $this->isNew() ) {
			$this->grabFreshId();
		}

		//XXX: very ugly and brittle hack to pass info to prepareEdit so we can check inside a db transaction
		//     whether an edit has occurred after EditEntity checked for conflicts. If we had nested
		//     database transactions, we could simply check here.
		$this->editEntity = $editEntity;

		// NOTE: make sure we start saving from a clean slate. Calling WikiPage::clear may cause the old content
		//       to be loaded from the database again. This may be necessary, because EntityContent is mutable,
		//       so the cached object might have changed.
		//
		//       The relevant test case is ItemContentTest::restRepeatedSave
		//
		//       We may be able to optimize this by only calling WikiPage::clear if
		//       $this->getWikiPage()->getContent() == $this, but that needs further investigation.

		$page = $this->getWikiPage();
		$page->clear();

		$status = $page->doEditContent(
			$this,
			$summary,
			$flags | EDIT_AUTOSUMMARY,
			$baseRevId,
			$user
		);

		$this->editEntity = null;

		return $status;
	}

	/**
	 *
	 * @param WikiPage $page
	 * @param int      $flags
	 * @param int      $baseRevId
	 * @param User     $user
	 *
	 * @return \Status
	 * @see Content::prepareSave()
	 */
	public function prepareSave( WikiPage $page, $flags, $baseRevId, User $user ) {
		// Chain to parent
		$status = parent::prepareSave( $page, $flags, $baseRevId, $user );
		if ( !$status->isOK() ) {
			return $status;
		}

		// If editEntity is set, check whether the current revision is still what the EditEntity though it was.
		// If it isn't, then someone managed to squeeze in an edit after we checked for conflicts.
		if ( $this->editEntity !== null && $page->getRevision() !== null ) {
			if ( $page->getRevision()->getId() !==  $this->editEntity->getCurrentRevisionId() ) {
				wfDebug( 'encountered late edit conflict: current revision changed after regular conflict check.' );
				$status->fatal('edit-conflict');
			}
		}

		return $status;
	}

}
