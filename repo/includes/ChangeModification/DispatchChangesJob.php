<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\ChangeModification;

use IJobSpecification;
use InvalidArgumentException;
use Job;
use JobSpecification;
use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use MediaWiki\JobQueue\JobQueueGroupFactory;
use MediaWiki\MediaWikiServices;
use MWException;
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

	/*
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var StatsdDataFactoryInterface
	 */
	private $stats;

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
		StatsdDataFactoryInterface $stats,
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
		$this->stats = $stats;
		$this->removeDuplicates = true;

		parent::__construct( 'DispatchChanges', $params );
	}

	public static function newFromGlobalState( $unused, array $params ): self {
		return new self(
			new SqlSubscriptionLookup(
				WikibaseRepo::getRepoDomainDbFactory()->newRepoDb()
			),
			WikibaseRepo::getEntityChangeLookup(),
			WikibaseRepo::getEntityIdParser(),
			MediaWikiServices::getInstance()->getJobQueueGroupFactory(),
			WikibaseRepo::getStore()->getChangeStore(),
			WikibaseRepo::getLogger(),
			MediaWikiServices::getInstance()->getPerDbNameStatsdDataFactory(),
			$params
		);
	}

	/**
	 * @throws MWException
	 */
	public function run(): bool {
		// TODO: for v2 of this job, we could actually get all the newest revision from the DB,
		//       calculate the change_info ourselves and thus make wb_changes table obsolete?

		$repoSettings = WikibaseRepo::getSettings();
		$allClientSites = $this->getClientWikis( $repoSettings );
		$entityId = $this->entityIdParser->parse( $this->entityIdSerialization );
		$subscribedClientSites = $this->subscriptionLookup->getSubscribers( $entityId );

		$changes = $this->changeLookup->loadByEntityIdFromPrimary( $this->entityIdSerialization );

		if ( empty( $changes ) ) {
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
		if ( empty( $dispatchingClientSites ) ) {
			// without subscribed wikis, this job should never have been scheduled
			$this->logger->warning( __METHOD__ . ': no wikis subscribed for {entity} => doing nothing', [
				'entity' => $this->entityIdSerialization,
			] );

			$this->deleteChangeRows( $changes );

			return true;
		}

		$this->logUnsubscribedWikisWithSitelinkChanges( $dispatchingClientSites, $subscribedClientSites );
		// StatsdDataFactoryInterface::timing is used so that p99 and similar aggregation is available
		$this->stats->timing( 'wikibase.repo.dispatchChangesJob.NumberOfChangesInJob', count( $changes ) );
		$this->stats->timing( 'wikibase.repo.dispatchChangesJob.numberOfWikisForChange', count( $dispatchingClientSites ) );

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

	private function logUnsubscribedWikisWithSitelinkChanges( $dispatchingClientSites, $subscribedClientSites ) {
		$clientsWithAddedSitelinks = array_diff( array_keys( $dispatchingClientSites ), $subscribedClientSites );
		foreach ( $clientsWithAddedSitelinks as $wikiId ) {
			$this->stats->increment( "wikibase.repo.dispatchChangesJob.sitelinkAdditionDispatched.{$wikiId}" );
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
	 * @throws MWException
	 * @return string[] A mapping of client wiki site IDs to logical database names.
	 */
	private function filterClientWikis( array $allClientWikis, array $subscribedClientSites, array $wikisWithSitelinkChanges ): array {
		Assert::parameterElementType( 'string', $allClientWikis, '$allClientWikis' );

		$clientWikis = [];
		foreach ( array_unique( array_merge( $subscribedClientSites, $wikisWithSitelinkChanges ) ) as $siteID ) {
			if ( array_key_exists( $siteID, $allClientWikis ) ) {
				$clientWikis[$siteID] = $allClientWikis[$siteID];
			} else {
				$this->stats->increment( 'wikibase.repo.dispatchChangesJob.clientWikiWithoutConfig' );
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
