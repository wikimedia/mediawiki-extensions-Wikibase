<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\ChangeModification;

use InvalidArgumentException;
use MediaWiki\JobQueue\IJobSpecification;
use MediaWiki\JobQueue\Job;
use MediaWiki\JobQueue\JobQueueGroupFactory;
use MediaWiki\JobQueue\JobSpecification;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use Psr\Log\LoggerInterface;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Changes\Change;
use Wikibase\Lib\Changes\ChangeRow;
use Wikibase\Lib\Changes\ChangeStore;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\ItemChange;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\Sql\EntityChangeLookup;
use Wikibase\Lib\WikibaseSettings;
use Wikibase\Repo\Store\Sql\SqlSubscriptionLookup;
use Wikibase\Repo\Store\SubscriptionLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Assert\Assert;
use Wikimedia\Stats\StatsFactory;

/**
 * @license GPL-2.0-or-later
 */
class DispatchChangesJob extends Job {

	private const ENTITY_ID_KEY = 'entityId';

	/** @var string */
	private $entityIdSerialization;

	/**
	 * @var SubscriptionLookup
	 */
	private $subscriptionLookup;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var JobQueueGroupFactory
	 */
	private $jobQueueGroupFactory;

	/**
	 * @var EntityChangeLookup
	 */
	private $changeLookup;

	/**
	 * @var ChangeStore
	 */
	private $changeStore;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var StatsFactory
	 */
	private $statsFactory;

	/** @var string */
	private $statsPrefix;

	public static function makeJobSpecification( string $entityIdSerialization ): IJobSpecification {
		return new JobSpecification(
			'DispatchChanges',
			[
				'title' => $entityIdSerialization,
				self::ENTITY_ID_KEY => $entityIdSerialization,
			]
		);
	}

	public function __construct(
		SubscriptionLookup $subscriptionLookup,
		EntityChangeLookup $changeLookup,
		EntityIdParser $entityIdParser,
		JobQueueGroupFactory $jobQueueGroupFactory,
		ChangeStore $changeStore,
		LoggerInterface $logger,
		StatsFactory $statsFactory,
		array $params
	) {

		if ( empty( $params[self::ENTITY_ID_KEY] ) ) {
			throw new InvalidArgumentException( 'entityId parameter missing' );
		}

		$this->entityIdSerialization = $params[self::ENTITY_ID_KEY];
		$this->subscriptionLookup = $subscriptionLookup;
		$this->changeLookup = $changeLookup;
		$this->entityIdParser = $entityIdParser;
		$this->jobQueueGroupFactory = $jobQueueGroupFactory;
		$this->changeStore = $changeStore;
		$this->logger = $logger;
		$this->statsFactory = $statsFactory->withComponent( 'WikibaseRepo' );
		$this->removeDuplicates = true;

		$services = MediaWikiServices::getInstance();
		$mainConfig = $services->getMainConfig();

		$this->statsPrefix = rtrim( $mainConfig->get( MainConfigNames::DBname ), '.' );
		parent::__construct( 'DispatchChanges', $params );
	}

	public static function newFromGlobalState( ?Title $unused, array $params ): self {
		return new self(
			new SqlSubscriptionLookup(
				WikibaseRepo::getRepoDomainDbFactory()->newRepoDb()
			),
			WikibaseRepo::getEntityChangeLookup(),
			WikibaseRepo::getEntityIdParser(),
			MediaWikiServices::getInstance()->getJobQueueGroupFactory(),
			WikibaseRepo::getStore()->getChangeStore(),
			WikibaseRepo::getLogger(),
			MediaWikiServices::getInstance()->getStatsFactory(),
			$params
		);
	}

	public function run(): bool {
		// TODO: for v2 of this job, we could actually get all the newest revision from the DB,
		//       calculate the change_info ourselves and thus make wb_changes table obsolete?

		$repoSettings = WikibaseRepo::getSettings();
		$allClientSites = $this->getClientWikis( $repoSettings );
		$entityId = $this->entityIdParser->parse( $this->entityIdSerialization );
		$subscribedClientSites = $this->subscriptionLookup->getSubscribers( $entityId );

		$changes = $this->changeLookup->loadByEntityIdFromPrimary( $this->entityIdSerialization );

		if ( !$changes ) {
			$this->logger->info( __METHOD__ . ': no changes for {entity} => all have been consumed by previous job?', [
				'entity' => $this->entityIdSerialization,
			] );
			return true;
		}

		if ( $entityId->getEntityType() === 'item' ) {
			$wikisWithSitelinkChanges = $this->getWikiIdsWithChangedSitelinks( $changes );
		} else {
			$wikisWithSitelinkChanges = [];
		}
		$dispatchingClientSites = $this->filterClientWikis( $allClientSites, $subscribedClientSites, $wikisWithSitelinkChanges );
		if ( !$dispatchingClientSites ) {
			// without subscribed wikis, this job should never have been scheduled
			$this->logger->warning( __METHOD__ . ': no wikis subscribed for {entity} => doing nothing', [
				'entity' => $this->entityIdSerialization,
			] );

			$this->deleteChangeRows( $changes );

			return true;
		}

		$this->logUnsubscribedWikisWithSitelinkChanges( $dispatchingClientSites, $subscribedClientSites );

		// not really a timing, but works like one (we want percentiles etc.)
		// TODO: probably a good candidate for T348796
		$this->statsFactory
			->getTiming( 'dispatchChangesJob_numberOfChangesInJob_total' )
			->setLabel( "db", $this->statsPrefix )
			->copyToStatsdAt( "$this->statsPrefix.wikibase.repo.dispatchChangesJob.NumberOfChangesInJob" )
			->observe( count( $changes ) );

		$this->statsFactory
			->getTiming( 'dispatchChangesJob_numberOfWikisForChange_total' )
			->setLabel( "db", $this->statsPrefix )
			->copyToStatsdAt( "$this->statsPrefix.wikibase.repo.dispatchChangesJob.numberOfWikisForChange" )
			->observe( count( $dispatchingClientSites ) );

		$this->logger->info( __METHOD__ . ': dispatching changes for {entity} to {numberOfWikis} clients: {listOfWikis}', [
			'entity' => $this->entityIdSerialization,
			'numberOfWikis' => count( $dispatchingClientSites ),
			'listOfWikis' => implode( ', ', $dispatchingClientSites ),
		] );

		$job = $this->getClientJobSpecification( $changes );
		foreach ( $dispatchingClientSites as $clientDb ) {
			$jobQueueGroup = $this->jobQueueGroupFactory->makeJobQueueGroup( $clientDb );
			$jobQueueGroup->lazyPush( $job );
		}

		$this->deleteChangeRows( $changes );

		return true;
	}

