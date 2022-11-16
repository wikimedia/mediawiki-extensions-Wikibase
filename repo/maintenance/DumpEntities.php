<?php

namespace Wikibase\Repo\Maintenance;

use ExtensionRegistry;
use Maintenance;
use MediaWiki\Settings\SettingsBuilder;
use MWException;
use Onoi\MessageReporter\ObservableMessageReporter;
use Wikibase\DataModel\Services\EntityId\EntityIdPager;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\Lib\Reporting\ExceptionHandler;
use Wikibase\Lib\Reporting\ReportingExceptionHandler;
use Wikibase\Lib\WikibaseSettings;
use Wikibase\Repo\Dumpers\DumpGenerator;
use Wikibase\Repo\IO\EntityIdReader;
use Wikibase\Repo\IO\LineReader;
use Wikibase\Repo\Store\Sql\SqlEntityIdPager;
use Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\AtEase\AtEase;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for generating a dump of entities in the repository.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
abstract class DumpEntities extends Maintenance {

	/**
	 * @var SqlEntityIdPagerFactory
	 */
	private $sqlEntityIdPagerFactory;

	/**
	 * @var bool|resource
	 */
	private $logFileHandle = false;

	/** @var string[] */
	private $existingEntityTypes = [];

	/** @var string[] */
	private $entityTypesToExcludeFromOutput = [];

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Generate a ' . $this->getDumpType() . ' dump from entities in the repository.' );

