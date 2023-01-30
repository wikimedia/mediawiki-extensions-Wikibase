<?php

declare( strict_types = 1 );

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
	 * Usage flag indicating that the entity's sitelinks (including badges) were used.
	 * This would, for example, be the case when generating language links or sister links
	 * from an entity's sitelinks, for display in the sidebar.
	 */
	public const SITELINK_USAGE = 'S';

	/**
	 * Usage flag indicating that one of the entity's labels were used.
	 * This would be the case when showing the label of a referenced entity. Note that
	 * label usage is typically tracked with a modifier specifying the label's language code.
	 */
	public const LABEL_USAGE = 'L';

	/**
	 * Usage flag indicating that one of the entity's descriptions were used.
	 * This would be the case when showing the descriptions of a referenced entity. Note that
	 * descriptions usage is typically tracked with a modifier specifying the language code.
	 */
	public const DESCRIPTION_USAGE = 'D';

	/**
	 * Usage flag indicating that the entity's local page name was used,
	 * i.e. the title of the local (client) page linked to the entity.
	 * This would be the case when linking a referenced entity to the
	 * corresponding local wiki page.
	 * This can be thought of as a special kind of sitelink usage,
	 * specifically for the sitelink for the local wiki.
	 */
	public const TITLE_USAGE = 'T';

	/**
	 * Usage flag indicating that certain statements (identified by their property id)
	 * from the entity were used.
	 * This currently implies that we also have an OTHER_USAGE or an ALL_USAGE
	 * for the same entity (STATEMENT_USAGE is never used alone).
	 */
	public const STATEMENT_USAGE = 'C';

	/**
	 * Usage flag indicating that any and all aspects of the entity
	 * were (or may have been) used.
	 */
	public const ALL_USAGE = 'X';

	/**
	 * Usage flag indicating that some aspect of the entity was changed
	 * which is not covered by any other usage flag (except "all"). That is,
	 * the specific usage flags together with the "other" flag are equivalent
	 * to the "all" flag ( S + T + L + O = X or rather O = X - S - T - L ).
	 *
	 * This currently covers alias usage and entity existence checks.
	 */
	public const OTHER_USAGE = 'O';

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
	public function __construct( EntityId $entityId, string $aspect, ?string $modifier = null ) {
		if ( !array_key_exists( $aspect, self::$aspects ) ) {
			throw new InvalidArgumentException( '$aspect must use one of the XXX_USAGE constants, "' . $aspect . '" given!' );
		}

		$this->entityId = $entityId;
		$this->aspect = $aspect;
		$this->modifier = $modifier;
		$this->identity = $entityId->getSerialization() . '#' . self::makeAspectKey( $aspect, $modifier );
	}

	public function getAspect(): string {
		return $this->aspect;
	}

	public function getModifier(): ?string {
		return $this->modifier;
	}

	/**
	 * Returns the aspect with the modifier applied.
	 * @see makeAspectKey
	 */
	public function getAspectKey(): string {
		return self::makeAspectKey( $this->aspect, $this->modifier );
	}

	public function getEntityId(): EntityId {
		return $this->entityId;
	}

	public function getIdentityString(): string {
		return $this->identity;
	}

	public function __toString(): string {
		return $this->identity;
	}

	/**
	 * @return array ( 'entityId' => string $entityId, 'aspect' => string $aspect, 'modifier' => string|null $modifier )
	 * @phan-return array{entityId:string,aspect:string,modifier:?string}
	 */
	public function asArray(): array {
		return [
			'entityId' => $this->entityId->getSerialization(),
			'aspect' => $this->aspect,
			'modifier' => $this->modifier,
		];
	}

	/**
	 * @return string One of the EntityUsage::..._USAGE constants with the modifier split off.
	 */
	public static function stripModifier( string $aspectKey ): string {
		// This is about twice as fast compared to calling $this->splitAspectKey.
		return strstr( $aspectKey, '.', true ) ?: $aspectKey;
	}

	/**
	 * Splits the given aspect key into aspect and modifier (if any).
	 * This is the inverse of makeAspectKey().
	 *
	 * @return string[] list( $aspect, $modifier )
	 */
	public static function splitAspectKey( string $aspectKey ): array {
		return array_pad( explode( '.', $aspectKey, 2 ), 2, null );
	}

	/**
	 * Composes an aspect key from aspect and modifier (if any).
	 * This is the inverse of splitAspectKey().
	 *
	 * @return string "$aspect.$modifier"
	 */
	public static function makeAspectKey( string $aspect, ?string $modifier = null ): string {
		if ( $modifier === null ) {
			return $aspect;
		}

		return "$aspect.$modifier";
	}

}
