<?php

namespace Wikibase;

use SiteStore;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Dumpers\DumpGenerator;
use Wikibase\Dumpers\RdfDumpGenerator;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Rdf\ValueSnakRdfBuilderFactory;
use Wikibase\Repo\Store\EntityPerPage;
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

	public function __construct() {
		parent::__construct();
		$this->addOption( 'format', "Set the dump format.", false, true );
	}

	/**
	 * @param EntityPerPage $entityPerPage
	 * @param EntityPrefetcher $entityPrefetcher
	 * @param SiteStore $siteStore
	 * @param PropertyDataTypeLookup $propertyDataTypeLookup
	 * @param ValueSnakRdfBuilderFactory $valueSnakRdfBuilderFactory
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param RdfVocabulary $rdfVocabulary
	 */
	public function setServices(
		EntityPerPage $entityPerPage,
		EntityPrefetcher $entityPrefetcher,
		SiteStore $siteStore,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		ValueSnakRdfBuilderFactory $valueSnakRdfBuilderFactory,
		EntityRevisionLookup $entityRevisionLookup,
		RdfVocabulary $rdfVocabulary
	) {
		parent::setDumpEntitiesServices( $entityPerPage );
		$this->entityPrefetcher = $entityPrefetcher;
		$this->siteStore = $siteStore;
		$this->propertyDatatypeLookup = $propertyDataTypeLookup;
		$this->valueSnakRdfBuilderFactory = $valueSnakRdfBuilderFactory;
		$this->revisionLookup = $entityRevisionLookup;
		$this->rdfVocabulary = $rdfVocabulary;
		$this->hasHadServicesSet = true;
	}

	public function execute() {
		if ( !$this->hasHadServicesSet ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$this->setServices(
				$wikibaseRepo->getStore()->newEntityPerPage(),
				$wikibaseRepo->getStore()->getEntityPrefetcher(),
				$wikibaseRepo->getSiteStore(),
				$wikibaseRepo->getPropertyDataTypeLookup(),
				$wikibaseRepo->getValueSnakRdfBuilderFactory(),
				$wikibaseRepo->getEntityRevisionLookup( 'uncached' ),
				$wikibaseRepo->getRdfVocabulary()
			);
		}
		parent::execute();
	}

	/**
	 * Returns EntityPerPage::INCLUDE_REDIRECTS.
	 *
	 * @return mixed a EntityPerPage::XXX_REDIRECTS constant
	 */
	protected function getRedirectMode() {
		return EntityPerPage::INCLUDE_REDIRECTS;
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
			$this->rdfVocabulary
		);
	}

}

$maintClass = DumpRdf::class;
require_once RUN_MAINTENANCE_IF_MAIN;
