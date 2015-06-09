<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Represents a redirect from one EntityId to another.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityRedirect  {

	/**
	 * @var EntityId
	 */
	private $entityId;

	/**
	 * @var EntityId
	 */
	private $targetId;

	/**
	 * @var int
	 */
	private $revisionId;

	/**
	 * @var string
	 */
	private $mwTimestamp;

	/**
	 * @param EntityId $entityId
	 * @param EntityId $targetId
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( EntityId $entityId, EntityId $targetId, $revisionId = 0, $mwTimestamp = '' ) {
		if ( $entityId->getEntityType() !== $targetId->getEntityType() ) {
			throw new InvalidArgumentException( '$entityId and $targetId must refer to the same kind of entity.' );
		}

		$this->entityId = $entityId;
		$this->targetId = $targetId;
		$this->revisionId = $revisionId;
		$this->mwTimestamp = $mwTimestamp;
	}

	/**
	 * @return EntityId
	 */
	public function getEntityId() {
		return $this->entityId;
	}

	/**
	 * @return EntityId
	 */
	public function getTargetId() {
		return $this->targetId;
	}

	/**
	 * @see Revision::getId
	 *
	 * @return int
	 */
	public function getRevisionId() {
		return $this->revisionId;
	}

	/**
	 * @see Revision::getTimestamp
	 *
	 * @return string in MediaWiki format or an empty string
	 */
	public function getTimestamp() {
		return $this->mwTimestamp;
	}

	/**
	 * @param EntityRedirect $that
	 *
	 * @return bool
	 */
	public function equals( $that ) {
		if ( $that === $this ) {
			return true;
		}

		return is_object( $that )
			&& get_class( $that ) === get_called_class()
			&& $this->entityId->equals( $that->entityId )
			&& $this->targetId->equals( $that->targetId )
			&& $this->revisionId == $that->revisionId
			&& $this->mwTimestamp == $that->mwTimestamp
			;
	}

}
