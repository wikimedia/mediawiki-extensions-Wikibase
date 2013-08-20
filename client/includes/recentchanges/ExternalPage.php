<?php

namespace Wikibase;

/**
 * Represents an external page
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ExternalPage {

	/**
	 * @var string
	 */
	protected $pageTitle;

	/**
	 * @var string
	 */
	protected $namespace;

	/**
	 * @var int
	 */
	protected $pageId;

	/**
	 * @since 0.4
	 *
	 * @param string $pageTitle
	 * @param int $pageId
	 * @param string $namespace
	 */
	public function __construct( $pageTitle, $pageId, $namespace = '' ) {
		$this->pageTitle = $pageTitle;
		$this->pageId = $pageId;
		$this->namespace = $namespace;
	}

	/**
	 * @return EntityId
	 */
	public function getEntityId() {
		return $this->entityId;
	}

	/**
	 * @return string
	 */
	public function getPageTitle() {
		return $this->pageTitle;
	}

	/**
	 * @return int
	 */
	public function getPageId() {
		return $this->pageId;
	}

	/**
	 * @return string
	 */
	public function getNamespace() {
		return $this->namespace;
	}

}
