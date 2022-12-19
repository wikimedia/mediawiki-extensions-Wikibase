<?php

declare( strict_types=1 );
namespace Wikibase\Repo;

use Config;
use DeferredUpdates;
use ExtensionRegistry;
use FormatJson;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MWCryptRand;
use MWTimestamp;
use ObjectCache;
use Psr\Log\LoggerInterface;
use RequestContext;
use SiteStats;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\SettingsArray;
use Wikimedia\Rdbms\ConnectionManager;

/**
 * Send information about this Wikibase instance to TODO.
 *
 * @license GPL-2.0-or-later
 * @see Pingback
 */
class WikibasePingback {
	/**
	 * @var int Revision ID of the JSON schema that describes the pingback
	 *   payload. The schema lives on MetaWiki, at
	 *   <https://meta.wikimedia.org/wiki/Schema:WikibasePingback>
	 */
	private const SCHEMA_REV = 20782637;

	/**
	 * @var int The minimum number of entities in a non-empty Wikibase
	 */
	private const MINIMUM_NUMBER_OF_ENTITIES = 10;

	/** @var LoggerInterface */
	protected $logger;

	/** @var Config */
	protected $config;

	/** @var string updatelog key (also used as cache/db lock key) */
	protected $key;

	/** @var string Randomly-generated identifier for this wiki */
	protected $id;

	/**
	 * @var int
	 */
	public const HEARTBEAT_TIMEOUT = 60 * 60 * 24 * 30;

	/**
	 * @var string
	 */
	private $host;

	/**
	 * @var ExtensionRegistry
	 */
	private $extensionRegistry;

	/**
	 * @var SettingsArray
	 */
	private $wikibaseRepoSettings;

	/**
	 * @var HttpRequestFactory
	 */
	private $requestFactory;

	/**
	 * @var ConnectionManager
	 */
	private $repoConnections;

	/**
	 * @param Config|null $config
	 * @param LoggerInterface|null $logger
	 * @param ExtensionRegistry|null $extensionRegistry
	 * @param SettingsArray|null $wikibaseRepoSettings
	 * @param HttpRequestFactory|null $requestFactory
	 * @param RepoDomainDb|null $repoDomainDb
	 * @param string|null $key
	 */
	public function __construct(
		Config $config = null,
		LoggerInterface $logger = null,
		ExtensionRegistry $extensionRegistry = null,
		SettingsArray $wikibaseRepoSettings = null,
		HTTPRequestFactory $requestFactory = null,
		RepoDomainDb $repoDomainDb = null,
		string $key = null
	) {
		$this->config = $config ?: RequestContext::getMain()->getConfig();
		$this->logger = $logger ?: LoggerFactory::getInstance( __CLASS__ );
		$this->extensionRegistry = $extensionRegistry ?: ExtensionRegistry::getInstance();
		$this->wikibaseRepoSettings = $wikibaseRepoSettings ?: WikibaseRepo::getSettings();
		$this->requestFactory = $requestFactory ?: MediaWikiServices::getInstance()->getHttpRequestFactory();
		$this->repoConnections = $repoDomainDb ? $repoDomainDb->connections() :
			WikibaseRepo::getRepoDomainDbFactory()->newRepoDb()->connections();

		$this->key = $key ?: 'WikibasePingback-' . MW_VERSION;
		$this->host = $this->wikibaseRepoSettings->getSetting( 'pingbackHost' );
	}

	/**
	 * Should a pingback be sent?
	 * @return bool
	 */
	private function shouldSend() {
		return $this->wikibaseRepoSettings->getSetting( 'wikibasePingback' ) && !$this->checkIfSent();
	}

