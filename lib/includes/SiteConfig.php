<?php

namespace Wikibase;

class SiteConfig {

	protected $localId;
	protected $linkInline;
	protected $linkNavigation;
	protected $forward;
	protected $allowTransclusion;

	public function __construct( $localId, $linkInline, $linkNavigation, $forward, $allowTransclusion ) {
		$this->localId = $localId;
		$this->linkInline = $linkInline;
		$this->linkNavigation = $linkNavigation;
		$this->forward = $forward;
		$this->allowTransclusion = $allowTransclusion;
	}

	/**
	 * @return string
	 */
	public function getLocalId() {
		return $this->localId;
	}

	/**
	 * @return boolean
	 */
	public function getLinkInline() {
		return $this->linkInline;
	}

	/**
	 * @return boolean
	 */
	public function getLinkNavigation() {
		return $this->linkNavigation;
	}

	/**
	 * @return boolean
	 */
	public function getForward() {
		return $this->forward;
	}

	/**
	 * @return boolean
	 */
	public function getAllowTransclusion() {
		return $this->allowTransclusion;
	}

}