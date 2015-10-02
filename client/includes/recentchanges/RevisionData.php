<?php

namespace Wikibase\Client\RecentChanges;

/**
 * Represents an revision on a site
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 *
 * @todo Merge this into ExternalChange
 *
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class RevisionData {

	/**
	 * @var string
	 */
	protected $userName;

	/**
	 * @var string
	 */
	protected $timestamp;

	/**
	 * @var string
	 */
	protected $comment;

	/**
	 * @var array
	 */
	protected $changeParams;

	/**
	 * @param string $userName
	 * @param string $timestamp
	 * @param string $comment
	 * @param string $siteId
	 * @param array $changeParams
	 */
	public function __construct( $userName, $timestamp,
		$comment, $siteId, array $changeParams
	) {
		$this->userName = $userName;
		$this->timestamp = $timestamp;
		$this->comment = $comment;
		$this->siteId = $siteId;
		$this->changeParams = $changeParams;
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
		return $this->changeParams['page_id'];
	}

	/**
	 * @return int
	 */
	public function getRevId() {
		return $this->changeParams['rev_id'];
	}

	/**
	 * @return int
	 */
	public function getParentId() {
		return $this->changeParams['parent_id'];
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

	/**
	 * @return array
	 */
	public function getChangeParams() {
		return $this->changeParams;
	}

}
