<?php

namespace Wikibase\Lib\Store;

use MediaWiki\Linker\LinkTarget;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;

/**
 * @license GPL-2.0-or-later
 */
class EntityLinkTargetEntityIdLookup implements LinkTargetEntityIdLookup {

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
		if ( $linkTarget->getNamespace() === -1 && explode( '/', $linkTarget->getText(), 2 )[0] === 'EntityPage' ) {
			$idText = explode( '/', $linkTarget->getText(), 2 )[1];
		} else {
			$idText = $linkTarget->getText();

			$entityTypeForNamespace = $this->entityNamespaceLookup->getEntityType( $linkTarget->getNamespace() );
			if ( !$entityTypeForNamespace ) {
				return null;
			}
		}

		try {
			$entityId = $this->entityIdParser->parse( $idText );
		} catch ( EntityIdParsingException $e ) {
			return null;
		}

		if ( isset( $entityTypeForNamespace ) && $entityId->getEntityType() !== $entityTypeForNamespace ) {
			throw new RuntimeException( 'Managed to lookup EntityId but got an unexpected type for namespace.' );
		}

		return $entityId;
	}
}
