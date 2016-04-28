<?php

namespace Wikibase;

use Maintenance;
use MWException;
use Wikibase\Dumpers\DumpGenerator;
use Wikibase\Lib\Reporting\ExceptionHandler;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Lib\Reporting\ReportingExceptionHandler;
use Wikibase\Repo\Disposable;
use Wikibase\Repo\IO\EntityIdReader;
use Wikibase\Repo\IO\LineReader;
use Wikibase\Repo\Store\EntityIdPager;
use Wikibase\Repo\Store\EntityPerPage;
use Wikibase\Repo\Store\SQL\EntityPerPageIdPager;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for generating a dump of entities in the repository.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
abstract class DumpScript extends Maintenance {

	/**
	 * @var EntityPerPage
	 */
	private $entityPerPage;

	/**
	 * @var bool|resource
	 */
	private $logFileHandle = false;

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Generate a JSON dump from entities in the repository.' );

		$this->addOption( 'list-file', "A file containing one entity ID per line.", false, true );
		$this->addOption( 'entity-type', "Only dump this kind of entity, e.g. `item` or `property`.", false, true );
		$this->addOption( 'sharding-factor', "The number of shards (must be >= 1)", false, true );
		$this->addOption( 'shard', "The shard to output (must be less than the sharding-factor)", false, true );
		$this->addOption( 'batch-size', "The number of entities per processing batch", false, true );
		$this->addOption( 'output', "Output file (default is stdout). Will be overwritten.", false, true );
		$this->addOption( 'log', "Log file (default is stderr). Will be appended.", false, true );
		$this->addOption( 'quiet', "Disable progress reporting", false, false );
		$this->addOption( 'limit', "Limit how many entities are dumped.", false, true );
	}

	public function setDumpEntitiesServices( EntityPerPage $entityPerPage ) {
		$this->entityPerPage = $entityPerPage;
	}

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
		$entityType = $this->getOption( 'entity-type' );
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

		if ( $entityType ) {
			$this->logMessage( "Dumping entities of type $entityType" );
		}

		if ( $shardingFactor ) {
			$this->logMessage( "Dumping shard $shard/$shardingFactor" );
		}

		$dumper = $this->createDumper( $output );
		$dumper->setLimit( $limit );

		$progressReporter = new ObservableMessageReporter();
		$progressReporter->registerReporterCallback( array( $this, 'logMessage' ) );
		$dumper->setProgressReporter( $progressReporter );

		$exceptionReporter = new ReportingExceptionHandler( $progressReporter );
		$dumper->setExceptionHandler( $exceptionReporter );

		//NOTE: we filter for $entityType twice: filtering in the DB is efficient,
		//      but filtering in the dumper is needed when working from a list file.
		$dumper->setShardingFilter( $shardingFactor, $shard );
		$dumper->setEntityTypeFilter( $entityType );
		$dumper->setBatchSize( $batchSize );

		$idStream = $this->makeIdStream( $entityType, $exceptionReporter );
		\MediaWiki\suppressWarnings();
		$dumper->generateDump( $idStream );
		\MediaWiki\restoreWarnings();

		if ( $idStream instanceof Disposable ) {
			// close stream / free resources
			$idStream->dispose();
		}

		$this->closeLogFile();
	}

	/**
	 * @param null|string $entityType
	 * @param ExceptionHandler|null $exceptionReporter
	 *
	 * @return EntityIdPager a stream of EntityId objects
	 */
	private function makeIdStream( $entityType = null, ExceptionHandler $exceptionReporter = null ) {
		$listFile = $this->getOption( 'list-file' );

		if ( $listFile !== null ) {
			$stream = $this->makeIdFileStream( $listFile, $exceptionReporter );
		} else {
			$stream = $this->makeIdQueryStream( $entityType );
		}

		return $stream;
	}

	/**
	 * Returns EntityPerPage::NO_REDIRECTS.
	 *
	 * @return mixed a EntityPerPage::XXX_REDIRECTS constant
	 */
	protected function getRedirectMode() {
		return EntityPerPage::NO_REDIRECTS;
	}

	/**
	 * @param string|null $entityType
	 *
	 * @return EntityIdPager
	 */
	private function makeIdQueryStream( $entityType ) {
		$stream = new EntityPerPageIdPager( $this->entityPerPage, $entityType, $this->getRedirectMode() );
		return $stream;
	}

	/**
	 * @param string $listFile
	 * @param ExceptionHandler|null $exceptionReporter
	 *
	 * @throws MWException
	 * @return EntityIdPager
	 */
	private function makeIdFileStream( $listFile, ExceptionHandler $exceptionReporter = null ) {
		$input = fopen( $listFile, 'r' );

		if ( !$input ) {
			throw new MWException( "Failed to open ID file: $input" );
		}

		$stream = new EntityIdReader( new LineReader( $input ), WikibaseRepo::getDefaultInstance()->getEntityIdParser() );
		$stream->setExceptionHandler( $exceptionReporter );

		return $stream;
	}

}
