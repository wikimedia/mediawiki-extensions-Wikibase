<?php

namespace Wikibase;

/**
 * Represents an external link
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ExternalLink {

	/**
	 * @var string
	 */
	protected $target;

	/**
	 * @var string
	 */
	protected $linkText;

	/**
 	 * @var array
	 */
	protected $linkParams;

	/**
 	 * @since 0.4
	 *
	 * @param target $target
	 * @param string $linkText
	 * @param $linkParams
	 */
	public function __construct( $target = null, $linkText = null, $linkParams = array() ) {
		$this->target = $target;
		$this->linkText = $linkText;
		$this->linkParams = $linkParams;
	}

	public function getTarget() {
		return $this->target;
	}

	public function getLinkText() {
		return $this->linkText;
	}

	public function getLinkParams() {
		return $this->linkParams;
	}

}
