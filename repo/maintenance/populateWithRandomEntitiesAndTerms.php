<?php

namespace Wikibase\Build;

use Maintenance;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

class PopulateWithRandomEntitiesAndTerms extends Maintenance
{
	const OPTION_DEFAULT_AT_LEAST = 1;
	const OPTION_DEFAULT_AT_MOST = 50;
	const OPTION_DEFAULT_LANGUAGES = [ 'de', 'en', 'fr', 'zh', 'es', 'ru', 'eo' ];

	public function __construct() {
		paernt::__construct();

		$this->addDescription( 'Populates Wikibase db with randomly generated entities and terms' );

		$this->addOption(
			'duplication-degree',
			'Degree of desired duplication in term text across term types and languages.'
				.' <=0 means no duplication at all, >=1 means same text for all terms. Default '
				. self::OPTION_DEFAULT_DUPLICATION_DEGREE,
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
				. implode( ',', self::OPTION_DEFAULT_LANGUAGES ),
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
			'without-terms',
			'Generate only empty entities without any terms in them'
		);

		$this->addOption(
			'without-labels',
			'Do not add label terms to generated entities.'
		);

		$this->addOption(
			'without-descriptions'
			'Do not add descriptions to generated entities'
		);

		$this->addOption(
			'without-aliases',
			'Do not add aliases to generated entities'
		);
	}

	public function execute() {
		$entityType = $this->getOption('entity-type');
		if ( $entityType !== 'item' && $entityType !== 'property' ) {
			$this->error('entity-type accepts only item or property as values');
			$this->maybeHelp( true );
		}

		$nrOfEntities = $this->getNrOfEntities();
		$nrOfTermsPerEntity = $this->calcNrOfTermsPerTypePerLanguagePerEntity();
		$texts = $this->generateTexts( $nrOfTermsPerEntity['total'], $nrOfEntities );

		$this->generateEntities(
			$entityType,
			$texts,
			$nrOfEntities,
			$nrOfTermsPerEntity
		);
	}

	private function generateEntities( $entityType, $texts, $nrOfEntities, $nrOfTermsPerEntity ) {
		$entityClass = $entityType === 'item' ? Item::class : Property::class;
		for ( $i = 0; $i < $nrOfEntities; $i++ ) {
			shuffle( $texts );

			EntityDocument $entity = new $entityClass();

		}
	}

	private function getNrOfEntities() {
		$minNrOfEntities = abs( (int)$this->getOption( 'at-least', self::OPTION_DEFAULT_AT_LEAST ) );
		$maxNrOfEntities = abs( (int)$this->getOption( 'at-most', self::OPTION_DEFAULT_AT_MOST ) );

		return rand( $minNrOfEntities, $maxNrOfEntities );
	}

	private function calcNrOfTermsPerTypePerLanguagePerEntity() {
		if ( $this->hasOption( 'without-terms' ) ) {
			return [ 'total' => 0 ];
		}

		$languages = $this->getOption( 'language', self::OPTION_DEFAULT_LANGUAGES );



		shuffle( $languages );

		$total = 0;
		$nrOfTerms = [];

		if ( !$this->hasOption( 'without-labels' ) ) {
			$types[] = 'label';

			$nrOfTerms['label'] = [];
			for ( $i = 0; $i < rand( 0, count( $languages ) ); $i++ ) {
				$nrOfTermsp['label'][$languages[$i]] = 1;
				$total++;
			}
		}
		if ( !$this->hasOption( 'without-descriptions' ) ) {
			$types[] = 'descriptions';
		}
		if ( !$this->hasOption( 'without-aliases' ) ) {
			$types[] = 'aliases';
		}

		foreach ( $types as $type ) {
			$randomLanguageIndexes = array_rand( $languages, rand( 1, count( $languages ) ) );

			foreach( $randomLanguageIndexes as $index ) {
				$nrOfTerms[$type][$languages[$index]] = 1;
				$total++;
			}
		}

		$nrOfTerms['total'] = $total;

		return $nrOfTerms;
	}

	private function generateTexts( $totalNrOfTermsPerEntity, $nrOfEntities ) {
		$duplicationDegree = (float)$this->getOption(
			'duplication-degree',
			self::OPTION_DEFAULT_DUPLICATION_DEGREE
		);

		$termTextPrefix = md5( random_bytes( 10 ) ) . '_';

		$duplicationDegree = $duplicationDegree > 1
						   ? 1
						   : $duplicationDegree < 0
						   ? 0
						   : $duplicationDegree;

		$totalNrOfTerms = $totalNrOfTermsPerEntity * $nrOfEntities;
		$nrOfUniqueTexts = $totalNrOfTerms * ( 1 - $duplicationDegree ) + 1;

		$texts = [];
		for ( $i = 0; $i < $nrOfUniqueTexts; $i++ ) {
			$texts[] = $termTextPrefix . $i;
		}

		return $texts;
	}
}


$maintClass = PopulateWithRandomEntitiesAndTerms::class;
require_once RUN_MAINTENANCE_IF_MAIN;
