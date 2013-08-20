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

	protected $externalPage;

	protected $changeType;

	protected $entityId;

	protected $userName;

	protected $title;

	protected $revId;

	protected $parentId;

	protected $timestamp;

	protected $comment;

	public function __construct( ExternalPage $externalPage, $changeType, $userName, $title,
		$revId, $parentId, $timestamp, $comment ) {

		$this->externalPage = $externalPage;
		$this->changeType = $changeType;
		$this->userName = $userName;
		$this->title = $title;
		$this->revId = $revId;
		$this->parentId = $parentId;
		$this->timestamp = $timestamp;
		$this->comment = $comment;
	}

	public function getChangeType() {
		return $this->changeType;
	}

	public function getEntityId() {
		$prefixedId = $this->externalTitle->getPageTitle();

		$idParser = WikibaseClient::getDefaultInstance()->getEntityIdParser();
		return $idParser->parse( $prefixedId );
	}

	public function getUserName() {
		return $this->userName;
	}

	public function getTitle() {
		return $this->title;
	}

	public function getExternalPage() {
		return $this->externalPage;
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
