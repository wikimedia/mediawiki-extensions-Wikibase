<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Changes;

use CannotCreateActorException;
use InvalidArgumentException;
use Job;
use JobSpecification;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\RecentChanges\RecentChangesFinder;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\Changes\ChangeRow;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\Store\Sql\EntityChangeLookup;
use Wikimedia\Assert\Assert;
use Wikimedia\Stats\StatsFactory;

/**
 * Job for injecting RecentChange records representing changes on the Wikibase repository.
 *
 * @see @ref docs_topics_change-propagation for an overview of the change propagation mechanism.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class InjectRCRecordsJob extends Job {

	/**
	 * @var EntityChangeLookup
	 */
	private $changeLookup;

	/**
	 * @var EntityChangeFactory
	 */
	private $changeFactory;

	/**
	 * @var RecentChangeFactory
	 */
	private $rcFactory;

	/**
	 * @var RecentChangesFinder|null
	 */
	private $rcDuplicateDetector = null;

	/**
	 * @var TitleFactory
	 */
	private $titleFactory;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	private ?StatsFactory $statsFactory = null;

	private ?string $dbName = null;

	/**
	 * @param Title[] $titles
	 * @param EntityChange $change
	 * @param array $rootJobParams
	 *
	 * @return JobSpecification
	 */
	public static function makeJobSpecification(
		array $titles,
		EntityChange $change,
		array $rootJobParams = []
	): JobSpecification {
		$pages = [];

		foreach ( $titles as $t ) {
			$id = $t->getArticleID();
			$pages[$id] = [ $t->getNamespace(), $t->getDBkey() ];
		}

		// Note: Avoid serializing Change objects. Original changes can be restored
		// from $changeData['info']['change-ids'], see getChange().
		$changeData = $change->getFields();
		$changeData[ChangeRow::INFO] = $change->getSerializedInfo( [ 'changes' ] );

		// See WikiPageUpdater::buildJobParams and ChangeHandler::handleChange for relevant root job parameters.
		$params = array_merge( $rootJobParams, [
			'change' => $changeData,
			'pages' => $pages,
		] );

		return new JobSpecification(
			'wikibase-InjectRCRecords',
			$params
		);
	}

	/**
	 * Constructs an InjectRCRecordsJob for injecting a change into the recentchanges feed
	 * for the given pages.
	 *
	 * @param EntityChangeLookup $changeLookup
	 * @param EntityChangeFactory $changeFactory
	 * @param RecentChangeFactory $rcFactory
	 * @param TitleFactory $titleFactory
	 * @param array $params Needs to have two keys: "change": the id of the change,
	 *     "pages": array of pages, represented as $pageId => [ $namespace, $dbKey ].
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		EntityChangeLookup $changeLookup,
		EntityChangeFactory $changeFactory,
		RecentChangeFactory $rcFactory,
		TitleFactory $titleFactory,
		array $params
	) {
		$title = Title::makeTitle( NS_SPECIAL, 'Badtitle/' . __CLASS__ );
		parent::__construct( 'wikibase-InjectRCRecords', $title, $params );

		Assert::parameter(
			isset( $params['change'] ),
			'$params',
			'$params[\'change\'] not set.'
		);
		// TODO: disallow integer once T172394 has been deployed and old jobs have cleared the queue.
		Assert::parameterType(
			[ 'integer', 'array' ],
			$params['change'],
			'$params[\'change\']'
		);

		Assert::parameter(
			isset( $params['pages'] ),
			'$params',
			'$params[\'pages\'] not set.'
		);
		Assert::parameterElementType(
			'array',
			$params['pages'],
			'$params[\'pages\']'
		);

		$this->changeLookup = $changeLookup;
		$this->changeFactory = $changeFactory;
		$this->rcFactory = $rcFactory;
		$this->titleFactory = $titleFactory;
		$this->logger = new NullLogger();
	}

	public static function newFromGlobalState( Title $unused, array $params ): self {
		$mwServices = MediaWikiServices::getInstance();

		$store = WikibaseClient::getStore( $mwServices );

		$job = new self(
			WikibaseClient::getEntityChangeLookup( $mwServices ),
			WikibaseClient::getEntityChangeFactory( $mwServices ),
			WikibaseClient::getRecentChangeFactory( $mwServices ),
			$mwServices->getTitleFactory(),
			$params
		);

		$job->setRecentChangesFinder( $store->getRecentChangesFinder() );

		$job->setLogger( WikibaseClient::getLogger( $mwServices ) );
		$job->setStatsFactory(
			$mwServices->getStatsFactory()->withComponent( 'WikibaseClient' ),
			$mwServices->getMainConfig()->get( MainConfigNames::DBname )
		);

		return $job;
	}

	public function setRecentChangesFinder( RecentChangesFinder $rcDuplicateDetector ): void {
		$this->rcDuplicateDetector = $rcDuplicateDetector;
	}

	public function setLogger( LoggerInterface $logger ): void {
		$this->logger = $logger;
	}

	public function setStatsFactory( StatsFactory $statsFactory, string $dbName ): void {
		$this->statsFactory = $statsFactory;
		$this->dbName = $dbName;
	}

	/**
	 * Returns the change that should be processed.
	 *
	 * EntityChange objects are loaded using a EntityChangeLookup.
	 *
	 * @return EntityChange|null the change to process (or none).
	 */
	private function getChange(): ?EntityChange {
		$params = $this->getParams();
		$change = $this->changeFactory->newFromFieldData( $params['change'] );

		// If the current change was composed of other child changes, restore the
		// child objects.
		$info = $change->getInfo();
		if ( isset( $info['change-ids'] ) && !isset( $info['changes'] ) ) {
			$children = $this->changeLookup->loadByChangeIds( $info['change-ids'] );
			$info['changes'] = $children;
			$change->setField( ChangeRow::INFO, $info );
		}

		return $change;
	}

	/**
	 * @return Title[] List of Titles to inject RC entries for, indexed by page ID
	 */
	private function getTitles(): array {
		$params = $this->getParams();
		$pages = $params['pages'];

		$titles = [];

		foreach ( $pages as $pageId => [ $namespace, $dbKey ] ) {
			$titles[$pageId] = $this->titleFactory->makeTitle( $namespace, $dbKey );
		}

		return $titles;
	}

	/**
	 * @return bool success
	 */
	public function run(): bool {
		$change = $this->getChange();
		$titles = $this->getTitles();

		if ( !$change || $titles === [] ) {
			return false;
		}

		$rcAttribs = $this->rcFactory->prepareChangeAttributes( $change );

		foreach ( $titles as $title ) {
			if ( !$title->exists() ) {
				continue;
			}

			$rc = $this->rcFactory->newRecentChange( $change, $title, $rcAttribs );

			if ( $this->rcDuplicateDetector
				&& $this->rcDuplicateDetector->getRecentChangeId( $rc ) !== null
			) {
				$this->logger->debug( __METHOD__ . ": skipping duplicate RC entry for " . $title->getFullText() );
			} else {
				$this->logger->debug( __METHOD__ . ": saving RC entry for " . $title->getFullText() );
				try {
					$rc->save();
				} catch ( CannotCreateActorException $e ) {
					$this->logger->error(
						__METHOD__ . ': cannot create actor {rc_user_text} for RC entry for {title}, skipping;'
						. ' misconfigured ExternalUserNames?',
						[
							'exception' => $e,
							'rc_user_text' => $rc->getAttribute( 'rc_user_text' ),
							'title' => $title->getFullText(),
						]
					);
				}
			}
		}

		if ( $this->statsFactory !== null ) {
			$this->statsFactory
				->getCounter( 'PageUpdates_InjectRCRecords_run_titles_total' )
				->setLabel( 'DBname', $this->dbName )
				->copyToStatsdAt( [
					'wikibase.client.pageupdates.InjectRCRecords.run.titles',
				] )
				->incrementBy( count( $titles ) );
			$this->statsFactory
				->getTiming( 'PageUpdates_InjectRCRecords_delay_seconds' )
				->setLabel( 'DBname', $this->dbName )
				->copyToStatsdAt( [
					'wikibase.client.pageupdates.InjectRCRecords.delay',
					"{$this->dbName}.wikibase.client.pageupdates.InjectRCRecords.delay",
				] )
				->observe( $change->getAge() * 1000 );
		}

		return true;
	}
}
