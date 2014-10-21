<?php

namespace Wikibase\Client\Usage;

use InvalidArgumentException;

/**
 * Value object representing the entity usages on a single page.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class PageEntityUsages {

	/**
	 * @var int
	 */
	private $pageId;

	/**
	 * @var EntityUsage[]
	 */
	private $usages = array();

	/**
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $pageId, array $usages = array() ) {
		if ( !is_int( $pageId ) || $pageId < 1 ) {
			throw new InvalidArgumentException( '$pageId must be an integer > 0' );
		}

		foreach ( $usages as $usage ) {
			if ( !$usage instanceof EntityUsage ) {
				throw new InvalidArgumentException( '$usages must contain only EntityUsage objects' );
			}
		}

		$this->pageId = $pageId;
		$this->addUsages( $usages );
	}

	/**
	 * Returns the page this PageEntityUsages object applies to.
	 *
	 * @return int
	 */
	public function getPageId() {
		return $this->pageId;
	}

	/**
	 * @return EntityUsage[]
	 */
	public function getUsages() {
		return $this->usages;
	}

	/**
	 * EntityUsage[] $usages
	 */
	public function addUsages( array $usages) {
		foreach ( $usages as $usage ) {
			if ( !$usage instanceof EntityUsage ) {
				throw new InvalidArgumentException( '$usages must contain only EntityUsage objects' );
			}

			$key = $usage->getIdentityString();
			$this->usages[$key] = $usage;
		}
	}

	/**
	 * Collects all usage aspects present on the page.
	 *
	 * string[]
	 */
	public function getAspects() {
		$aspects = array();

		foreach ( $this->usages as $usage ) {
			$aspect = $usage->getAspect();
			$aspects[$aspect] = 1;
		}

		return array_keys( $aspects );
	}

}
 