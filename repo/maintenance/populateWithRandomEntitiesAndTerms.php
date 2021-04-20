<?php

namespace Wikibase\Repo\Maintenance;

use DerivativeContext;
use Generator;
use IContextSource;
use Maintenance;
use RequestContext;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Lib\WikibaseSettings;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false
		  ? getenv( 'MW_INSTALL_PATH' )
		  : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Populates the database with generated entities.
 *
 * Those entities will be populated with randomly generated terms (labels, descriptions, aliases),
 * in a set of different languages (can be modified through options).
 * @license GPL-2.0-or-later
 */
class PopulateWithRandomEntitiesAndTerms extends Maintenance {
	private const SCRIPT_USER_NAME = 'script_populate_random_entities';
	private const SUMMARY_TEXT = 'Created using PopulateWithRandomEntitiesAndTerms maintenance script';

	private const OPTION_DEFAULT_AT_LEAST = 1;
	private const OPTION_DEFAULT_AT_MOST = 50;
	private const OPTION_DEFAULT_DUPLICATION_DEGREE = 0.5;

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

		$this->addOption(
			'verbose',
			'Print verbose information',
			false,
			false,
			'v'
		);
	}

	public function execute() {
		if ( WikibaseRepo::getSettings()
			 ->getSetting( 'enablePopulateWithRandomEntitiesAndTermsScript' ) !== true
		) {
			$this->output(
				"This script is not enabled by default!  To enable it, add "
				. "\$wgWBRepoSettings['enablePopulateWithRandomEntitiesAndTermsScript'] = true;"
				. " to your LocalSettings.php\n"
			);
			exit;
		}
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->output( "You need to have Wikibase enabled in order to use this maintenance script!\n" );
			exit;
		}

		$entityType = $this->getOption( 'entity-type' );
		if ( $entityType !== 'item' && $entityType !== 'property' ) {
			$this->error( 'entity-type accepts only item or property as values' );
			$this->maybeHelp( true );
		}

		$verbose = $this->hasOption( 'verbose' );

		$nrOfEntities = $this->getNrOfEntities();

		$languages = $this->getOption( 'language', $this->getDefaultLanguages() );

		$entityGenerator = $this->createEntityGenerator( $entityType, $nrOfEntities, $languages );

		$startTime = microtime( true );

		$nrOfGeneratedEntities = 0;
		foreach ( $entityGenerator as $entityId ) {
			$nrOfGeneratedEntities += 1;

			$progress = $verbose
					  ? $entityId->getSerialization() . "\n"
					  : '.';
			$this->output( $progress );
		}

		$elapsed = ( microtime( true ) - $startTime );

		$this->output(
			sprintf( "\nGenerated %d entities in %f seconds.\n", $nrOfGeneratedEntities, $elapsed )
		);
	}

	private function saveEntity(
		EntityDocument $entity,
		IContextSource $context,
		MediawikiEditEntityFactory $editEntityFactory
	): ?EntityId {
		$editEntity = $editEntityFactory->newEditEntity( $context );

		$status = $editEntity->attemptSave( $entity, self::SUMMARY_TEXT, EDIT_NEW, false );

		if ( !$status->isOK() ) {
			$this->output( "\n" . $status->getValue() . "\n" );
			return null;
		} else {
			return $editEntity->getEntityId();
		}
	}

	/**
	 * @param string $entityType 'item' or 'property'
	 * @param int $nrOfEntities
	 * @param array $languages
	 *
	 * @return Generator
	 */
	private function createEntityGenerator( $entityType, $nrOfEntities, array $languages ) {
		$context = new DerivativeContext( RequestContext::getMain() );
		$user = User::newSystemUser( self::SCRIPT_USER_NAME, [ 'steal' => true ] );
		$context->setUser( $user );
		$duplicationDegree = $this->getDuplicationDegree();

		$labelTextGenerator = $this->createTextGenerator();
		$descriptionTextGenerator = $this->createTextGenerator( $duplicationDegree );
		$aliasTextGenerator = $this->createTextGenerator( $duplicationDegree );

		$editEntityFactory = WikibaseRepo::getEditEntityFactory();

		for ( ; $nrOfEntities > 0; $nrOfEntities-- ) {
			$entity = $entityType === 'item'
					? new Item( null, new Fingerprint() )
					: new Property( null, new Fingerprint(), 'string' );

			$this->addLabelsToEntity( $entity, $languages, $labelTextGenerator );
			$this->addDescriptionsToEntity( $entity, $languages, $descriptionTextGenerator );

			if ( !$this->hasOption( 'without-aliases' ) ) {
				$this->addAliasesToEntity( $entity, $languages, $aliasTextGenerator );
			}

			$entityId = $this->saveEntity( $entity, $context, $editEntityFactory );

			if ( $entityId !== null ) {
				yield $entityId;
			}
		}
	}

	/**
	 * @return int
	 */
	private function getDuplicationDegree() {
		$duplicationDegree = (float)$this->getOption(
			'duplication-degree',
			self::OPTION_DEFAULT_DUPLICATION_DEGREE
		);

		return max( min( $duplicationDegree, 1 ), 0 );
	}

	private function addLabelsToEntity(
		EntityDocument $entity,
		array $languages,
		Generator $textGenerator
	) {
		'@phan-var Item|Property $entity';
		foreach ( $languages as $language ) {
			$termText = $textGenerator->current();
			$textGenerator->next();

			$entity->getFingerprint()->setLabel( $language, $termText );
		}
	}

	private function addDescriptionsToEntity(
		EntityDocument $entity,
		array $languages,
		Generator $textGenerator
	) {
		'@phan-var Item|Property $entity';
		foreach ( $languages as $language ) {
			$termText = $textGenerator->current();
			$textGenerator->next();

			$entity->getFingerprint()->setDescription( $language, $termText );
		}
	}

	private function addAliasesToEntity(
		EntityDocument $entity,
		array $languages,
		Generator $textGenerator
	) {
		'@phan-var Item|Property $entity';
		foreach ( $languages as $language ) {
			$termText = $textGenerator->current();
			$textGenerator->next();

			$entity->getFingerprint()->setAliasGroup( $language, [ $termText ] );
		}
	}

	/**
	 * @return int number of entities to generate
	 */
	private function getNrOfEntities() {
		$minNrOfEntities = abs( (int)$this->getOption( 'at-least', self::OPTION_DEFAULT_AT_LEAST ) );
		$maxNrOfEntities = abs( (int)$this->getOption( 'at-most', self::OPTION_DEFAULT_AT_MOST ) );

		if ( $maxNrOfEntities < $minNrOfEntities ) {
			$maxNrOfEntities = $minNrOfEntities + 1;
		}

		return rand( $minNrOfEntities, $maxNrOfEntities );
	}

	/**
	 * @param int $duplicationDegree [0, 1] aimed percentage of duplication over all generated text.
	 *                               <= 0 means no duplication and all generated text is unique
	 *                               >= 1 will generate one unique text once and always return it
	 * @return Generator
	 */
	private function createTextGenerator( $duplicationDegree = 0 ) {
		$prevText = null;

		// @phan-suppress-next-line PhanInfiniteLoop
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
