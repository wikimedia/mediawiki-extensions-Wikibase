<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use DataValues\Geo\Formatters\GeoCoordinateFormatter;
use DataValues\StringValue;
use RuntimeException;
use ValueFormatters\FormatterOptions;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\EntityId\EntityIdParsingException;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\EntityRevision;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\WikibaseValueFormatterBuilders;
use Wikibase\Repo\WikibaseRepo;

/**
 * @todo description i18n
 * @todo param i18n
 * @todo use serializers in this a bit?
 *
 * @author Adam Shorland
 */
class NutShell extends ApiBase {

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;
	/**
	 * @var EntityIdParser
	 */
	private $idParser;
	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * @var WikibaseValueFormatterBuilders
	 */
	private $formatterBuilders;

	/**
	 * @todo get this from some service
	 * @var array
	 */
	private $propertyOrder = array(
		'P31',//instanceof
		'P18',//image
		'P625',//coordinate location
		'P569',//dob
		'P106',//occupation
		'P1',
		'P2',
		'P3',
		'P4',
		'P5',
		'P6',
		'P7',
		'P8',
		'P9',
		'P10', //test site?
	);

	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $this->getContext() );

		$this->errorReporter = $apiHelperFactory->getErrorReporter( $this );
		$this->entityRevisionLookup = $wikibaseRepo->getEntityRevisionLookup();
		$this->idParser = $wikibaseRepo->getEntityIdParser();
		$this->dataTypeLookup = $wikibaseRepo->getPropertyDataTypeLookup();
		$this->formatterBuilders = $wikibaseRepo->getDefaultFormatterBuilders();
	}

	public function execute() {
		$params = $this->extractRequestParams();

		if ( !isset( $params['id'] ) ) {
			$this->errorReporter->dieError( 'You must pass the id parameter', 'param-missing' );
		}

		$entityId = $this->getEntityIdFromIdParam( $params );
		$entityRevision = $this->getEntityRevision( $entityId );

		$this->buildResult( $entityRevision->getEntity() );
	}

	/**
	 * @param array $params
	 *
	 * @return EntityId
	 */
	private function getEntityIdFromIdParam( $params ) {
		if ( isset( $params['id'] ) ) {
			try {
				return $this->idParser->parse( $params['id'] );
			}
			catch ( EntityIdParsingException $e ) {
				$this->errorReporter->dieError(
					"Invalid id: " . $params['id'],
					'no-such-entity',
					0,
					array( 'id' => $params['id'] )
				);
			}
		}
		throw new RuntimeException( 'TODO a better error message....' );
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return null|EntityRevision
	 */
	private function getEntityRevision( EntityId $entityId ) {
		$entityRevision = null;

		try {
			$entityRevision = $this->entityRevisionLookup->getEntityRevision( $entityId );
		}
		catch ( RevisionedUnresolvedRedirectException $ex ) {
			$entityId = $ex->getRedirectTargetId();
			$entityRevision = $this->getEntityRevision( $entityId );
		}

		return $entityRevision;
	}

	private function buildResult( EntityDocument $entity ) {
		$nutshell = $this->getBaseNutShell( $entity );

		if ( $entity instanceof StatementListProvider ) {
			$nutshell['statements'] = $this->getStatementListNutShell( $entity );
		}

		$this->getResult()->addValue( null, 'nutshell', $nutshell );
		$this->getResult()->addValue( null, 'version', '0.0.1' );
	}

	private function getStatementListNutShell( StatementListProvider $entity ) {
		$nutshell = array();
		$bestStatements = $entity->getStatements()->getBestStatements();

		foreach ( $this->propertyOrder as $propertyIdSerialization ) {
			$propertyId = new PropertyId( $propertyIdSerialization );
			$statements = $bestStatements->getByPropertyId( $propertyId )->toArray();

			foreach ( $statements as $statement ) {
				if ( $statement->getMainSnak() instanceof PropertyValueSnak ) {
					$nutshell[$propertyIdSerialization] = $this->getStatementNutShell( $statement );
				}
			}
		}

		return $nutshell;
	}

	private function getStatementNutShell( Statement $statement ) {
		/** @var PropertyValueSnak $snak */
		$snak = $statement->getMainSnak();
		$datavalue = $snak->getDataValue();

		$propertyId = $statement->getPropertyId();
		$dataType = $this->dataTypeLookup->getDataTypeIdForProperty( $propertyId );

		$data = null;

		switch ( $dataType ) {
			case 'string';
				$data = array( 'raw' => $datavalue->getArrayValue(), );
				break;
			case 'commonsMedia';
				$fileName = $datavalue->getValue();
				$fileHash = md5( $fileName );
				$chars1 = substr( $fileHash, 0, 1 );
				$chars2 = substr( $fileHash, 0, 2 );
				//TODO image info?
				$data = array(
					'raw' => $datavalue->getArrayValue(),
					'title' => 'File:' . $fileName,
					'remote-page' => 'https://commons.wikimedia.org/wiki/File:' . $fileName,
					'url' => 'https://upload.wikimedia.org/wikipedia/commons/' .
						$chars1 . '/' . $chars2 . '/' . $fileName,
				);
				break;
			case 'wikibase-item';
			case 'wikibase-property';
				//TODO could do recursive to level n?
				/** @var EntityIdValue $datavalue */
				$dvEntityId = $datavalue->getEntityId();
				$dvRevision = $this->entityRevisionLookup->getEntityRevision( $dvEntityId );
				/** @var FingerprintProvider $dvEntity */
				$dvEntity = $dvRevision->getEntity();
				$data = array(
					'raw' => $datavalue->getArrayValue(),
					'id' => $dvEntityId->getSerialization(),
					'type' => $dvEntityId->getEntityType(),
					'labels' => $dvEntity->getFingerprint()->getLabels()->toTextArray(),
					'descriptions' => $dvEntity->getFingerprint()->getDescriptions()->toTextArray(),
					'aliases' => $dvEntity->getFingerprint()->getAliasGroups()->toTextArray(),
				);
				break;
			case 'globe-coordinate';
				$globeFormatterGeoCode = $this->formatterBuilders
					->newGlobeCoordinateFormatter(
						SnakFormatter::FORMAT_PLAIN,
						$this->getFormatterOptionsForGeoHack()
					);
				$globeFormatterDefault = $this->formatterBuilders
					->newGlobeCoordinateFormatter(
						SnakFormatter::FORMAT_PLAIN,
						new FormatterOptions()
					);

				$geoCodeCoord =
					str_replace( ' ', '', $globeFormatterGeoCode->format( $datavalue ) );
				$rawArray = $datavalue->getArrayValue();

				$data = array(
					//TODO serlize?
					'raw' => $rawArray,
					'formatted' => $globeFormatterDefault->format( $datavalue ),
					'geohack-coordinate' => $geoCodeCoord,
					'geohack-url' => 'https://tools.wmflabs.org/geohack/geohack.php?params=' .
						$geoCodeCoord,
					'google-url' => 'https://www.google.com/maps?q=' .
						$rawArray['latitude'] .
						',' .
						$rawArray['longitude'],
					'osm-url' => 'https://www.openstreetmap.org/?mlat=' .
						$rawArray['latitude'] .
						'&mlon=' .
						$rawArray['longitude'],
				);
				break;
		}

		//TODO dont assume this
		/** @var FingerprintProvider $property */
		$property = $this->entityRevisionLookup->getEntityRevision( $propertyId )->getEntity();

		return array(
			'labels' => $property->getFingerprint()->getLabels()->toTextArray(),
			'guid' => $statement->getGuid(),
			'type' => $dataType,
			'data' => $data,
		);
	}

	private function getBaseNutShell( EntityDocument $entity ) {
		$nutshell = array();

		if ( $entity instanceof FingerprintProvider ) {
			$nutshell['labels'] = $entity->getFingerprint()->getLabels()->toTextArray();
			$nutshell['descriptions'] = $entity->getFingerprint()->getDescriptions()->toTextArray();
			$nutshell['aliases'] = $entity->getFingerprint()->getAliasGroups()->toTextArray();
		}
		if ( $entity instanceof Item ) {
			$nutshell['sitelinks'] = $entity->getSiteLinkList()->toArray();
		}

		return $nutshell;
	}

	private function getFormatterOptionsForGeoHack() {
		$options = new FormatterOptions();
		$options->setOption( GeoCoordinateFormatter::OPT_MINUTE_SYMBOL, '_' );
		$options->setOption( GeoCoordinateFormatter::OPT_SECOND_SYMBOL, '_' );
		$options->setOption( GeoCoordinateFormatter::OPT_DEGREE_SYMBOL, '_' );
		$options->setOption( GeoCoordinateFormatter::OPT_SEPARATOR_SYMBOL, '_' );
		//FIXME spacing level does not work? So we remove all spaces...
		$options->setOption( GeoCoordinateFormatter::OPT_SPACING_LEVEL, array() );

		return $options;
	}

	/**
	 * @see ApiBase::getAllowedParams
	 * @todo site title combinations
	 */
	protected function getAllowedParams() {
		return array_merge(
			parent::getAllowedParams(),
			array(
				'id' => array(
					self::PARAM_TYPE => 'string',
				),
			)
		);
	}

	/**
	 * Mark this api as internal & unstable
	 *
	 * @return bool
	 */
	public function isInternal() {
		return true;
	}

}
