<?php

namespace Wikibase;

use Disposable;
use Maintenance;
use MWException;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Dumpers\JsonDumpGenerator;
use Wikibase\Lib\Reporting\ExceptionHandler;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Lib\Reporting\ReportingExceptionHandler;
use Wikibase\Lib\Serializers\DispatchingEntitySerializer;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\Serializer;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Repo\IO\EntityIdReader;
use Wikibase\Repo\IO\LineReader;
use Wikibase\Repo\Store\EntityIdPager;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for generating a JSON dump of entities in the repository.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DumpJson extends Maintenance {

	/**
	 * @var EntityLookup
	 */
	public $entityLookup;

	/**
	 * @var Serializer
	 */
	public $entitySerializer;

	/**
	 * @var EntityPerPage
	 */
	public $entityPerPage;

	/**
	 * @var bool|resource
	 */
	public $logFileHandle = false;

	public function __construct() {
		parent::__construct();

		$this->mDescription = 'Generate a JSON dump from entities in the repository.';

		$this->addOption( 'list-file', "A file containing one entity ID per line.", false, true );
		$this->addOption( 'entity-type', "Only dump this kind of entity, e.g. `item` or `property`.", false, true );
		$this->addOption( 'sharding-factor', "The number of shards (must be >= 1)", false, true );
		$this->addOption( 'shard', "The shard to output (must be less than the sharding-factor)", false, true );
		$this->addOption( 'batch-size', "The number of entities per processing batch", false, true );
		$this->addOption( 'output', "Output file (default is stdout). Will be overwritten.", false, true );
		$this->addOption( 'log', "Log file (default is stderr). Will be appended.", false, true );
		$this->addOption( 'quiet', "Disable progress reporting", false, false );
		$this->addOption( 'snippet', "Output a JSON snippet without square brackets at the start and end. Allows output to be combined more freely.", false, false );
	}

	public function initServices() {
		$entityFactory = WikibaseRepo::getDefaultInstance()->getEntityFactory();
		$serializerOptions = new SerializationOptions();

		$serializerFactory = new SerializerFactory(
			$serializerOptions,
			WikibaseRepo::getDefaultInstance()->getPropertyDataTypeLookup(),
			$entityFactory
		);

		$this->entitySerializer = new DispatchingEntitySerializer( $serializerFactory, $serializerOptions );

		//TODO: allow injection for unit tests
		$this->entityPerPage = WikibaseRepo::getDefaultInstance()->getStore()->newEntityPerPage();

		// Use an uncached EntityRevisionLookup here to avoid memory leaks
		$this->entityLookup = WikibaseRepo::getDefaultInstance()->getEntityLookup( 'uncached' );
	}

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
	 * @param $file
	 *
	 * @throws \MWException
	 */
	protected function openLogFile( $file ) {
		$this->closeLogFile();

		if ( $file === '-' ) {
			$file = 'php://stdout';
		}

		// wouldn't streams be nice...
		$this->logFileHandle = fopen( $file, 'a' );

		if ( !$this->logFileHandle ) {
			throw new \MWException( 'Failed to open log file: ' . $file );
		}
	}

	/**
	 * Closes any currently open file opened with openLogFile().
	 */
	protected function closeLogFile() {
		if ( $this->logFileHandle
			&& $this->logFileHandle !== STDERR
			&& $this->logFileHandle !== STDOUT ) {

			fclose( $this->logFileHandle );
		}

		$this->logFileHandle = false;
	}

	/**
	 * Do the actual work. All child classes will need to implement this
	 */
	public function execute() {
		$this->initServices();

		//TODO: more validation for options
		$entityType = $this->getOption( 'entity-type' );
		$shardingFactor = (int)$this->getOption( 'sharding-factor', 1 );
		$shard = (int)$this->getOption( 'shard', 0 );
		$batchSize = (int)$this->getOption( 'batch-size', 100 );
		$snippets = (bool)$this->getOption( 'snippet', false );

		//TODO: Allow injection of an OutputStream for logging
		$this->openLogFile( $this->getOption( 'log', 'php://stderr' ) );

		$outFile = $this->getOption( 'output', 'php://stdout' );

		if ( $outFile === '-' ) {
			$outFile = 'php://stdout';
		}

		$output = fopen( $outFile, 'w' ); //TODO: Allow injection of an OutputStream

		if ( !$output ) {
			throw new \MWException( 'Failed to open ' . $outFile . '!' );
		}

		if ( $this->hasOption( 'list-file' ) ) {
			$this->logMessage( "Dumping entities listed in " . $this->getOption( 'list-file' ) );
		}

		if ( $entityType ) {
			$this->logMessage( "Dumping entities of type $entityType" );
		}

		if ( $shardingFactor && $shard ) {
			$this->logMessage( "Dumping shard $shard/$shardingFactor" );
		}

		$dumper = new JsonDumpGenerator( $output, $this->entityLookup, $this->entitySerializer );

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
		$dumper->setUseSnippets( $snippets );

		$idStream = $this->makeIdStream( $entityType, $exceptionReporter );
		$dumper->generateDump( $idStream );

		if ( $idStream instanceof Disposable ) {
			// close stream / free resources
			$idStream->dispose();
		}

		$this->closeLogFile();
	}

	/**
	 * @param null|string $entityType
	 * @param ExceptionHandler $exceptionReporter
	 *
	 * @return EntityIdPager a stream of EntityId objects
	 */
	public function makeIdStream( $entityType = null, ExceptionHandler $exceptionReporter = null ) {
		$listFile = $this->getOption( 'list-file' );

		if ( $listFile !== null ) {
			$stream = $this->makeIdFileStream( $listFile, $exceptionReporter );
		} else {
			$stream = $this->makeIdQueryStream( $entityType );
		}

		return $stream;
	}

	/**
	 * @param $entityType
	 *
	 * @return EntityIdPager
	 */
	protected function makeIdQueryStream( $entityType ) {
		$stream = new EntityPerPageIdPager( $this->entityPerPage, $entityType );
		return $stream;
	}

	/**
	 * @param $listFile
	 * @param ExceptionHandler $exceptionReporter
	 *
	 * @throws MWException
	 * @return EntityIdPager
	 */
	protected function makeIdFileStream( $listFile, ExceptionHandler $exceptionReporter = null ) {
		$input = fopen( $listFile, 'r' );

		if ( !$input ) {
			throw new \MWException( "Failed to open ID file: $input" );
		}

		$stream = new EntityIdReader( new LineReader( $input ), new BasicEntityIdParser() );
		$stream->setExceptionHandler( $exceptionReporter );

		return $stream;
	}
}

$maintClass = 'Wikibase\DumpJson';
require_once( RUN_MAINTENANCE_IF_MAIN );
