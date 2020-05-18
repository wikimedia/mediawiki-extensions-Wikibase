<?php

namespace Wikibase\Lib\Store;

use MediaWiki\Linker\LinkTarget;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;

/**
 * In the context of this class for lack of a better name a "simple entity" is an entity
 * which has a title whose main text is a parseable entity ID.
 * For example: Property:P123 or Item:Q3
 */
class SimpleEntityLinkTargetEntityIdLookup implements LinkTargetEntityIdLookup {

	/**
	 * @var EntityNamespaceLookup
	 */
	private $entityNamespaceLookup;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	public function __construct( EntityNamespaceLookup $entityNamespaceLookup, EntityIdParser $entityIdParser ) {
		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->entityIdParser = $entityIdParser;
	}

	public function getEntityId( LinkTarget $linkTarget ): ?EntityId {
		$entityTypeForNamespace = $this->entityNamespaceLookup->getEntityType( $linkTarget->getNamespace() );
		if ( !$entityTypeForNamespace ) {
			return null;
		}

		try {
			$entityId = $this->entityIdParser->parse( $linkTarget->getText() );
		} catch ( EntityIdParsingException $e ) {
			return null;
		}

		if ( $entityId->getEntityType() !== $entityTypeForNamespace ) {
			throw new RuntimeException( 'Managed to lookup EntityId but got an unexpected type for namespace.' );
		}

		return $entityId;
	}
}
