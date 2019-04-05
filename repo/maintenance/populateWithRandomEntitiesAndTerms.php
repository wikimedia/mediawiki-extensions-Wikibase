<?php

namespace Wikibase\Build;

use Maintenance;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\WikibaseSettings;


$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

class PopulateWithRandomEntitiesAndTerms extends Maintenance
{
	const SCRIPT_USER_NAME = 'script_populate_random_entities';
	const SUMMARY_TEXT = 'Created using PopulateWithRandomEntitiesAndTerms maintenance script';

	const OPTION_DEFAULT_AT_LEAST = 1;
	const OPTION_DEFAULT_AT_MOST = 50;
	const OPTION_DEFAULT_DUPLICATION_DEGREE = 0.5;

	private $editEntityFactory;

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Populates Wikibase db with randomly generated entities and terms' );

		$this->addOption(
			'duplication-degree',
			'Degree of desired duplication in term text across term types and languages.'
			    . ' Note that labels will always be unique regardless of this option.'
				. ' <=0 means no duplication at all, >=1 means same text for all terms per type across lanagues.'
			    . ' Default ' . self::OPTION_DEFAULT_DUPLICATION_DEGREE,
			false,
			true
		);

		$this->addOption(
			'at-least',
			'Populate at least this number of entities. Default ' . self::OPTION_DEFAULT_AT_LEAST,
			false,
			true
		);

		$this->addOption(
			'at-most',
			'Populate at most this number of entities. Default ' . self::OPTION_DEFAULT_AT_MOST,
			false,
			true
		);

		$this->addOption(
			'language',
			'Add language to be used in generated terms. Default list: '
				. implode( ',', $this->getDefaultLanguages() ),
			false,
			true,
			'l',
			true
		);

		$this->addOption(
			'entity-type',
			'Only generate this type of entity. Accepts `item` or `property`',
			true,
			true
		);

		$this->addOption(
			'without-aliases',
			'Do not add aliases to generated entities'
		);
	}

	public function execute() {
		global $wgEnablePopulateWithRandomEntitiesAndTermsScript;
		if ( $wgEnablePopulateWithRandomEntitiesAndTermsScript !== true ) {
			$this->output( "This script is not enabled! Add '\$wgEnablePopulateWithRandomEntitiesAndTermsScript = true;' to your LocalSettings.php to enable it.\n\n" );
			exit;
		}
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->output( "You need to have Wikibase enabled in order to use this maintenance script!\n\n" );
			exit;
		}
		$this->editEntityFactory = WikibaseRepo::getDefaultInstance()->newEditEntityFactory();

		$entityType = $this->getOption('entity-type');
		if ( $entityType !== 'item' && $entityType !== 'property' ) {
			$this->error('entity-type accepts only item or property as values');
			$this->maybeHelp( true );
		}

		$nrOfEntities = $this->getNrOfEntities();

		$languages = $this->getOption( 'language', $this->getDefaultLanguages() );

		$this->generateEntities(
			$entityType,
			$nrOfEntities,
			$languages
		);
	}


	private function saveEntity( $entity ) {
		$editEntity = $this->editEntityFactory->newEditEntity(
			User::newSystemUser( self::SCRIPT_USER_NAME, [ 'steal' => true ] )
		);

		$status = $editEntity->attemptSave( $entity, self::SUMMARY_TEXT, EDIT_NEW, false );

		if ( !$status->isOk() ) {
			$this->output( "Failed to save entity.\n\n" );
			$this->output( print_r( $status->getValue(), true ) );
			exit;
		}

		return $editEntity->getEntityId();
	}

	private function generateEntities( $entityType, $nrOfEntities, $languages ) {
		$duplicationDegree = $this->getDuplicationDegree();

		$labelTextGenerator = $this->createTextGenerator();
		$descriptionTextGenerator = $this->createTextGenerator( $duplicationDegree );
		$aliasTextGenerator = $this->createTextGenerator( $duplicationDegree );

		for ( ; $nrOfEntities > 0; $nrOfEntities-- ) {
			$entity = $entityType === 'item'
					? new Item( null, new Fingerprint() )
					: new Property( null, new Fingerprint(), 'string' );

			$this->addLabelsToEntity( $entity, $languages, $labelTextGenerator );
			$this->addDescriptionsToEntity( $entity, $languages, $descriptionTextGenerator );

			if ( !$this->hasOption( 'without-aliases' ) ) {
				$this->addAliasesToEntity( $entity, $languages, $aliasTextGenerator );
			}

			$entityId = $this->saveEntity( $entity );
			$this->output( $entityId->getSerialization() . "\n" );
		}
		$this->output("\n");
	}

	private function getDuplicationDegree() {
		$duplicationDegree = (float)$this->getOption(
			'duplication-degree',
			self::OPTION_DEFAULT_DUPLICATION_DEGREE
		);
		$duplicationDegree = $duplicationDegree > 1
						   ? 1
						   : $duplicationDegree < 0
						   ? 0
						   : $duplicationDegree;

		return $duplicationDegree;
	}

	private function addLabelsToEntity( $entity, $languages, $textGenerator ) {
		foreach ( $languages as $language ) {
			$termText = $textGenerator->current();
			$textGenerator->next();

			$entity->getFingerprint()->setLabel( $language, $termText );
		}
	}

	private function addDescriptionsToEntity( $entity, $languages, $textGenerator ) {
		foreach ( $languages as $language ) {
			$termText = $textGenerator->current();
			$textGenerator->next();

			$entity->getFingerprint()->setDescription( $language, $termText );
		}
	}

	private function addAliasesToEntity( $entity, $languages, $textGenerator ) {
		foreach ( $languages as $language ) {
			$termText = $textGenerator->current();
			$textGenerator->next();

			$entity->getFingerprint()->setAliasGroup( $language, [ $termText ] );
		}
	}

	private function getNrOfEntities() {
		$minNrOfEntities = abs( (int)$this->getOption( 'at-least', self::OPTION_DEFAULT_AT_LEAST ) );
		$maxNrOfEntities = abs( (int)$this->getOption( 'at-most', self::OPTION_DEFAULT_AT_MOST ) );

		return rand( $minNrOfEntities, $maxNrOfEntities );
	}

	private function createTextGenerator( $duplicationDegree = 0 ) {
		$prevText = null;

		while ( true ) {
			if ( $prevText === null || $duplicationDegree < ( rand() / getrandmax() ) ) {
				$prevText = md5( random_bytes( 10 ) );
			}

			yield $prevText;
		}
	}

	private function getDefaultLanguages() {
		return [ 'de', 'en', 'fr', 'zh', 'es', 'ru', 'eo' ];
	}
}


$maintClass = PopulateWithRandomEntitiesAndTerms::class;
require_once RUN_MAINTENANCE_IF_MAIN;