	/**
	 * Has a pingback been sent in the last month for this MediaWiki version?
	 * @return bool
	 */
	private function checkIfSent() {
		$dbr = $this->repoConnections->getReadConnection();

		$timestamp = $dbr->newSelectQueryBuilder()
			->select( 'ul_value' )
			->from( 'updatelog' )
			->where( [ 'ul_key' => $this->key ] )
			->caller( __METHOD__ )
			->fetchField();

		if ( $timestamp === false ) {
			return false;
		}
		// send heartbeat ping if last ping was over a month ago
		if ( MWTimestamp::time() - (int)$timestamp >= self::HEARTBEAT_TIMEOUT ) {
			return false;
		}
		return true;
	}

	/**
	 * Record the fact that we have sent a pingback for this Wikibase version,
	 * to ensure we don't submit data multiple times.
	 * @return bool
	 */
	private function markSent() {
		$dbw = $this->repoConnections->getWriteConnection();

		$timestamp = MWTimestamp::time();

		return $dbw->upsert(
			'updatelog',
			[ 'ul_key' => $this->key, 'ul_value' => $timestamp ],
			'ul_key',
			[ 'ul_value' => $timestamp ],
			__METHOD__
		);
	}

	/**
	 * Acquire lock for sending a pingback
	 *
	 * This ensures only one thread can attempt to send a pingback at any given
	 * time and that we wait an hour before retrying failed attempts.
	 *
	 * @return bool Whether lock was acquired
	 */
	private function acquireLock() {
		$cache = ObjectCache::getLocalClusterInstance();
		if ( !$cache->add( $this->key, 1, 60 * 60 ) ) {
			return false;  // throttled
		}

		$dbw = $this->repoConnections->getWriteConnection();

		if ( !$dbw->lock( $this->key, __METHOD__, 0 ) ) {
			return false;  // already in progress
		}

		return true;
	}

	private function getTrackedExtensions(): array {
		$extensions = [
			'WikibaseManifest' => 'WBM',
			'EntitySchema' => 'ENS',
			'PropertySuggester' => 'PS',
			'WikibaseImport' => 'WBI',
			'WikibaseLexeme' => 'WBL',
			'WikibaseQualityConstraints' => 'WBQC',
			'WikibaseCirrusSearch' => 'WBCS',
			'WikibaseMediaInfo' => 'WBMI',
			'OAuth' => 'OA',
			'ConfirmEdit' => 'CE',
			'Nuke' => 'NKE',
			'UniversalLanguageSelector' => 'ULS',
			'CLDR' => 'CLDR',
			'VisualEditor' => 'VE',
			'Scribunto' => 'SCRI',
			'SyntaxHighlight' => 'SH',
			'Babel' => 'BBL',
			'Auth_remoteuser' => 'AR',
			'ArticlePlaceholder' => 'AP',
		];

		$currentExtensions = array_keys( $this->extensionRegistry->getAllThings() );

		return array_reduce( $currentExtensions, function ( $tracked, $current ) use ( $extensions ) {
			return array_key_exists( $current, $extensions )
				? array_merge( $tracked, [ $extensions[ $current ] ] )
				: $tracked;
		}, [] );
	}

	/**
	 * Collect basic data about this MediaWiki installation and return it
	 * as an associative array conforming to the Pingback schema on MetaWiki
	 * (<https://meta.wikimedia.org/wiki/Schema:MediaWikiPingback>).
	 *
	 * This is public so we can display it in the installer
	 *
	 * Developers: If you're adding a new piece of data to this, please ensure
	 * that you update https://www.mediawiki.org/wiki/Manual:$wgPingback
	 *
	 * @return array
	 */
	public function getSystemInfo() {
		$extensions = $this->getTrackedExtensions();
		$federation = $this->wikibaseRepoSettings->getSetting( 'federatedPropertiesEnabled' );
		$hasEntities = SiteStats::pages() > self::MINIMUM_NUMBER_OF_ENTITIES;

		$event = [
			'database'   => $this->config->get( 'DBtype' ),
			'mediawiki'  => MW_VERSION,
			'hasEntities'  => $hasEntities,
			'federation'  => $federation,
			'extensions'  => $extensions,
			'termbox' => $this->wikibaseRepoSettings->getSetting( 'termboxEnabled' ),
		];

		$limit = ini_get( 'memory_limit' );
		if ( $limit && $limit != -1 ) {
			$event['memoryLimit'] = $limit;
		}

		return $event;
	}

