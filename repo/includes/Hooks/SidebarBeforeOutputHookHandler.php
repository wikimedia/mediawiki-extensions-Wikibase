<?php

namespace Wikibase\Repo\Hooks;

use MediaWiki\Hook\SidebarBeforeOutputHook;
use MediaWiki\Skin\Skin;
use MediaWiki\Title\Title;
use Psr\Log\LoggerInterface;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;

/**
 * @license GPL-2.0-or-later
 *
 * If in appropriate namespace and page, create additional links, including
 * Concept URI and WikiProject links for later addition to the sidebar.
 */
class SidebarBeforeOutputHookHandler implements SidebarBeforeOutputHook {

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
	private SettingsArray $wikibaseRepoSettings;

	public function __construct(
		EntityIdLookup $idLookup,
		EntityLookup $entityLookup,
		EntityNamespaceLookup $nsLookup,
		DatabaseEntitySource $localEntitySource,
		LoggerInterface $logger,
		SettingsArray $wikibaseRepoSettings
	) {
		$this->baseConceptUri = $localEntitySource->getConceptBaseUri();
		$this->idLookup = $idLookup;
		$this->entityLookup = $entityLookup;
		$this->nsLookup = $nsLookup;
		$this->logger = $logger;
		$this->wikibaseRepoSettings = $wikibaseRepoSettings;
	}

	/**
	 * When appropriate, add Concept URI link to the toolbox section, and add
	 * links to related Wikiprojects
	 *
	 * @param Skin $skin
	 * @param string[] &$sidebar
	 * @return void
	 */
	public function onSidebarBeforeOutput( $skin, &$sidebar ): void {
		$entityId = $this->getValidEntityId( $skin->getTitle() );

		if ( $entityId === null ) {
			return;
		}

		$this->addConceptUriLink( $skin, $sidebar, $entityId );
		$this->addWikiProjectLinks( $sidebar, $entityId );
	}

	/**
	 * Builds and adds a concept URI link for the sidebar toolbox.
	 *
	 * @param Skin $skin
	 * @param string[] &$sidebar
	 * @param EntityId $entityId
	 */
	private function addConceptUriLink( Skin $skin, array &$sidebar, EntityId $entityId ): void {
		$sidebar[ 'TOOLBOX' ][ 'wb-concept-uri' ] = [
			'id' => 't-wb-concept-uri',
			'text' => $skin->msg( 'wikibase-concept-uri' )->text(),
			'href' => $this->baseConceptUri . $entityId->getSerialization(),
			'title' => $skin->msg( 'wikibase-concept-uri-tooltip' )->text(),
		];
	}

	/**
	 * Determines which WikiProjects are relevant to the given Entity and if there are any, adds links to
	 * them in the sidebar.
	 *
	 * @param array &$sidebar
	 * @param EntityId $entityId
	 */
	private function addWikiProjectLinks( array &$sidebar, EntityId $entityId ): void {
		$wikiProjectsConfig = $this->wikibaseRepoSettings->getSetting( 'tmpWikiProjectsLinking' );
		if ( !$wikiProjectsConfig ) {
			return;
		}

		$entity = $this->entityLookup->getEntity( $entityId );
		if ( !( $entity instanceof StatementListProvider ) ) {
			return;
		}

		$properties = $entity->getStatements()->getPropertyIds();
		$links = [];
		foreach ( $wikiProjectsConfig as $projectConfig ) {
			if (
				!isset( $projectConfig[ 'text' ] ) ||
				!isset( $projectConfig[ 'href' ] )
			) {
				continue;
			}

			$matchesProperty = isset( $projectConfig[ 'propertyIds' ] ) &&
				is_array( $projectConfig[ 'propertyIds' ] ) &&
				count( array_intersect( array_keys( $properties ), $projectConfig[ 'propertyIds' ] ) ) > 0;

			if (
				$matchesProperty ||
				$this->itemMatchesWikiProject( $entity, $projectConfig )
			) {
				$links[] = [
					// TODO determine if/how to use translated titles rather than monolingual text from the config T425437
					'text' => $projectConfig[ 'text' ],
					'href' => $projectConfig[ 'href' ],
					'data' => [
						'mw-tracking-link-type' => 'wikiproject',
						'mw-source-entity-id' => $entityId->getSerialization(),
					],
				];
			}
		}

		if ( count( $links ) > 0 ) {
			$sidebar[ 'wikibase-wikiprojects-sidebar-section' ] = $links;
		}
	}

	private function itemMatchesWikiProject( StatementListProvider $entity, array $wikiProject ): bool {
		foreach ( $wikiProject[ 'statements' ] ?? [] as $propertyId => $entityIds ) {
			$statements = $entity->getStatements()->getByPropertyId(
				new NumericPropertyId( $propertyId )
			);

			foreach ( $statements as $statement ) {
				$mainSnak = $statement->getMainSnak();

				if ( !$mainSnak instanceof PropertyValueSnak ) {
					continue;
				}

				$dataValue = $mainSnak->getDataValue();

				if ( !$dataValue instanceof EntityIdValue ) {
					continue;
				}

				if (
					in_array(
						$dataValue->getEntityId()->getSerialization(),
						$entityIds,
						true
					)
				) {
					return true;
				}
			}
		}

		return false;
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
