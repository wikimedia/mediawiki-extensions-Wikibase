<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Maintenance;

use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\EntityId\EntityIdPager;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\Dumpers\DumpGenerator;
use Wikibase\Repo\Dumpers\RdfDumpGenerator;
use Wikibase\Repo\Rdf\RdfBuilderFactory;
use Wikibase\Repo\Rdf\UnknownFlavorException;
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
	 * @var bool
	 */
	private $hasHadServicesSet = false;

	/**
	 * @var RdfBuilderFactory
	 */
	private $rdfBuilderFactory;

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
		EntityRevisionLookup $entityRevisionLookup,
		RdfBuilderFactory $rdfBuilderFactory
	): void {
		parent::setDumpEntitiesServices(
			$sqlEntityIdPagerFactory,
			$existingEntityTypes,
			$entityTypesWithoutRdfOutput
		);
		$this->entityPrefetcher = $entityPrefetcher;
		$this->revisionLookup = $entityRevisionLookup;
		$this->hasHadServicesSet = true;
		$this->rdfBuilderFactory = $rdfBuilderFactory;
	}

	public function execute(): void {
		if ( !$this->hasHadServicesSet ) {
			$mwServices = MediaWikiServices::getInstance();

			$sqlEntityIdPagerFactory = new SqlEntityIdPagerFactory(
				WikibaseRepo::getEntityNamespaceLookup( $mwServices ),
				WikibaseRepo::getEntityIdLookup( $mwServices ),
				WikibaseRepo::getRepoDomainDbFactory( $mwServices )->newRepoDb(),
				$mwServices->getLinkCache()
			);
			$store = WikibaseRepo::getStore( $mwServices );

			$this->setServices(
				$sqlEntityIdPagerFactory,
				WikibaseRepo::getEnabledEntityTypes( $mwServices ),
				WikibaseRepo::getSettings( $mwServices )
					->getSetting( 'entityTypesWithoutRdfOutput' ),
				$store->getEntityPrefetcher(),
				$store->getEntityRevisionLookup( $this->getEntityRevisionLookupCacheMode() ),
				WikibaseRepo::getRdfBuilderFactory( $mwServices )
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
	 */
	protected function createDumper( $output ): DumpGenerator {
		$flavor = $this->getOption( 'flavor', 'full-dump' );

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

		try {
			return RdfDumpGenerator::createDumpGenerator(
				$this->getOption( 'format', 'ttl' ),
				$output,
				$flavor,
				$this->revisionLookup,
				$this->entityPrefetcher,
				$labeler,
				$this->rdfBuilderFactory
			);
		} catch ( UnknownFlavorException $e ) {
			$this->fatalError( $e->getMessage() );
		}
	}

	protected function getDumpType(): string {
		return "RDF";
	}
}

$maintClass = DumpRdf::class;
require_once RUN_MAINTENANCE_IF_MAIN;
