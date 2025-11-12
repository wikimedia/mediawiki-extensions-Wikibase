<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\RemoteEntity;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;

/**
 * Decorator for EntityIdParser that understands "repoPrefix:Q123"
 * (or "repoPrefix:P123", etc.) as a remote entity id for configured repositories.
 */
class RemoteEntityIdParser implements EntityIdParser {

	private EntityIdParser $innerParser;

	/** @var string[] repoName => true */
	private array $remoteRepos;

	/**
	 * @param EntityIdParser $innerParser existing parser for local ids
	 * @param string[] $remoteRepos list of allowed repo prefixes, e.g. [ 'wd', 'commons' ]
	 */
	public function __construct( EntityIdParser $innerParser, array $remoteRepos ) {
		$this->innerParser = $innerParser;
		$this->remoteRepos = array_fill_keys( $remoteRepos, true );
	}

	public function parse( $idSerialization ): EntityId {
		if ( !is_string( $idSerialization ) ) {
			// Let the inner parser handle weird inputs / errors.
			return $this->innerParser->parse( $idSerialization );
		}

		// Look for "repo:LocalId" – e.g. "wd:Q123", "wd:P31"
		$parts = explode( ':', $idSerialization, 2 );
		if ( count( $parts ) === 2 ) {
			[ $repo, $localId ] = $parts;

			if ( isset( $this->remoteRepos[$repo] ) ) {
				// Use the inner parser to interpret the local id ("Q42", "P31", etc.)
				// This gives us the right underlying EntityId type automatically.
				$localEntityId = $this->innerParser->parse( $localId );

				return new RemoteEntityId( $repo, $localEntityId );
			}
		}

		// Fallback to the original parser for local Q/P/etc ids.
		return $this->innerParser->parse( $idSerialization );
	}
}
