<?php

namespace Wikibase\Repo\Hooks;

use Psr\Log\LoggerInterface;
use Skin;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;

/**
 * @license GPL-2.0-or-later
 *
 * If in appropriate namespace and page, create a Concept URI link
 * for later addition to the sidebar.
 */
class SidebarBeforeOutputHookHandler {

	/**
	 * @var string
	 */
	private $baseConceptUri;
	/**
	 * @var EntityIdLookup
	 */
	private $idLookup;
	/**
	 * @var EntityLookup
	 */
	private $entityLookup;
	/**
	 * @var EntityNamespaceLookup
	 */
	private $nsLookup;
	/**
	 * @var LoggerInterface
	 */
	private $logger;

	public function __construct(
		string $baseConceptUri,
		EntityIdLookup $idLookup,
		EntityLookup $entityLookup,
		EntityNamespaceLookup $nsLookup,
		LoggerInterface $logger
	) {
		$this->baseConceptUri = $baseConceptUri;
		$this->idLookup = $idLookup;
		$this->entityLookup = $entityLookup;
		$this->nsLookup = $nsLookup;
		$this->logger = $logger;
	}

	/**
	 * Build concept URI link for the sidebar toolbox.
	 *
	 * @param Skin $skin
	 * @return string[]|null Array of link elements or Null if link cannot be created.
	 */
	public function buildConceptUriLink( Skin $skin ): ?array {
		$title = $skin->getTitle();
		$entityId = $this->getValidEntityId( $title );

		if ( $title === null || $entityId === null ) {
			return null;
		}

		return [
			'id' => 't-wb-concept-uri',
			'text' => $skin->msg( 'wikibase-concept-uri' )->text(),
			'href' => $this->baseConceptUri . $entityId->getSerialization(),
			'title' => $skin->msg( 'wikibase-concept-uri-tooltip' )->text(),
		];
	}

	/**
	 * Get a valid entity id, based on a series of checks
	 *
	 * @param Title|null $title
	 * @return EntityId|null
	 */
	private function getValidEntityId( ?Title $title ): ?EntityId {
		if ( $title === null ) {
			return null;
		}

		if ( !$this->nsLookup->isNamespaceWithEntities( $title->getNamespace() ) ) {
			return null;
		}

		$entityId = $this->idLookup->getEntityIdForTitle( $title );

		if ( $entityId === null ) {
			return null;
		}

		// As per T243779, a concept uri should be built for redirects, so the hasEntity check is skipped
		if ( $title->isRedirect() ) {
			return $entityId;
		}

		try {
			// Check if the entity exists
			// Placing in try catch block since there are cases where `hasEntity` throws an exception
			if ( !$this->entityLookup->hasEntity( $entityId ) ) {
				return null;
			}
		} catch ( EntityLookupException $error ) {
			$this->logger->warning( 'Could not lookup entity for id {id}: {exception}', [
				'id' => $entityId->getSerialization(),
				'exception' => $error->getMessage(),
			] );

			return null;
		}

		return $entityId;
	}
}
