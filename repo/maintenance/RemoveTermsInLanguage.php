<?php

namespace Wikibase\Repo\Maintenance;

use InvalidArgumentException;
use Maintenance;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\WikibaseRepo;
use User;
use Wikibase\WikibaseSettings;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * @license GPL-2.0-or-later
 */
class RemoveTermsInLanguage extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription( "Removes terms in the given language in the given entities." );

		$this->addOption( 'entity-id', 'Id of the entity', true, true, false, true );
		$this->addOption( 'language', 'Language to remove', true, true );
	}

	public function execute() {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->error( "You need to have Wikibase enabled in order to use this maintenance script!\n", 1 );
		}

		$repo = WikibaseRepo::getDefaultInstance();

		$idSerializations = $this->getOption( 'entity-id' );
		$language = $this->getOption( 'language' );

		$entityRevisionLookup = $repo->getEntityRevisionLookup();
		$entityStore = $repo->getEntityStore();

		foreach ( $idSerializations as $idSerialization ) {

			try {
				$entityId = $repo->getEntityIdParser()->parse( $idSerialization );
			} catch ( InvalidArgumentException $e ) {
				$this->error( "Invalid property id: " . $idSerialization, 1 );
			}

			$entityRevision = $entityRevisionLookup->getEntityRevision(
				$entityId,
				0,
				EntityRevisionLookup::LATEST_FROM_MASTER
			);

			if ( $entityRevision === null ) {
				throw new StorageException( "Could not load entity: " . $entityId->getSerialization() );
			}

			$entity = $entityRevision->getEntity();
			if ( $entity instanceof LabelsProvider ) {
				$entity->getLabels()->removeByLanguage( $language );
			}

			if ( $entity instanceof DescriptionsProvider ) {
				$entity->getDescriptions()->removeByLanguage( $language );
			}

			if ( $entity instanceof AliasesProvider ) {
				$entity->getAliasGroups()->removeByLanguage( $language );
			}

			$entityStore->saveEntity(
				$entity,
				'Removed terms in language ' . $language,
				User::newFromName( 'Maintenance script' ),
				EDIT_UPDATE,
				$entityRevision->getRevisionId()
			);
		}

		$this->output( "Successfully removed terms in language $language from " . implode( ", ", $idSerializations ) . "\n" );
	}

}

$maintClass = RemoveTermsInLanguage::class;
require_once RUN_MAINTENANCE_IF_MAIN;
