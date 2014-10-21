<?php

namespace Wikibase\Client\Usage;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;

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
	 * @return EntityUsage[] $usages EntityUsage objects keyed and sorted by identity string.
	 */
	public function getUsages() {
		return $this->usages;
	}

	/**
	 * @param array $usages
	 *
	 * @throws InvalidArgumentException
	 */
	public function addUsages( array $usages ) {
		foreach ( $usages as $usage ) {
			if ( !$usage instanceof EntityUsage ) {
				throw new InvalidArgumentException( '$usages must contain only EntityUsage objects' );
			}

			$key = $usage->getIdentityString();
			$this->usages[$key] = $usage;
		}

		ksort( $this->usages );
	}

	/**
	 * Collects all usage aspects present on the page.
	 *
	 * string[] aspect names (sorted)
	 */
	public function getAspects() {
		$aspects = array();

		foreach ( $this->usages as $usage ) {
			$aspect = $usage->getAspect();
			$aspects[$aspect] = 1;
		}

		ksort( $aspects );
		return array_keys( $aspects );
	}

	/**
	 * @param PageEntityUsages $other
	 *
	 * @return bool
	 */
	public function equals( PageEntityUsages $other ) {
		if ( !$other->getPageId() === $this->getPageId() ) {
			return false;
		} elseif( array_keys( $other->getUsages() ) != array_keys( $this->getUsages() ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Returns all entities used on the page represented by this PageEntityUsages object.
	 *
	 * @return EntityId[] List of EntityIde objects, keyed and sorted by their identity string.
	 */
	public function getEntityIds() {
		$entityIds = array();

		foreach ( $this->usages as $usage ) {
			$id = $usage->getEntityId();
			$key = $id->getSerialization();
			$entityIds[$key] = $id;
		}

		ksort( $entityIds );
		return $entityIds;
	}

	/**
	 * Returns the aspects used by the given entity on the page
	 * represented by this PageEntityUsages object.
	 *
	 * @param EntityId $id
	 *
	 * @return string[] List of aspect names, sorted.
	 */
	public function getUsageAspects( EntityId $id ) {
		$aspects = array();

		foreach ( $this->usages as $usage ) {
			if ( $id->equals( $usage->getEntityId() ) ) {
				$aspects[] = $usage->getAspect();
			}
		}

		sort( $aspects );
		return $aspects;
	}

	public function __toString() {
		$s = 'Page ' . $this->getPageId() . ' uses (';
		$s .= implode( '|', array_keys( $this->getUsages() ) );
		$s .= ')';

		return $s;
	}

}
 