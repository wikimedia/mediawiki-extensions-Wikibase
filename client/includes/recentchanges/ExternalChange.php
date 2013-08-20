<?php

namespace Wikibase;

/**
 * Represents an external change
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ExternalChange {

	protected $changeType;

	protected $entityId;

	protected $userName;

	protected $title;

	protected $pageId;

	protected $revId;

	protected $parentId;

	protected $timestamp;

	protected $comment;

	public function __construct( $changeType, $entityId, $userName, $title, $pageId,
		$revId, $parentId, $timestamp, $comment ) {

		$this->changeType = $changeType;
		$this->entityId = $entityId;
		$this->userName = $userName;
		$this->title = $title;
		$this->pageId = $pageId;
		$this->revId = $revId;
		$this->parentId = $parentId;
		$this->timestamp = $timestamp;
		$this->comment = $comment;
	}

	public function getChangeType() {
		return $this->changeType;
	}

	public function getEntityId() {
		return $this->entityId;
	}

	public function getUserName() {
		return $this->userName;
	}

	public function getTitle() {
		return $this->title;
	}

	public function getPageId() {
		return $this->pageId;
	}

	public function getRevId() {
		return $this->revId;
	}

	public function getParentId() {
		return $this->parentId;
	}

	public function getTimestamp() {
		return $this->timestamp;
	}

	public function getComment() {
		return $this->comment;
	}

}
