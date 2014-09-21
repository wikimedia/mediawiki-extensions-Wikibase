<?php

namespace Wikibase\Client\RecentChanges;

/**
 * Represents an revision on a site
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RevisionData {

	/**
	 * @var string
	 */
	protected $userName;

	/**
	 * @var int
	 */
	protected $pageId;

	/**
	 * @var int
	 */
	protected $revId;

	/**
	 * @var int
	 */
	protected $parentId;

	/**
	 * @var string
	 */
	protected $timestamp;

	/**
	 * @var string
	 */
	protected $comment;

	/**
	 * @param string $userName
	 * @param int $pageId
	 * @param int $revId
	 * @param int $parentId
	 * @param string $timestamp
	 * @param string $comment
	 * @param string $siteId
	 */
	public function __construct( $userName, $pageId, $revId, $parentId, $timestamp,
		$comment, $siteId
	) {
		$this->userName = $userName;
		$this->pageId = $pageId;
		$this->revId = $revId;
		$this->parentId = $parentId;
		$this->timestamp = $timestamp;
		$this->comment = $comment;
		$this->siteId = $siteId;
	}

	/**
	 * @return string
	 */
	public function getUserName() {
		return $this->userName;
	}

	/**
	 * @return int
	 */
	public function getPageId() {
		return $this->pageId;
	}

	/**
	 * @return int
	 */
	public function getRevId() {
		return $this->revId;
	}

	/**
	 * @return int
	 */
	public function getParentId() {
		return $this->parentId;
	}

	/**
	 * @return string
	 */
	public function getTimestamp() {
		return $this->timestamp;
	}

	/**
	 * @return string
	 */
	public function getComment() {
		return $this->comment;
	}

	/**
	 * @return string
	 */
	public function getSiteId() {
		return $this->siteId;
	}

}