		$this->addOption( 'list-file', "A file containing one entity ID per line.", false, true );
		$this->addOption(
			'entity-type',
			"Only dump this kind of entity, e.g. `item` or `property`. Can be given multiple times.",
			false,
			true,
			false,
			/* $multiOccurrence */ true
		);
		$this->addOption( 'sharding-factor', "The number of shards (must be >= 1)", false, true );
		$this->addOption( 'shard', "The shard to output (must be less than the sharding-factor)", false, true );
		$this->addOption( 'batch-size', "The number of entities per processing batch", false, true );
		$this->addOption( 'output', "Output file (default is stdout). Will be overwritten.", false, true );
		$this->addOption( 'log', "Log file (default is stderr). Will be appended.", false, true );
		$this->addOption( 'quiet', "Disable progress reporting", false, false );
		$this->addOption( 'limit', "Limit how many entities are dumped.", false, true );
		$this->addOption( 'no-cache', "If this is set, don't try to read from an EntityRevisionCache.", false, false );
		$this->addOption(
			'first-page-id',
			'First page id to dump, use 1 to start with the first page. Use the reported last SqlEntityIdPager position + 1 ' .
				'to continue a previous run. Not compatible with --list-file.',
			false,
			true
		);
		$this->addOption(
			'last-page-id',
			'Page id of the last page to possibly include in the dump. Not compatible with --list-file.',
			false,
			true
		);
		$this->addOption(
			'ignore-missing',
			'Ignore missing IDs, do not report errors on them',
			false,
			false
		);
	}

	public function setDumpEntitiesServices(
		SqlEntityIdPagerFactory $sqlEntityIdPagerFactory,
		array $existingEntityTypes,
		array $entityTypesToExcludeFromOutput
	) {
		$this->sqlEntityIdPagerFactory = $sqlEntityIdPagerFactory;
		$this->existingEntityTypes = $existingEntityTypes;
		$this->entityTypesToExcludeFromOutput = $entityTypesToExcludeFromOutput;
	}

	/**
	 * Used in description of script at command-line
	 * @return string
	 */
	abstract protected function getDumpType(): string;

	/**
	 * Create concrete dumper instance
	 * @param resource $output
	 * @return DumpGenerator
	 */
	abstract protected function createDumper( $output );

	/**
	 * Outputs a message vis the output() method.
	 *
	 * @see MessageReporter::logMessage()
	 *
	 * @param string $message
	 */
	public function logMessage( $message ) {
		if ( $this->logFileHandle ) {
			fwrite( $this->logFileHandle, "$message\n" );
			fflush( $this->logFileHandle );
		} else {
			$this->output( "$message\n" );
		}
	}

	/**
	 * Opens the given file for use by logMessage().
	 *
	 * @param string $file use "-" as a shortcut for "php://stdout"
	 *
	 * @throws MWException
	 */
	private function openLogFile( $file ) {
		$this->closeLogFile();

		if ( $file === '-' ) {
			$file = 'php://stdout';
		}

		// wouldn't streams be nice...
		$this->logFileHandle = fopen( $file, 'a' );

		if ( !$this->logFileHandle ) {
			throw new MWException( 'Failed to open log file: ' . $file );
		}
	}

	/**
	 * Closes any currently open file opened with openLogFile().
	 */
	private function closeLogFile() {
		if ( $this->logFileHandle
			&& $this->logFileHandle !== STDERR
			&& $this->logFileHandle !== STDOUT
		) {
			fclose( $this->logFileHandle );
		}

		$this->logFileHandle = false;
	}

	/**
	 * Do the actual work. All child classes will need to implement this
	 */
	public function execute() {
		//TODO: more validation for options
		$shardingFactor = (int)$this->getOption( 'sharding-factor', 1 );
		$shard = (int)$this->getOption( 'shard', 0 );
		$batchSize = (int)$this->getOption( 'batch-size', 100 );
		$limit = (int)$this->getOption( 'limit', 0 );

		//TODO: Allow injection of an OutputStream for logging
		$this->openLogFile( $this->getOption( 'log', 'php://stderr' ) );

		$outFile = $this->getOption( 'output', 'php://stdout' );

		if ( $outFile === '-' ) {
			$outFile = 'php://stdout';
		}

		$output = fopen( $outFile, 'w' ); //TODO: Allow injection of an OutputStream

		if ( !$output ) {
			throw new MWException( 'Failed to open ' . $outFile . '!' );
		}

		if ( $this->hasOption( 'list-file' ) ) {
			$this->logMessage( "Dumping entities listed in " . $this->getOption( 'list-file' ) );
		}

		$entityTypes = $this->getEntityTypes();
		if ( empty( $entityTypes ) ) {
			$this->logMessage( "No entity types to dump" );
			$this->closeLogFile();
			return;
		}

		$this->logMessage( 'Dumping entities of type ' . implode( ', ', $entityTypes ) );

		if ( $shardingFactor ) {
			$this->logMessage( "Dumping shard $shard/$shardingFactor" );
		}

		$dumper = $this->createDumper( $output );
		$dumper->setLimit( $limit );

		$progressReporter = new ObservableMessageReporter();
		$progressReporter->registerReporterCallback( [ $this, 'logMessage' ] );
		$dumper->setProgressReporter( $progressReporter );

		$ignored = $this->hasOption( 'ignore-missing' ) ?
			[ EntityLookupException::class ] :
			[];
		$exceptionReporter = new ReportingExceptionHandler( $progressReporter, $ignored );
		$dumper->setExceptionHandler( $exceptionReporter );

		//NOTE: we filter for $entityType twice: filtering in the DB is efficient,
		//      but filtering in the dumper is needed when working from a list file.
		$dumper->setShardingFilter( $shardingFactor, $shard );
		$dumper->setEntityTypesFilter( $entityTypes );
		$dumper->setBatchSize( $batchSize );

		$db = WikibaseRepo::getRepoDomainDbFactory()->newRepoDb();
		$dumper->setBatchCallback( [ $db, 'autoReconfigure' ] );

		$idStream = $this->makeIdStream( $entityTypes, $exceptionReporter );
		AtEase::suppressWarnings();
		$dumper->generateDump( $idStream );
		AtEase::restoreWarnings();

		if ( $idStream instanceof EntityIdReader ) {
			// close stream / free resources
			$idStream->dispose();
		}

		$this->closeLogFile();
	}

	/**
	 * @inheritDoc
	 */
	public function finalSetup( SettingsBuilder $settingsBuilder = null ) {
		global $wgHooks;

		parent::finalSetup( $settingsBuilder );

		if ( $this->hasOption( 'dbgroupdefault' ) ) {
			// A group was set via cli, so no need to set the default here
			return;
		}

		$wgHooks['MediaWikiServices'][] = function() {
			global $wgDBDefaultGroup;
			if ( !ExtensionRegistry::getInstance()->isLoaded( 'WikibaseRepository' ) ) {
				// Something instantiates the MediaWikiServices before Wikibase
				// is loaded, nothing we can do here.
				wfWarn( self::class . ': Can not change default DB group.' );
				return;
			}

			// Don't use WikibaseRepo or MediaWikiServices here as this is run very early on, thus
			// the bootstrapping code is not ready yet (T202452).
			$settings = WikibaseSettings::getRepoSettings();
			$dumpDBDefaultGroup = $settings->getSetting( 'dumpDBDefaultGroup' );

			if ( $dumpDBDefaultGroup !== null ) {
				$wgDBDefaultGroup = $dumpDBDefaultGroup;
			}
		};
	}

	/**
	 * @return string[]
	 */
	private function getEntityTypes(): array {
		$inputTypes = array_diff(
			$this->getOption( 'entity-type', $this->existingEntityTypes ),
			$this->entityTypesToExcludeFromOutput
		);

		$types = [];
		foreach ( $inputTypes as $type ) {
			if ( !in_array( $type, $this->existingEntityTypes ) ) {
				$this->logMessage( "Warning: Unknown entity type $type." );
				continue;
			}
			$types[] = $type;
		}

		return $types;
	}

	/**
	 * @param string[] $entityTypes
	 * @param ExceptionHandler|null $exceptionReporter
	 *
	 * @return EntityIdReader|SqlEntityIdPager a stream of EntityId objects
	 */
	private function makeIdStream( array $entityTypes, ExceptionHandler $exceptionReporter = null ) {
		$listFile = $this->getOption( 'list-file' );

		if ( $listFile !== null ) {
			$stream = $this->makeIdFileStream( $listFile, $exceptionReporter );
		} else {
			$stream = $this->makeIdQueryStream( $entityTypes );
		}

		return $stream;
	}

	/**
	 * Returns EntityIdPager::NO_REDIRECTS.
	 *
	 * @return mixed a EntityIdPager::XXX_REDIRECTS constant
	 */
	protected function getRedirectMode() {
		return EntityIdPager::NO_REDIRECTS;
	}

	/**
	 * Cache flag for use in Store::getEntityRevisionLookup.
	 *
	 * @return string One of Store::LOOKUP_CACHING_RETRIEVE_ONLY and Store::LOOKUP_CACHING_DISABLED
	 */
	protected function getEntityRevisionLookupCacheMode() {
		if ( $this->getOption( 'no-cache', false ) ) {
			return Store::LOOKUP_CACHING_DISABLED;
		} else {
			return Store::LOOKUP_CACHING_RETRIEVE_ONLY;
		}
	}

	/**
	 * @param string[] $entityTypes
	 *
	 * @return SqlEntityIdPager
	 */
	private function makeIdQueryStream( array $entityTypes ) {
		$sqlEntityIdPager = $this->sqlEntityIdPagerFactory->newSqlEntityIdPager( $entityTypes, $this->getRedirectMode() );

		$firstPageId = $this->getOption( 'first-page-id', null );
		if ( $firstPageId ) {
			$sqlEntityIdPager->setPosition( intval( $firstPageId ) - 1 );
		}
		$lastPageId = $this->getOption( 'last-page-id', null );
		if ( $lastPageId ) {
			$sqlEntityIdPager->setCutoffPosition( intval( $lastPageId ) );
		}

		return $sqlEntityIdPager;
	}

	/**
	 * @param string $listFile
	 * @param ExceptionHandler|null $exceptionReporter
	 *
	 * @throws MWException
	 * @return EntityIdReader
	 */
	private function makeIdFileStream( $listFile, ExceptionHandler $exceptionReporter = null ) {
		$input = fopen( $listFile, 'r' );

		if ( !$input ) {
			throw new MWException( "Failed to open ID file: $listFile" );
		}

		$stream = new EntityIdReader( new LineReader( $input ), WikibaseRepo::getEntityIdParser() );
		$stream->setExceptionHandler( $exceptionReporter );

		return $stream;
	}

}
