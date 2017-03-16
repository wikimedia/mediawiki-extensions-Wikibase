<?php

namespace Wikibase;

use SiteLookup;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\EntityId\EntityIdPager;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Dumpers\DumpGenerator;
use Wikibase\Dumpers\RdfDumpGenerator;
use Wikibase\Edrsf\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Rdf\EntityRdfBuilderFactory;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Rdf\ValueSnakRdfBuilderFactory;
use Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory;
use Wikibase\Repo\WikibaseRepo;

require_once __DIR__ . '/dumpEntities.php';

/**
 * @license GPL-2.0+
 * @author Stas Malyshev
 * @author Addshore
 */
class DumpRdf extends DumpScript {

	/**
	 * @var \Wikibase\Edrsf\EntityRevisionLookup
	 */
	private $revisionLookup;

	/**
	 * @var EntityPrefetcher
	 */
	private $entityPrefetcher;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDatatypeLookup;

	/**
	 * @var ValueSnakRdfBuilderFactory
	 */
	private $valueSnakRdfBuilderFactory;

	/**
	 * @var EntityRdfBuilderFactory
	 */
	private $entityRdfBuilderFactory;

	/**
	 * @var RdfVocabulary
	 */
	private $rdfVocabulary;

	/**
	 * @var bool
	 */
	private $hasHadServicesSet = false;

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	public function __construct() {
		parent::__construct();
		$this->addOption( 'format', "Set the dump format.", false, true );
	}

	/**
	 * @param SqlEntityIdPagerFactory $sqlEntityIdPagerFactory
	 * @param EntityPrefetcher $entityPrefetcher
	 * @param SiteLookup $siteLookup
	 * @param PropertyDataTypeLookup $propertyDataTypeLookup
	 * @param ValueSnakRdfBuilderFactory $valueSnakRdfBuilderFactory
	 * @param EntityRdfBuilderFactory $entityRdfBuilderFactory
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param RdfVocabulary $rdfVocabulary
	 * @param EntityTitleLookup $titleLookup
	 */
	public function setServices(
		SqlEntityIdPagerFactory $sqlEntityIdPagerFactory,
		EntityPrefetcher $entityPrefetcher,
		SiteLookup $siteLookup,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		ValueSnakRdfBuilderFactory $valueSnakRdfBuilderFactory,
		EntityRdfBuilderFactory $entityRdfBuilderFactory,
		EntityRevisionLookup $entityRevisionLookup,
		RdfVocabulary $rdfVocabulary,
		EntityTitleLookup $titleLookup
	) {
		parent::setDumpEntitiesServices( $sqlEntityIdPagerFactory );
		$this->entityPrefetcher = $entityPrefetcher;
		$this->siteLookup = $siteLookup;
		$this->propertyDatatypeLookup = $propertyDataTypeLookup;
		$this->valueSnakRdfBuilderFactory = $valueSnakRdfBuilderFactory;
		$this->entityRdfBuilderFactory = $entityRdfBuilderFactory;
		$this->revisionLookup = $entityRevisionLookup;
		$this->rdfVocabulary = $rdfVocabulary;
		$this->titleLookup = $titleLookup;
		$this->hasHadServicesSet = true;
	}

	public function execute() {
		if ( !$this->hasHadServicesSet ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$sqlEntityIdPagerFactory = new SqlEntityIdPagerFactory(
				$wikibaseRepo->getEntityNamespaceLookup(),
				$wikibaseRepo->getEntityIdParser()
			);

			$this->setServices(
				$sqlEntityIdPagerFactory,
				$wikibaseRepo->getStore()->getEntityPrefetcher(),
				$wikibaseRepo->getSiteLookup(),
				$wikibaseRepo->getPropertyDataTypeLookup(),
				$wikibaseRepo->getValueSnakRdfBuilderFactory(),
				$wikibaseRepo->getEntityRdfBuilderFactory(),
				$wikibaseRepo->getEntityRevisionLookup( 'uncached' ),
				$wikibaseRepo->getRdfVocabulary(),
				$wikibaseRepo->getEntityContentFactory()
			);
		}
		parent::execute();
	}

	/**
	 * Returns EntityIdPager::INCLUDE_REDIRECTS.
	 *
	 * @return mixed a EntityIdPager::XXX_REDIRECTS constant
	 */
	protected function getRedirectMode() {
		return EntityIdPager::INCLUDE_REDIRECTS;
	}

	/**
	 * Create concrete dumper instance
	 *
	 * @param resource $output
	 *
	 * @return DumpGenerator
	 */
	protected function createDumper( $output ) {
		return RdfDumpGenerator::createDumpGenerator(
			$this->getOption( 'format', 'ttl' ),
			$output,
			$this->siteLookup->getSites(),
			$this->revisionLookup,
			$this->propertyDatatypeLookup,
			$this->valueSnakRdfBuilderFactory,
			$this->entityRdfBuilderFactory,
			$this->entityPrefetcher,
			$this->rdfVocabulary,
			$this->titleLookup
		);
	}

}

$maintClass = DumpRdf::class;
require_once RUN_MAINTENANCE_IF_MAIN;
