<?php

namespace Wikibase;

use SiteStore;
use Title;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Dumpers\DumpGenerator;
use Wikibase\Dumpers\RdfDumpGenerator;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Rdf\ValueSnakRdfBuilderFactory;
use Wikibase\Repo\Store\EntityPerPage;
use Wikibase\Repo\WikibaseRepo;

require_once __DIR__ . '/dumpEntities.php';

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
	private $dataValueRdfBuilderFactory;

	/**
	 * @var string
	 */
	private $conceptBaseUri;

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
	 * @param ValueSnakRdfBuilderFactory $dataValueRdfBuilderFactory
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param $conceptBaseUri
	 */
	public function setServices(
		EntityPerPage $entityPerPage,
		EntityPrefetcher $entityPrefetcher,
		SiteStore $siteStore,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		ValueSnakRdfBuilderFactory $dataValueRdfBuilderFactory,
		EntityRevisionLookup $entityRevisionLookup,
		$conceptBaseUri
	) {
		parent::setDumpEntitiesServices( $entityPerPage );
		$this->entityPrefetcher = $entityPrefetcher;
		$this->siteStore = $siteStore;
		$this->propertyDatatypeLookup = $propertyDataTypeLookup;
		$this->dataValueRdfBuilderFactory = $dataValueRdfBuilderFactory;
		$this->revisionLookup = $entityRevisionLookup;
		$this->conceptBaseUri = $conceptBaseUri;
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
				$wikibaseRepo->getDataValueRdfBuilderFactory(),
				$wikibaseRepo->getEntityRevisionLookup( 'uncached' ),
				$wikibaseRepo->getSettings()->getSetting( 'conceptBaseUri' )
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
		$entityDataTitle = Title::makeTitle( NS_SPECIAL, 'EntityData' );

		$languageCodes = array_merge(
				$GLOBALS['wgDummyLanguageCodes'],
				WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'canonicalLanguageCodes' )
		);

		return RdfDumpGenerator::createDumpGenerator(
			$this->getOption( 'format', 'ttl' ),
			$output,
			$this->conceptBaseUri,
			$entityDataTitle->getCanonicalURL() . '/',
			$this->siteStore->getSites(),
			$this->revisionLookup,
			$this->propertyDatatypeLookup,
			$this->dataValueRdfBuilderFactory,
			$this->entityPrefetcher,
			$languageCodes
		);
	}

}

$maintClass = 'Wikibase\DumpRdf';
require_once RUN_MAINTENANCE_IF_MAIN;
