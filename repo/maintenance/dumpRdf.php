<?php

namespace Wikibase\Repo\Maintenance;

use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\EntityId\EntityIdPager;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Dumpers\DumpGenerator;
use Wikibase\Repo\Dumpers\RdfDumpGenerator;
use Wikibase\Repo\Rdf\EntityRdfBuilderFactory;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\ValueSnakRdfBuilderFactory;
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

	/** @var EntityContentFactory */
	private $entityContentFactory;

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
		array $entityTypesWithoutRdfOutput,
		EntityPrefetcher $entityPrefetcher,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		ValueSnakRdfBuilderFactory $valueSnakRdfBuilderFactory,
		EntityRdfBuilderFactory $entityRdfBuilderFactory,
		EntityRevisionLookup $entityRevisionLookup,
		RdfVocabulary $rdfVocabulary,
		EntityContentFactory $entityContentFactory
	) {
		parent::setDumpEntitiesServices(
			$sqlEntityIdPagerFactory,
			$existingEntityTypes,
			$entityTypesWithoutRdfOutput
		);
		$this->entityPrefetcher = $entityPrefetcher;
		$this->propertyDatatypeLookup = $propertyDataTypeLookup;
		$this->valueSnakRdfBuilderFactory = $valueSnakRdfBuilderFactory;
		$this->entityRdfBuilderFactory = $entityRdfBuilderFactory;
		$this->revisionLookup = $entityRevisionLookup;
		$this->rdfVocabulary = $rdfVocabulary;
		$this->entityContentFactory = $entityContentFactory;
		$this->hasHadServicesSet = true;
	}

	public function execute() {
		if ( !$this->hasHadServicesSet ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$mwServices = MediaWikiServices::getInstance();

			$sqlEntityIdPagerFactory = new SqlEntityIdPagerFactory(
				WikibaseRepo::getEntityNamespaceLookup( $mwServices ),
				WikibaseRepo::getEntityIdLookup( $mwServices ),
				$mwServices->getLinkCache()
			);

			$this->setServices(
				$sqlEntityIdPagerFactory,
				$wikibaseRepo->getEnabledEntityTypes(),
				WikibaseRepo::getSettings( $mwServices )
					->getSetting( 'entityTypesWithoutRdfOutput' ),
				WikibaseRepo::getStore( $mwServices )->getEntityPrefetcher(),
				$wikibaseRepo->getPropertyDataTypeLookup(),
				WikibaseRepo::getValueSnakRdfBuilderFactory( $mwServices ),
				WikibaseRepo::getEntityRdfBuilderFactory( $mwServices ),
				$wikibaseRepo->getEntityRevisionLookup( $this->getEntityRevisionLookupCacheMode() ),
				WikibaseRepo::getRdfVocabulary( $mwServices ),
				WikibaseRepo::getEntityContentFactory( $mwServices )
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
			$this->fatalError( 'Invalid flavor: ' . $flavor );
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
			$this->revisionLookup,
			$this->propertyDatatypeLookup,
			$this->valueSnakRdfBuilderFactory,
			$this->entityRdfBuilderFactory,
			$this->entityPrefetcher,
			$this->rdfVocabulary,
			$this->entityContentFactory,
			$labeler
		);
	}

	protected function getDumpType(): string {
		return "RDF";
	}
}

$maintClass = DumpRdf::class;
require_once RUN_MAINTENANCE_IF_MAIN;
