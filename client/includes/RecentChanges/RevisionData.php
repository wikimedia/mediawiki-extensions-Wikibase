<?php

declare( strict_types = 1 );
namespace Wikibase\Client\RecentChanges;

/**
 * Represents a revision on a site
 *
 * @todo Merge this into ExternalChange
 *
 * @license GPL-2.0-or-later
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
	 * @var string wikitext
	 */
	protected $comment;

	/**
	 * @var string|null HTML
	 */
	protected $commentHtml;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @var int
	 */
	private $visibility;

	/**
	 * @var array
	 */
	protected $changeParams;

	/**
	 * @param string $userName
	 * @param string $timestamp
	 * @param string $comment
	 * @param string|null $commentHtml
	 * @param string $siteId
	 * @param int $visibility
	 * @param array $changeParams
	 */
	public function __construct(
		$userName,
		$timestamp,
		$comment,
		$commentHtml,
		$siteId,
		int $visibility,
		array $changeParams
	) {
		$this->userName = $userName;
		$this->timestamp = $timestamp;
		$this->comment = $comment;
		$this->commentHtml = $commentHtml;
		$this->siteId = $siteId;
		$this->visibility = $visibility;
		$this->changeParams = $changeParams;
	}

	/**
	 * @return string
	 */
	public function getUserName() {
		return $this->userName;
	}

	/**
	 * Gets the central user ID.  This should be from CentralIdLookup,
	 * with the repo wiki and client wiki being part of the same central
	 * system.
	 *
	 * @return int
	 */
	public function getCentralUserId() {
		return $this->changeParams['central_user_id'];
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
		return intval( $this->changeParams['parent_id'] );
	}

	/**
	 * @return int
	 */
	public function getVisibility(): int {
		return $this->visibility;
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
	 * @return string|null
	 */
	public function getCommentHtml() {
		return $this->commentHtml;
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