	private function logUnsubscribedWikisWithSitelinkChanges( array $dispatchingClientSites, array $subscribedClientSites ) {
		$clientsWithAddedSitelinks = array_diff( array_keys( $dispatchingClientSites ), $subscribedClientSites );
		foreach ( $clientsWithAddedSitelinks as $wikiId ) {
			$metric = $this->statsFactory->getCounter(
				"dispatchChangesJob_SitelinkAdditionDispatched_total"
			)->setLabels( [ "db" => $this->statsPrefix, "wikiId" => $wikiId ] );
			$metric->copyToStatsdAt( "$this->statsPrefix.wikibase.repo.dispatchChangesJob.sitelinkAdditionDispatched.{$wikiId}" )
				->increment();
		}
	}

	/**
	 * @return string[]
	 */
	private function getWikiIdsWithChangedSitelinks( array $changes ): array {
		return array_values( array_unique( array_reduce(
			$changes,
			function ( $carry, ItemChange $change ) {
				return array_merge( $carry, array_keys( $change->getSiteLinkDiff()->getOperations() ) );
			},
			[]
		) ) );
	}

	/**
	 * @param Change[] $changes
	 */
	private function deleteChangeRows( array $changes ): void {
		$this->changeStore->deleteChangesByChangeIds( array_map(
			function ( Change $change ): int {
				return $change->getId();
			},
			$changes
		) );
	}

	/**
	 * @param string[] $allClientWikis as returned by getClientWikis().
	 * @param string[] $subscribedClientSites sites subscribed to this entityId
	 * @param string[] $wikisWithSitelinkChanges sites whose sitelink was changed
	 *
	 * @return string[] A mapping of client wiki site IDs to logical database names.
	 */
	private function filterClientWikis( array $allClientWikis, array $subscribedClientSites, array $wikisWithSitelinkChanges ): array {
		Assert::parameterElementType( 'string', $allClientWikis, '$allClientWikis' );

		$clientWikis = [];
		foreach ( array_unique( array_merge( $subscribedClientSites, $wikisWithSitelinkChanges ) ) as $siteID ) {
			if ( array_key_exists( $siteID, $allClientWikis ) ) {
				$clientWikis[$siteID] = $allClientWikis[$siteID];
			} else {
				$metric = $this->statsFactory->getCounter(
					'dispatchChangesJob_clientWikiWithoutConfig_total'
				)
				->setLabels( [ "db" => $this->statsPrefix, "siteId" => $siteID ] );
				$metric->copyToStatsdAt( "$this->statsPrefix.wikibase.repo.dispatchChangesJob.clientWikiWithoutConfig" )
					->increment();
				$this->logger->warning(
					__METHOD__ . ': No client wiki with site ID {siteID} configured in \$wgWBRepoSettings["localClientDatabases"]!',
					[
						'siteID' => $siteID,
					]
				);
			}
		}

		return $clientWikis;
	}

	/**
	 * @return string[] A mapping of client wiki site IDs to logical database names.
	 */
	private function getClientWikis( SettingsArray $repoSettings ): array {
		$clientWikis = $repoSettings->getSetting( 'localClientDatabases' );
		// make sure we have a mapping from siteId to database name in clientWikis:
		foreach ( $clientWikis as $siteID => $dbName ) {
			if ( is_int( $siteID ) ) {
				unset( $clientWikis[$siteID] );
				$siteID = $dbName;
			}
			$clientWikis[$siteID] = $dbName;
		}

		// If this repo is also a client, make sure it dispatches also to itself.
		if ( WikibaseSettings::isClientEnabled() ) {
			$clientSettings = WikibaseClient::getSettings();
			$repoName = $clientSettings->getSetting( 'repoSiteId' );
			$repoDb = MediaWikiServices::getInstance()->getMainConfig()->get( 'DBname' );

			if ( !isset( $clientWikis[$repoName] ) ) {
				$clientWikis[$repoName] = $repoDb;
			}
		}

		return $clientWikis;
	}

	/**
	 * @param EntityChange[] $changes
	 */
	private function getClientJobSpecification( array $changes ): IJobSpecification {
		$params = [
			'changes' => array_map( function ( EntityChange $change ) {
				$fields = $change->getFields();
				$fields[ChangeRow::INFO] = $change->getSerializedInfo();
				return $fields;
			}, $changes ),
		];

		return new JobSpecification(
			'EntityChangeNotification',
			$params
		);
	}
}