	/**
	 * Get the EventLogging packet to be sent to the server
	 *
	 * @return array
	 */
	private function getData() {
		return [
			'schema'           => 'WikibasePingback',
			'revision'         => self::SCHEMA_REV,
			'wiki'             => $this->getOrCreatePingbackId(),
			'event'            => $this->getSystemInfo(),
		];
	}

	/**
	 * Get a unique, stable identifier for this wiki
	 *
	 * If the identifier does not already exist, create it and save it in the
	 * database. The identifier is randomly-generated.
	 *
	 * @return string 32-character hex string
	 */
	private function getOrCreatePingbackId() {
		if ( !$this->id ) {
			$dbr = $this->repoConnections->getReadConnection();

			$id = $dbr->newSelectQueryBuilder()
				->select( 'ul_value' )
				->from( 'updatelog' )
				->where( [ 'ul_key' => 'WikibasePingback' ] )
				->caller( __METHOD__ )
				->fetchField();

			if ( $id === false ) {
				$id = MWCryptRand::generateHex( 32 );
				$dbw = $this->repoConnections->getWriteConnection();
				$dbw->insert(
					'updatelog',
					[ 'ul_key' => 'WikibasePingback', 'ul_value' => $id ],
					__METHOD__,
					[ 'IGNORE' ]
				);

				if ( !$dbw->affectedRows() ) {
					$id = $dbw->newSelectQueryBuilder()
						->select( 'ul_value' )
						->from( 'updatelog' )
						->where( [ 'ul_key' => 'WikibasePingback' ] )
						->caller( __METHOD__ )
						->fetchField();
				}
			}

			$this->id = $id;
		}

		return $this->id;
	}

	/**
	 * Serialize pingback data and send it to MediaWiki.org via a POST
	 * to its event beacon endpoint.
	 *
	 * The data encoding conforms to the expectations of EventLogging,
	 * a software suite used by the Wikimedia Foundation for logging and
	 * processing analytic data.
	 *
	 * Compare:
	 * <https://github.com/wikimedia/mediawiki-extensions-EventLogging/
	 *   blob/7e5fe4f1ef/includes/EventLogging.php#L32-L74>
	 *
	 * @param array $data Pingback data as an associative array
	 * @return bool true on success, false on failure
	 */
	private function postPingback( array $data ) {
		$json = FormatJson::encode( $data );
		$queryString = rawurlencode( str_replace( ' ', '\u0020', $json ) ) . ';';
		$url = $this->host . '?' . $queryString;
		$response = $this->requestFactory->post( $url, [], __METHOD__ );
		return $response !== null;
	}

	/**
	 * @return bool
	 */
	public function sendPingback() {
		if ( !$this->acquireLock() ) {
			$this->logger->debug( __METHOD__ . ": couldn't acquire lock" );
			return false;
		}

		$data = $this->getData();
		if ( !$this->postPingback( $data ) ) {
			$this->logger->warning( __METHOD__ . ": failed to send pingback; check 'http' log" );
			return false;
		}

		$this->markSent();
		$this->logger->debug( __METHOD__ . ": pingback sent OK ({$this->key})" );
		return true;
	}

	/**
	 * Schedule a deferred callable that will check if a pingback should be
	 * sent and (if so) proceed to send it.
	 */
	public static function schedulePingback() {
		DeferredUpdates::addCallableUpdate( function () {
			WikibasePingback::doSchedule();
		} );
	}

	public static function doSchedule( WikibasePingback $instance = null ) {
		$instance = $instance ?: new WikibasePingback;
		if ( $instance->shouldSend() ) {
			$instance->sendPingback();
		}
	}
}
