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
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityUsage {

	/**
	 * Usage flag indicating that the entity's sitelinks were used as links.
	 * This would be the case when generating language links or sister links from
	 * an entity's sitelinks, for display in the sidebar.
	 *
	 * @note: This does NOT cover sitelinks used in wikitext (e.g. via Lua).
	 *        Use OTHER_USAGE for that.
	 */
	const SITELINK_USAGE = 'S';

	/**
	 * Usage flag indicating that one of the entity's labels were used.
	 * This would be the case when showing the label of a referenced entity. Note that
	 * label usage is typically tracked with a modifier specifying the label's language code.
	 */
	const LABEL_USAGE = 'L';

	/**
	 * Usage flag indicating that one of the entity's descriptions were used.
	 * This would be the case when showing the descriptions of a referenced entity. Note that
	 * descriptions usage is typically tracked with a modifier specifying the language code.
	 */
	const DESCRIPTION_USAGE = 'D';

	/**
	 * Usage flag indicating that the entity's local page name was used.
	 * This would be the case when linking a referenced entity to the
	 * corresponding local wiki page.
	 */
	const TITLE_USAGE = 'T';

	/**
	 * Usage flag indicating that certain statements (identified by their property id)
	 * from the entity were used.
	 * This currently implies that we also have an OTHER_USAGE or an ALL_USAGE
	 * for the same entity (STATEMENT_USAGE is never used alone).
	 */
	const STATEMENT_USAGE = 'C';

	/**
	 * Usage flag indicating that any and all aspects of the entity
	 * were (or may have been) used.
	 */
	const ALL_USAGE = 'X';

	/**
	 * Usage flag indicating that some aspect of the entity was changed
	 * which is not covered by any other usage flag (except "all"). That is,
	 * the specific usage flags together with the "other" flag are equivalent
	 * to the "all" flag ( S + T + L + O = X or rather O = X - S - T - L ).
	 *
	 * Due to migration efforts, this is currently used redundantly with
	 * STATEMENT_USAGE or DESCRIPTION_USAGE, as they were only added later.
	 */
	const OTHER_USAGE = 'O';

	/**
	 * List of all valid aspects. Only the array keys are used, the values are meaningless.
	 *
	 * @var null[]
	 */
	private static $aspects = [
		self::SITELINK_USAGE => null,
		self::LABEL_USAGE => null,
		self::DESCRIPTION_USAGE => null,
		self::TITLE_USAGE => null,
		self::STATEMENT_USAGE => null,
		self::OTHER_USAGE => null,
		self::ALL_USAGE => null,
	];

	/**
	 * @var EntityId
	 */
	private $entityId;

	/**
	 * @var string
	 */
	private $aspect;

	/**
	 * @var null|string
	 */
	private $modifier;

	/**
	 * @var string
	 */
	private $identity;

	/**
	 * @param EntityId $entityId
	 * @param string $aspect use the EntityUsage::XXX_USAGE constants
	 * @param string|null $modifier for further qualifying the usage aspect (e.g. a language code
	 *        may be used along with the LABEL_USAGE aspect.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( EntityId $entityId, $aspect, $modifier = null ) {
		if ( !array_key_exists( $aspect, self::$aspects ) ) {
			throw new InvalidArgumentException( '$aspect must use one of the XXX_USAGE constants!' );
		}

		$this->entityId = $entityId;
		$this->aspect = $aspect;
		$this->modifier = $modifier;
		$this->identity = $entityId->getSerialization() . '#' . self::makeAspectKey( $aspect, $modifier );
	}

	/**
	 * @return string
	 */
	public function getAspect() {
		return $this->aspect;
	}

	/**
	 * @return null|string
	 */
	public function getModifier() {
		return $this->modifier;
	}

	/**
	 * Returns the aspect with the modifier applied.
	 * @see makeAspectKey
	 *
	 * @return string
	 */
	public function getAspectKey() {
		return self::makeAspectKey( $this->aspect, $this->modifier );
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
		return $this->identity;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->identity;
	}

	/**
	 * @return array( 'entityId' => string $entityId, 'aspect' => string $aspect, 'modifier' => string|null $modifier )
	 */
	public function asArray() {
		return [
			'entityId' => $this->entityId->getSerialization(),
			'aspect' => $this->aspect,
			'modifier' => $this->modifier
		];
	}

	/**
	 * @param string $aspectKey
	 *
	 * @return string One of the EntityUsage::..._USAGE constants with the modifier split off.
	 */
	public static function stripModifier( $aspectKey ) {
		// This is about twice as fast compared to calling $this->splitAspectKey.
		return strstr( $aspectKey, '.', true ) ?: $aspectKey;
	}

	/**
	 * Splits the given aspect key into aspect and modifier (if any).
	 * This is the inverse of makeAspectKey().
	 *
	 * @param string $aspectKey
	 *
	 * @return string[] list( $aspect, $modifier )
	 */
	public static function splitAspectKey( $aspectKey ) {
		$parts = explode( '.', $aspectKey, 2 );

		if ( !isset( $parts[1] ) ) {
			$parts[1] = null;
		}

		return $parts;
	}

	/**
	 * Composes an aspect key from aspect and modifier (if any).
	 * This is the inverse of splitAspectKey().
	 *
	 * @param string $aspect
	 * @param string|null $modifier
	 *
	 * @return string "$aspect.$modifier"
	 */
	public static function makeAspectKey( $aspect, $modifier = null ) {
		if ( $modifier === null ) {
			return $aspect;
		}

		return "$aspect.$modifier";
	}

}
