<?php

namespace Wikibase;

use Serializers\Serializer;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Dumpers\DumpGenerator;
use Wikibase\Dumpers\JsonDumpGenerator;
use Wikibase\Lib\Store\RevisionBasedEntityLookup;
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
	 * @var EntityLookup
	 */
	private $entityLookup;

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

	public function __construct() {
		parent::__construct();

		$this->addOption(
			'snippet',
			'Output a JSON snippet without square brackets at the start and end. Allows output to'
				. ' be combined more freely.',
			false,
			false
		);
	}

	public function setServices(
		SqlEntityIdPagerFactory $sqlEntityIdPagerFactory,
		array $existingEntityTypes,
		array $disabledEntityTypes,
		EntityPrefetcher $entityPrefetcher,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		EntityLookup $entityLookup,
		Serializer $entitySerializer
	) {
		parent::setDumpEntitiesServices(
			$sqlEntityIdPagerFactory,
			$existingEntityTypes,
			$disabledEntityTypes
		);
		$this->entityPrefetcher = $entityPrefetcher;
		$this->propertyDatatypeLookup = $propertyDataTypeLookup;
		$this->entityLookup = $entityLookup;
		$this->entitySerializer = $entitySerializer;
		$this->hasHadServicesSet = true;
	}

	public function execute() {
		if ( !$this->hasHadServicesSet ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$sqlEntityIdPagerFactory = new SqlEntityIdPagerFactory(
				$wikibaseRepo->getEntityNamespaceLookup(),
				$wikibaseRepo->getEntityIdParser()
			);
			$revisionLookup = $wikibaseRepo->getEntityRevisionLookup(
				$this->getEntityRevisionLookupCacheMode()
			);

			$this->setServices(
				$sqlEntityIdPagerFactory,
				$wikibaseRepo->getEnabledEntityTypes(),
				[],
				$wikibaseRepo->getStore()->getEntityPrefetcher(),
				$wikibaseRepo->getPropertyDataTypeLookup(),
				new RevisionBasedEntityLookup( $revisionLookup ),
				$wikibaseRepo->getCompactEntitySerializer()
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
		$dataTypeLookup = $this->propertyDatatypeLookup;

		$dumper = new JsonDumpGenerator(
			$output,
			$this->entityLookup,
			$this->entitySerializer,
			$this->entityPrefetcher,
			$dataTypeLookup
		);

		$dumper->setUseSnippets( (bool)$this->getOption( 'snippet', false ) );
		return $dumper;
	}

}

$maintClass = DumpJson::class;
require_once RUN_MAINTENANCE_IF_MAIN;
