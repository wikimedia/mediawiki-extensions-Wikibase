<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\RemoteEntity;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;

/**
 * Decorator for EntityIdParser that understands concept URIs
 * ("https://…/entity/Q123") as remote entity ids.
 * Also accepts legacy "repoName:LocalId" and maps to a concept URI
 * using configured concept bases.
 */
class RemoteEntityIdParser implements EntityIdParser {

	private EntityIdParser $innerParser;

	/** @var array<string,string> repoName => concept base URI (ends with /entity/) */
	private array $repoConceptBases;

	/**
	 * @param EntityIdParser $innerParser existing parser for local ids
	 * @param array<string,string> $repoConceptBases e.g. [ 'wikidata' => 'https://www.wikidata.org/entity/' ]
	 */
	public function __construct( EntityIdParser $innerParser, array $repoConceptBases ) {
		$this->innerParser = $innerParser;
		$this->repoConceptBases = $repoConceptBases;
	}

	public function parse( $idSerialization ): EntityId {
		if ( !is_string( $idSerialization ) ) {
			return $this->innerParser->parse( $idSerialization );
		}

		// 1) Canonical: full concept URI (https://…/entity/Q123)
		if ( preg_match( '~^https?://.+/entity/[A-Za-z]\d+$~', $idSerialization ) ) {
			// Validate local part via inner parser; throws on bad ids.
			$this->innerParser->parse( basename( $idSerialization ) );
			return new RemoteEntityId( $idSerialization );
		}

		// 2) Legacy support: "repoName:LocalId" → map to concept URI
		$parts = explode( ':', $idSerialization, 2 );
		if ( count( $parts ) === 2 ) {
			[ $repo, $localId ] = $parts;
			if ( isset( $this->repoConceptBases[$repo] ) ) {
				$this->innerParser->parse( $localId ); // validate
				return new RemoteEntityId( $this->repoConceptBases[$repo] . $localId );
			}
		}

		// Fallback to the original parser for local Q/P/etc ids.
		return $this->innerParser->parse( $idSerialization );
	}
}
