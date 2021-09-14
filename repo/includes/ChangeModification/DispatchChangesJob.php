<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\ChangeModification;

use IJobSpecification;
use InvalidArgumentException;
use Job;
use JobSpecification;
use MediaWiki\JobQueue\JobQueueGroupFactory;
use MediaWiki\MediaWikiServices;
use MWException;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Changes\Change;
use Wikibase\Lib\Changes\ChangeStore;
use Wikibase\Lib\Changes\EntityChange;
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

	public static function makeJobSpecification( string $entityIdSerialization ): IJobSpecification {
		return new JobSpecification(
			'DispatchChanges',
			[
				'title' => $entityIdSerialization,
				'entityId' => $entityIdSerialization,
			]
		);
	}

	public function __construct(
		SubscriptionLookup $subscriptionLookup,
		EntityChangeLookup $changeLookup,
		EntityIdParser $entityIdParser,
		JobQueueGroupFactory $jobQueueGroupFactory,
		ChangeStore $changeStore,
		array $params
	) {

		if ( empty( $params['entityId'] ) ) {
			throw new InvalidArgumentException( 'entityId parameter missing' );
		}

		$this->entityIdSerialization = $params['entityId'];
		$this->subscriptionLookup = $subscriptionLookup;
		$this->changeLookup = $changeLookup;
		$this->entityIdParser = $entityIdParser;
		$this->jobQueueGroupFactory = $jobQueueGroupFactory;
		$this->changeStore = $changeStore;
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
			$params
		);
	}

	public function getDeduplicationInfo(): array {
		return [
			'entityId' => $this->entityIdSerialization,
		];
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
		$allowedClientSites = WikibaseRepo::getSettings()->getSetting( 'dispatchViaJobsAllowedClients' );

		$dispatchingClientSites = $this->filterClientWikis( $allClientSites, $subscribedClientSites, $allowedClientSites );
		if ( empty( $dispatchingClientSites ) ) {
			return true;
		}

		$changes = $this->changeLookup->loadByEntityId( $this->entityIdSerialization );
		if ( empty( $changes ) ) {
			return true;
		}

		$job = $this->getClientJobSpecification( $changes );
		foreach ( $dispatchingClientSites as $clientDb ) {
			$jobQueueGroup = $this->jobQueueGroupFactory->makeJobQueueGroup( $clientDb );
			$jobQueueGroup->lazyPush( $job );
		}

		if ( $repoSettings->getSetting( 'dispatchViaJobsPruneChangesTableInJobEnabled' ) ) {
			$this->deleteChangeRows( $changes );
		}

		return true;
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
	 * @param string[]|null $allowedSiteIDs site IDs to select, or null to allow all
	 *
	 * @throws MWException
	 * @return string[] A mapping of client wiki site IDs to logical database names.
	 */
	private function filterClientWikis( array $allClientWikis, array $subscribedClientSites, ?array $allowedSiteIDs ): array {
		Assert::parameterElementType( 'string', $allClientWikis, '$allClientWikis' );

		if ( $allowedSiteIDs === null ) {
			$dispatchingClientSites = $subscribedClientSites;
		} else {
			Assert::parameterElementType( 'string', $allowedSiteIDs, '$allowedSiteIDs' );
			$dispatchingClientSites = array_intersect( $subscribedClientSites, $allowedSiteIDs );
		}

		$clientWikis = [];
		foreach ( $dispatchingClientSites as $siteID ) {
			if ( array_key_exists( $siteID, $allClientWikis ) ) {
				$clientWikis[$siteID] = $allClientWikis[$siteID];
			} else {
				throw new MWException(
					"No client wiki with site ID $siteID configured! " .
					"Please check \$wgWBRepoSettings['localClientDatabases']."
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
				$fields['info'] = $change->getSerializedInfo();
				return $fields;
			}, $changes ),
		];

		return new JobSpecification(
			'EntityChangeNotification',
			$params
		);
	}
}
