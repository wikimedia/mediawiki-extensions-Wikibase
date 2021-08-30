<?php

namespace Wikibase\Lib\Store;

use MediaWiki\Linker\LinkTarget;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;

/**
 * @license GPL-2.0-or-later
 */
class EntityLinkTargetEntityIdLookup implements LinkTargetEntityIdLookup {
	/**
	 * used e.g. for links from Commons to Wikidata in the form of d:Special:EntityPage/Q42
	 */
	private const SPECIAL_ENTITY_PAGE = 'Special:EntityPage';

	/**
	 * @var EntityNamespaceLookup
	 */
	private $namespaceLookup;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var EntitySourceDefinitions
	 */
	private $entitySourceDefinitions;
	/**
	 * @var DatabaseEntitySource
	 */
	private $localSource;

	public function __construct(
		EntityNamespaceLookup $namespaceLookup,
		EntityIdParser $entityIdParser,
		EntitySourceDefinitions $entitySourceDefinitions,
		DatabaseEntitySource $localSource
	) {
		$this->namespaceLookup = $namespaceLookup;
		$this->entityIdParser = $entityIdParser;
		$this->entitySourceDefinitions = $entitySourceDefinitions;
		$this->localSource = $localSource;
	}

	public function getEntityId( LinkTarget $linkTarget ): ?EntityId {
		return $linkTarget->isExternal()
			? $this->getEntityIdFromExternalLink( $linkTarget )
			: $this->getEntityIdFromLocalLink( $linkTarget );
	}

	private function getEntityIdFromExternalLink( LinkTarget $linkTarget ): ?EntityId {
		$potentialSpecialEntityPageParts = explode( '/', $linkTarget->getText(), 2 );
		if (
			$potentialSpecialEntityPageParts[0] !== self::SPECIAL_ENTITY_PAGE ||
			count( $potentialSpecialEntityPageParts ) < 2
		) {
			return null;
		}

		$id = $this->parseEntityId( $potentialSpecialEntityPageParts[1] );
		if ( !$id ) {
			return null;
		}

		$source = $this->entitySourceDefinitions->getDatabaseSourceForEntityType( $id->getEntityType() );
		return $source && $source->getInterwikiPrefix() === $linkTarget->getInterwiki() ? $id : null;
	}

	private function getEntityIdFromLocalLink( LinkTarget $linkTarget ): ?EntityId {
		$entityTypeForNamespace = $this->namespaceLookup->getEntityType( $linkTarget->getNamespace() );
		if ( !$entityTypeForNamespace ) {
			return null;
		}

		$entityId = $this->parseEntityId( $linkTarget->getText() );
		return (
			$entityId &&
			$entityId->getEntityType() === $entityTypeForNamespace &&
			in_array( $entityId->getEntityType(), $this->localSource->getEntityTypes() )
		) ? $entityId : null;
	}

	private function parseEntityId( string $id ): ?EntityId {
		try {
			return $this->entityIdParser->parse( $id );
		} catch ( EntityIdParsingException $e ) {
			return null;
		}
	}

}
