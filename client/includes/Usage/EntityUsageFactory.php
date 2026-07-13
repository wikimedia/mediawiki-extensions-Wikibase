<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Usage;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityIdParser;

/**
 * Factory service for generating EntityUsage objects based on their identity strings.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class EntityUsageFactory {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	public function __construct( EntityIdParser $entityIdParser ) {
		$this->entityIdParser = $entityIdParser;
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function newFromIdentity( string $identity ): EntityUsage {
		if ( !str_contains( $identity, '#' ) ) {
			throw new InvalidArgumentException(
				'Invalid identity string passed, must be obtained from EntityUsage::getIdentityString.'
			);
		}

		[ $entityIdSerialization, $aspectKey ] = explode( '#', $identity, 2 );
		$entityId = $this->entityIdParser->parse( $entityIdSerialization );

		$parts = explode( '.', $aspectKey, 2 );
		$aspect = $parts[0];
		$modifier = $parts[1] ?? null;

		return new EntityUsage( $entityId, $aspect, $modifier );
	}

}
