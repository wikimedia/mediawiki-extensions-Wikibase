<?php

namespace Wikibase\Client\Usage;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Value object representing the usage of an entity. This includes information about
 * how the entity is used, but not where.
 *
 * @see docs/usagetracking.wiki
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class EntityUsage {

	/**
	 * Usage flag indicating that the entity's sitelinks were used.
	 * This would be the case when generating language links or sister links from
	 * an entity's sitelinks.
	 */
	const SITELINK_USAGE = 'S';

	/**
	 * Usage flag indicating that the entity's label in the local content language was used.
	 * This would be the case when showing the label of a referenced entity.
	 */
	const LABEL_USAGE = 'L';

	/**
	 * Usage flag indicating that the entity's local page name was used.
	 * This would be the case when linking a referenced entity to the
	 * corresponding local wiki page.
	 */
	const TITLE_USAGE = 'T';

	/**
	 * Usage flag indicating that any and all aspects of the entity
	 * were (or may have been) used.
	 */
	const ALL_USAGE = 'X';

	/**
	 * A list of all valid aspects
	 *
	 * @var array
	 */
	private static $aspects = array(
		self::SITELINK_USAGE,
		self::LABEL_USAGE,
		self::TITLE_USAGE,
		self::ALL_USAGE
	);

	/**
	 * @var EntityId
	 */
	private $entityId;

	/**
	 * @var string
	 */
	private $aspect;

	/**
	 * @param EntityId $entityId
	 * @param string $aspect use the XXX_USAGE constants
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( EntityId $entityId, $aspect ) {
		if ( !in_array( $aspect, self::$aspects ) ) {
			throw new InvalidArgumentException( '$aspect must use one of the XXX_USAGE constants!' );
		}

		$this->entityId = $entityId;
		$this->aspect = $aspect;
	}

	/**
	 * @return string
	 */
	public function getAspect() {
		return $this->aspect;
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
	public function getIdentityString() {
		return $this->getEntityId()->getSerialization() . '#' . $this->getAspect();
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->getIdentityString();
	}

}
