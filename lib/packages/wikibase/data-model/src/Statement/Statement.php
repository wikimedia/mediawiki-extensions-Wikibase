<?php declare( strict_types=1 );

namespace Wikibase\DataModel\Statement;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\PropertyIdProvider;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * Class representing a Wikibase statement.
 * See https://www.mediawiki.org/wiki/Wikibase/DataModel#Statements
 *
 * @since 0.1
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class Statement implements PropertyIdProvider {

	/**
	 * Rank enum. Higher values are more preferred.
	 *
	 * @since 2.0
	 */
	public const RANK_PREFERRED = 2;
	public const RANK_NORMAL = 1;
	public const RANK_DEPRECATED = 0;

	/**
	 * @var string|null
	 */
	private $guid = null;

	/**
	 * @var Snak
	 */
	private $mainSnak;

	/**
	 * The property value snaks making up the qualifiers for this statement.
	 *
	 * @var SnakList
	 */
	private $qualifiers;

	/**
	 * @var ReferenceList
	 */
	private $references;

	/**
	 * @var int element of the Statement::RANK_ enum
	 */
	private $rank = self::RANK_NORMAL;

	/**
	 * @since 2.0
	 *
	 * @param Snak $mainSnak
	 * @param SnakList|null $qualifiers
	 * @param ReferenceList|null $references
	 * @param string|null $guid
	 */
	public function __construct(
		Snak $mainSnak,
		SnakList $qualifiers = null,
		ReferenceList $references = null,
		string $guid = null
	) {
		$this->mainSnak = $mainSnak;
		$this->qualifiers = $qualifiers ?: new SnakList();
		$this->references = $references ?: new ReferenceList();
		$this->setGuid( $guid );
	}

	/**
	 * @since 0.2
	 *
	 * @return string|null
	 */
	public function getGuid(): ?string {
		return $this->guid;
	}

	/**
	 * Sets the GUID of this statement.
	 *
	 * @since 0.2
	 *
	 * @param string|null $guid
	 */
	public function setGuid( ?string $guid ): void {
		$this->guid = $guid;
	}

	/**
	 * Returns the main value snak of this statement.
	 *
	 * @since 0.1
	 *
	 * @return Snak
	 */
	public function getMainSnak(): Snak {
		return $this->mainSnak;
	}

	/**
	 * Sets the main value snak of this statement.
	 *
	 * @since 0.1
	 *
	 * @param Snak $mainSnak
	 */
	public function setMainSnak( Snak $mainSnak ): void {
		$this->mainSnak = $mainSnak;
	}

	/**
	 * Returns the property value snaks making up the qualifiers for this statement.
	 *
	 * @since 0.1
	 *
	 * @return SnakList
	 */
	public function getQualifiers(): SnakList {
		return $this->qualifiers;
	}

	/**
	 * Sets the property value snaks making up the qualifiers for this statement.
	 *
	 * @since 0.1
	 *
	 * @param SnakList $propertySnaks
	 */
	public function setQualifiers( SnakList $propertySnaks ): void {
		$this->qualifiers = $propertySnaks;
	}

	/**
	 * Returns the references attached to this statement.
	 *
	 * @since 0.1
	 *
	 * @return ReferenceList
	 */
	public function getReferences(): ReferenceList {
		return $this->references;
	}

	/**
	 * Sets the references attached to this statement.
	 *
	 * @since 0.1
	 *
	 * @param ReferenceList $references
	 */
	public function setReferences( ReferenceList $references ): void {
		$this->references = $references;
	}

	/**
	 * @since 2.0
	 *
	 * @param Snak ...$snaks
	 */
	public function addNewReference( Snak ...$snaks ) {
		$this->references->addNewReference( ...$snaks );
	}

	/**
	 * Sets the rank of the statement.
	 * The rank is an element of the Statement::RANK_ enum.
	 *
	 * @since 0.1
	 *
	 * @param integer $rank
	 *
	 * @throws InvalidArgumentException
	 */
	public function setRank( int $rank ): void {
		$ranks = [ self::RANK_DEPRECATED, self::RANK_NORMAL, self::RANK_PREFERRED ];

		if ( !in_array( $rank, $ranks, true ) ) {
			throw new InvalidArgumentException( 'Invalid rank specified for statement: ' . var_export( $rank, true ) );
		}

		$this->rank = $rank;
	}

	/**
	 * @since 0.1
	 *
	 * @return integer
	 */
	public function getRank(): int {
		return $this->rank;
	}

	/**
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHash(): string {
		$hashParts = [
			sha1( $this->mainSnak->getHash() . $this->qualifiers->getHash() ),
			$this->rank,
			$this->references->getValueHash(),
		];
		return sha1( implode( '|', $hashParts ) );
	}

	/**
	 * Returns the id of the property of the main snak.
	 * Short for ->getMainSnak()->getPropertyId()
	 *
	 * @see PropertyIdProvider::getPropertyId
	 *
	 * @since 0.2
	 *
	 * @return PropertyId
	 */
	public function getPropertyId(): PropertyId {
		return $this->getMainSnak()->getPropertyId();
	}

	/**
	 * Returns a list of all Snaks on this statement. This includes the main snak and all snaks
	 * from qualifiers and references.
	 *
	 * This is a convenience method for use in code that needs to operate on all snaks, e.g.
	 * to find all referenced Entities.
	 *
	 * @return Snak[]
	 */
	public function getAllSnaks(): array {
		$snaks = [ $this->mainSnak ];

		foreach ( $this->qualifiers as $qualifier ) {
			$snaks[] = $qualifier;
		}

		/* @var Reference $reference */
		foreach ( $this->getReferences() as $reference ) {
			foreach ( $reference->getSnaks() as $referenceSnak ) {
				$snaks[] = $referenceSnak;
			}
		}

		return $snaks;
	}

	/**
	 *
	 * @since 0.7.4
	 *
	 * @param mixed $target
	 *
	 * @return bool
	 */
	public function equals( $target ): bool {
		if ( $this === $target ) {
			return true;
		}

		return $target instanceof self
			   && $this->guid === $target->guid
			   && $this->rank === $target->rank
			   && $this->mainSnak->equals( $target->mainSnak )
			   && $this->qualifiers->equals( $target->qualifiers )
			   && $this->references->equals( $target->references );
	}

	/**
	 * @see http://php.net/manual/en/language.oop5.cloning.php
	 *
	 * @since 5.1
	 */
	public function __clone() {
		$this->qualifiers = clone $this->qualifiers;
		$this->references = clone $this->references;
	}

}
