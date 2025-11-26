<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\RemoteEntity\Hooks;

use MediaWiki\Hook\PageSaveCompleteHook;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\Content\EntityContent;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\RemoteEntity\RemoteEntityId;
use Wikibase\Repo\RemoteEntity\RemoteEntityLookup;

/**
 * Hook handler that stores remote entities in the DB mirror when
 * an entity with statements referencing remote entities is saved.
 *
 * This ensures remote entity data is persisted only when actually used
 * in saved statements, not during autocomplete/preview.
 *
 * @license GPL-2.0-or-later
 */
class RemoteEntityStorageHookHandler implements PageSaveCompleteHook {

	private EntityContentFactory $entityContentFactory;
	private RemoteEntityLookup $remoteEntityLookup;
	private bool $federatedValuesEnabled;

	public static function factory(
		EntityContentFactory $entityContentFactory,
		RemoteEntityLookup $remoteEntityLookup,
		SettingsArray $settings
	): self {
		return new self(
			$entityContentFactory,
			$remoteEntityLookup,
			$settings->getSetting( 'federatedValuesEnabled' )
		);
	}

	public function __construct(
		EntityContentFactory $entityContentFactory,
		RemoteEntityLookup $remoteEntityLookup,
		bool $federatedValuesEnabled
	) {
		$this->entityContentFactory = $entityContentFactory;
		$this->remoteEntityLookup = $remoteEntityLookup;
		$this->federatedValuesEnabled = $federatedValuesEnabled;
	}

	/**
	 * @inheritDoc
	 */
	public function onPageSaveComplete(
		$wikiPage,
		$user,
		$summary,
		$flags,
		$revisionRecord,
		$editResult
	): void {
		if ( !$this->federatedValuesEnabled ) {
			return;
		}

		$content = $revisionRecord->getContent( 'main' );
		if ( !$content instanceof EntityContent ) {
			return;
		}

		if ( !$this->entityContentFactory->isEntityContentModel( $content->getModel() ) ) {
			return;
		}

		if ( $content->isRedirect() ) {
			return;
		}

		$entity = $content->getEntity();
		if ( !$entity instanceof StatementListProvider ) {
			return;
		}

		$this->storeRemoteEntitiesFromStatements( $entity );
	}

	/**
	 * Scan all statements in the entity and store any remote entities found.
	 */
	private function storeRemoteEntitiesFromStatements( StatementListProvider $entity ): void {
		$statements = $entity->getStatements();

		foreach ( $statements as $statement ) {
			// Check main snak
			$mainSnak = $statement->getMainSnak();
			$this->storeRemoteEntityFromSnak( $mainSnak );

			// Check qualifiers
			foreach ( $statement->getQualifiers() as $qualifier ) {
				$this->storeRemoteEntityFromSnak( $qualifier );
			}

			// Check references
			foreach ( $statement->getReferences() as $reference ) {
				foreach ( $reference->getSnaks() as $snak ) {
					$this->storeRemoteEntityFromSnak( $snak );
				}
			}
		}
	}

	/**
	 * If the snak contains a RemoteEntityId value, ensure it's stored.
	 *
	 * @param \Wikibase\DataModel\Snak\Snak $snak
	 */
	private function storeRemoteEntityFromSnak( $snak ): void {
		if ( !$snak instanceof PropertyValueSnak ) {
			return;
		}

		$dataValue = $snak->getDataValue();
		if ( !$dataValue instanceof EntityIdValue ) {
			return;
		}

		$entityId = $dataValue->getEntityId();
		if ( !$entityId instanceof RemoteEntityId ) {
			return;
		}

		$conceptUri = $entityId->getSerialization();
		\wfDebugLog(
			'federation',
			"RemoteEntityStorageHookHandler: Ensuring remote entity is stored: {$conceptUri}"
		);

		$this->remoteEntityLookup->ensureStored( $conceptUri );
	}
}

diff --git a/repo/includes/RemoteEntity/RemoteEntityIdValueFormatter.php b/repo/includes/RemoteEntity/RemoteEntityIdValueFormatter.php
index 77ba021662..1ff5fb7c6e 100644
--- a/repo/includes/RemoteEntity/RemoteEntityIdValueFormatter.php
++ b/repo/includes/RemoteEntity/RemoteEntityIdValueFormatter.php
@@ -7,6 +7,7 @@ namespace Wikibase\Repo\RemoteEntity;
 use MediaWiki\Html\Html;
 use ValueFormatters\ValueFormatter;
