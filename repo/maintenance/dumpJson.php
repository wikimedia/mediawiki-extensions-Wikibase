<?php

namespace Wikibase\Repo\Maintenance;

use MediaWiki\MediaWikiServices;
use Serializers\Serializer;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\Dumpers\DumpGenerator;
use Wikibase\Repo\Dumpers\JsonDumpGenerator;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory;
use Wikibase\Repo\WikibaseRepo;

require_once __DIR__ . '/DumpEntities.php';

/**
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Addshore
 */
class DumpJson extends DumpEntities {

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var EntityTitleStoreLookup
	 */
	private $entityTitleStoreLookup;

	/**
	 * @var Serializer
	 */
	private $entitySerializer;

	/**
	 * @var EntityPrefetcher
	 */
	private $entityPrefetcher;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDatatypeLookup;

	/**
	 * @var bool
	 */
	private $hasHadServicesSet = false;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	public function __construct() {
		parent::__construct();

		$this->addOption(
			'snippet',
			'Output a JSON snippet without square brackets at the start and end. Allows output to'
				. ' be combined more freely.',
			false,
			false
		);
		$this->addOption(
			'page-metadata',
			'Entities should include page metadata.',
			false,
			false
		);
	}

	public function setServices(
		SqlEntityIdPagerFactory $sqlEntityIdPagerFactory,
		array $existingEntityTypes,
		EntityPrefetcher $entityPrefetcher,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		EntityRevisionLookup $entityRevisionLookup,
		Serializer $entitySerializer,
		EntityIdParser $entityIdParser,
		EntityTitleStoreLookup $entityTitleStoreLookup
	) {
		parent::setDumpEntitiesServices(
			$sqlEntityIdPagerFactory,
			$existingEntityTypes,
			[]
		);
		$this->entityPrefetcher = $entityPrefetcher;
		$this->propertyDatatypeLookup = $propertyDataTypeLookup;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->entitySerializer = $entitySerializer;
		$this->entityIdParser = $entityIdParser;
		$this->entityTitleStoreLookup = $entityTitleStoreLookup;
		$this->hasHadServicesSet = true;
	}

	public function execute() {
		if ( !$this->hasHadServicesSet ) {
			$mwServices = MediaWikiServices::getInstance();

			$sqlEntityIdPagerFactory = new SqlEntityIdPagerFactory(
				WikibaseRepo::getEntityNamespaceLookup( $mwServices ),
				WikibaseRepo::getEntityIdLookup( $mwServices ),
				WikibaseRepo::getRepoDomainDbFactory( $mwServices )->newRepoDb(),
				$mwServices->getLinkCache()
			);
			$store = WikibaseRepo::getStore( $mwServices );
			$revisionLookup = $store->getEntityRevisionLookup(
				$this->getEntityRevisionLookupCacheMode()
			);

			$this->setServices(
				$sqlEntityIdPagerFactory,
				WikibaseRepo::getEnabledEntityTypes( $mwServices ),
				$store->getEntityPrefetcher(),
				WikibaseRepo::getPropertyDataTypeLookup(),
				$revisionLookup,
				WikibaseRepo::getCompactEntitySerializer( $mwServices ),
				WikibaseRepo::getEntityIdParser( $mwServices ),
				WikibaseRepo::getEntityTitleStoreLookup( $mwServices )
			);
		}
		parent::execute();
	}

	/**
	 * Create concrete dumper instance
	 * @param resource $output
	 * @return DumpGenerator
	 */
	protected function createDumper( $output ) {
		$dumper = new JsonDumpGenerator(
			$output,
			$this->entityRevisionLookup,
			$this->entitySerializer,
			$this->entityPrefetcher,
			$this->propertyDatatypeLookup,
			$this->entityIdParser,
			$this->entityTitleStoreLookup
		);

		$dumper->setUseSnippets( (bool)$this->getOption( 'snippet', false ) );
		$dumper->setAddPageMetadata( (bool)$this->getOption( 'page-metadata', false ) );
		return $dumper;
	}

	protected function getDumpType(): string {
		return "JSON";
	}
}

$maintClass = DumpJson::class;
require_once RUN_MAINTENANCE_IF_MAIN;
