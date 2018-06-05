<?php

namespace Wikibase;

use SiteLookup;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Dumpers\DumpGenerator;
use Wikibase\Dumpers\RdfDumpGenerator;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Rdf\EntityRdfBuilderFactory;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Rdf\ValueSnakRdfBuilderFactory;
use Wikibase\DataModel\Services\EntityId\EntityIdPager;
use Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Purtle\BNodeLabeler;

require_once __DIR__ . '/DumpEntities.php';

/**
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 * @author Addshore
 */
class DumpRdf extends DumpEntities {

	/**
	 * @var EntityRevisionLookup
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
		$this->addOption( 'format', 'Set the dump format, such as "nt" or "ttl". Defaults to "ttl".', false, true );
		$this->addOption(
			'flavor',
			'Set the flavor to produce. Can be either "full-dump" or "truthy-dump". Defaults to "full-dump".',
			false,
			true
		);
		$this->addOption( 'redirect-only', 'Whether to only dump information about redirects.', false, false );
		$this->addOption( 'part-id', 'Unique identifier for this part of multi-part dump, to be used for marking bnodes.', false, true );
	}

	public function setServices(
		SqlEntityIdPagerFactory $sqlEntityIdPagerFactory,
		array $existingEntityTypes,
		array $disabledEntityTypes,
		EntityPrefetcher $entityPrefetcher,
		SiteLookup $siteLookup,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		ValueSnakRdfBuilderFactory $valueSnakRdfBuilderFactory,
		EntityRdfBuilderFactory $entityRdfBuilderFactory,
		EntityRevisionLookup $entityRevisionLookup,
		RdfVocabulary $rdfVocabulary,
		EntityTitleLookup $titleLookup
	) {
		parent::setDumpEntitiesServices(
			$sqlEntityIdPagerFactory,
			$existingEntityTypes,
			$disabledEntityTypes
		);
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
				$wikibaseRepo->getEnabledEntityTypes(),
				$wikibaseRepo->getSettings()->getSetting( 'disabledRdfExportEntityTypes' ),
				$wikibaseRepo->getStore()->getEntityPrefetcher(),
				$wikibaseRepo->getSiteLookup(),
				$wikibaseRepo->getPropertyDataTypeLookup(),
				$wikibaseRepo->getValueSnakRdfBuilderFactory(),
				$wikibaseRepo->getEntityRdfBuilderFactory(),
				$wikibaseRepo->getEntityRevisionLookup( $this->getEntityRevisionLookupCacheMode() ),
				$wikibaseRepo->getRdfVocabulary(),
				$wikibaseRepo->getEntityContentFactory()
			);
		}
		parent::execute();
	}

	/**
	 * Returns one of the EntityIdPager::XXX_REDIRECTS constants.
	 *
	 * @return mixed a EntityIdPager::XXX_REDIRECTS constant
	 */
	protected function getRedirectMode() {
		$redirectOnly = $this->getOption( 'redirect-only', false );

		if ( $redirectOnly ) {
			return EntityIdPager::ONLY_REDIRECTS;
		} else {
			return EntityIdPager::INCLUDE_REDIRECTS;
		}
	}

	/**
	 * Create concrete dumper instance
	 *
	 * @param resource $output
	 *
	 * @return DumpGenerator
	 */
	protected function createDumper( $output ) {
		$flavor = $this->getOption( 'flavor', 'full-dump' );
		if ( !in_array( $flavor, [ 'full-dump', 'truthy-dump' ] ) ) {
			$this->error( 'Invalid flavor: ' . $flavor, 1 );
		}

		$labeler = null;
		$partId = $this->getOption( 'part-id' );
		if ( $partId ) {
			$labeler = new BNodeLabeler( "genid{$partId}-" );
		}

		if ( $this->getOption( 'sharding-factor', 1 ) !== 1 ) {
			$shard = $this->getOption( 'shard', 0 );
			if ( !$labeler ) {
				// Mark this shard's bnodes with shard ID if not told otherwise
				$labeler = new BNodeLabeler( "genid{$shard}-" );
			}
		}

		return RdfDumpGenerator::createDumpGenerator(
			$this->getOption( 'format', 'ttl' ),
			$output,
			$flavor,
			$this->siteLookup->getSites(),
			$this->revisionLookup,
			$this->propertyDatatypeLookup,
			$this->valueSnakRdfBuilderFactory,
			$this->entityRdfBuilderFactory,
			$this->entityPrefetcher,
			$this->rdfVocabulary,
			$this->titleLookup,
			$labeler
		);
	}

}

$maintClass = DumpRdf::class;
require_once RUN_MAINTENANCE_IF_MAIN;
