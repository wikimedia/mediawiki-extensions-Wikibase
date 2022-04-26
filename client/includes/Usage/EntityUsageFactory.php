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
		if ( strpos( $identity, '#' ) === false ) {
			throw new InvalidArgumentException(
				'Invalid identity string passed, must be obtained from EntityUsage::getIdentityString.'
			);
		}

		list( $entityIdSerialization, $aspectKey ) = explode( '#', $identity, 2 );
		$entityId = $this->entityIdParser->parse( $entityIdSerialization );

		if ( strpos( $aspectKey, '.' ) !== false ) {
			list( $aspect, $modifier ) = explode( '.', $aspectKey, 2 );
		} else {
			$aspect = $aspectKey;
			$modifier = null;
		}

		return new EntityUsage( $entityId, $aspect, $modifier );
	}

}
