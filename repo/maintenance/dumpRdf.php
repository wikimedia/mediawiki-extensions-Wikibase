<?php

namespace Wikibase;

use SiteStore;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Dumpers\DumpGenerator;
use Wikibase\Dumpers\RdfDumpGenerator;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Rdf\ValueSnakRdfBuilderFactory;
use Wikibase\Repo\Store\EntityIdPager;
use Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory;
use Wikibase\Repo\WikibaseRepo;

require_once __DIR__ . '/dumpEntities.php';

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Stas Malyshev
 * @author Addshore
 */
class DumpRdf extends DumpScript {

	/**
	 * @var EntityRevisionLookup
	 */
	private $revisionLookup;

	/**
	 * @var EntityPrefetcher
	 */
	private $entityPrefetcher;

	/**
	 * @var SiteStore
	 */
	private $siteStore;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDatatypeLookup;

	/**
	 * @var ValueSnakRdfBuilderFactory
	 */
	private $valueSnakRdfBuilderFactory;

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
	 * @param SqlEntityIdPagerFactory    $sqlEntityIdPagerFactory
	 * @param EntityPrefetcher           $entityPrefetcher
	 * @param SiteStore                  $siteStore
	 * @param PropertyDataTypeLookup     $propertyDataTypeLookup
	 * @param ValueSnakRdfBuilderFactory $valueSnakRdfBuilderFactory
	 * @param EntityRevisionLookup       $entityRevisionLookup
	 * @param RdfVocabulary              $rdfVocabulary
	 * @param EntityTitleLookup          $titleLookup
	 */
	public function setServices(
		SqlEntityIdPagerFactory $sqlEntityIdPagerFactory,
		EntityPrefetcher $entityPrefetcher,
		SiteStore $siteStore,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		ValueSnakRdfBuilderFactory $valueSnakRdfBuilderFactory,
		EntityRevisionLookup $entityRevisionLookup,
		RdfVocabulary $rdfVocabulary,
		EntityTitleLookup $titleLookup
	) {
		parent::setDumpEntitiesServices( $sqlEntityIdPagerFactory );
		$this->entityPrefetcher = $entityPrefetcher;
		$this->siteStore = $siteStore;
		$this->propertyDatatypeLookup = $propertyDataTypeLookup;
		$this->valueSnakRdfBuilderFactory = $valueSnakRdfBuilderFactory;
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
				$wikibaseRepo->getSiteStore(),
				$wikibaseRepo->getPropertyDataTypeLookup(),
				$wikibaseRepo->getValueSnakRdfBuilderFactory(),
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
			$this->siteStore->getSites(),
			$this->revisionLookup,
			$this->propertyDatatypeLookup,
			$this->valueSnakRdfBuilderFactory,
			$this->entityPrefetcher,
			$this->rdfVocabulary,
			$this->titleLookup
		);
	}

}

$maintClass = DumpRdf::class;
require_once RUN_MAINTENANCE_IF_MAIN;
