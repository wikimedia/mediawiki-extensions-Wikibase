<?php

namespace Wikibase;

use Disposable;
use ExceptionHandler;
use Iterator;
use Maintenance;
use Traversable;
use ValueFormatters\FormatterOptions;
use Wikibase\Dumpers\JsonDumpGenerator;
use Wikibase\IO\EntityIdReader;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\Serializers\EntitySerializationOptions;
use Wikibase\Lib\Serializers\EntitySerializer;
use Wikibase\Lib\Serializers\Serializer;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for generating a JSON dump of entities in the repository.
 *
 * @since 0.5
 *
 * @ingroup WikibaseRepo
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

	public $quiet = true;

	public function __construct() {
		parent::__construct();

		$this->mDescription = 'Generate a JSON dump from entities in the repository.';

		$this->addOption( 'list-file', "A file containing one entity ID per line.", false, true );
		$this->addOption( 'entity-type', "Only dump this kind of entitiy, e.g. `item` or `property`.", false, true );
		$this->addOption( 'sharding-factor', "The number of shards (must be >= 1)", false, true );
		$this->addOption( 'shard', "A the shard to output (must be lett than the sharding-factor) ", false, true );
		$this->addOption( 'output', "Output file (default is stdout) ", false, true );
		$this->addOption( 'quiet', "Disable progress reporting", false, false );
	}

	public function initServices() {
		$serializerOptions = new EntitySerializationOptions();
		$this->entitySerializer = new EntitySerializer( $serializerOptions );

		//TODO: allow injection for unit tests
		$this->entityPerPage = new EntityPerPageTable();
		$this->entityLookup = WikibaseRepo::getDefaultInstance()->getEntityLookup();
	}

	/**
	 * Outputs a message vis the output() method.
	 *
	 * @see MessageReporter::reportMessage()
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {
			$this->output( "$message\n" );
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

		$outFile = $this->getOption( 'output', 'php://stdout' );

		if ( $outFile === '-' ) {
			$outFile = 'php://stdout';
		}

		$output = fopen( $outFile, 'wa' ); //TODO: Allow injection of an OutputStream

		if ( !$output ) {
			throw new \MWException( 'Failed to open ' . $outFile . '!' );
		}

		if ( $entityType ) {
			$this->reportMessage( "Dumping entities of type $entityType" );
		}

		if ( $shardingFactor && $shard ) {
			$this->reportMessage( "Dumping shard $shard/$shardingFactor" );
		}

		$dumper = new JsonDumpGenerator( $output, $this->entityLookup, $this->entitySerializer );

		if ( $outFile !== 'php://stdout' && $outFile !== 'php://output' ) {
			$progressReporter = new \ObservableMessageReporter();
			$progressReporter->registerReporterCallback( array( $this, 'reportMessage' ) );

			$dumper->setProgressReporter( $progressReporter );
		} else {
			$progressReporter = new \NullMessageReporter();
			$this->mQuiet = true; // suppress in-band output
		}

		$exceptionReporter = new \ReportingExceptionHandler( $progressReporter );
		$dumper->setExceptionHandler( $exceptionReporter );

		//NOTE: we filter for $entityType twice: filtering in the DB is efficient,
		//      but filtering in the dumper is needed when working from a list file.
		$dumper->setShardingFilter( $shardingFactor, $shard );
		$dumper->setEntityTypeFilter( $entityType );

		$idStream = $this->makeIdStream( $entityType, $exceptionReporter );
		$dumper->generateDump( $idStream );

		if ( $idStream instanceof Disposable ) {
			// close stream / free resources
			$idStream->dispose();
		}
	}

	/**
	 * @param string|null $entityType
	 * @param \ExceptionHandler $exceptionReporter
	 *
	 * @return Iterator a stream of EntityId objects
	 */
	public function makeIdStream( $entityType = null, ExceptionHandler $exceptionReporter = null ) {
		$listFile = $this->getOption( 'list-file' );

		if ( $listFile !== null ) {
			$stream = $this->makeIdFileStream( $listFile, $exceptionReporter );
		} else {
			$stream = $this->entityPerPage->getEntities( $entityType );
		}

		return $stream;
	}

	/**
	 * @param $listFile
	 * @param \ExceptionHandler $exceptionReporter
	 *
	 * @throws \MWException
	 * @return Traversable
	 */
	protected function makeIdFileStream( $listFile, ExceptionHandler $exceptionReporter = null ) {
		$input = fopen( $listFile, 'r' );

		if ( !$input ) {
			throw new \MWException( "Failed to open ID file: $input" );
		}

		$stream = new EntityIdReader( $input );
		$stream->setExceptionHandler( $exceptionReporter );

		return $stream;
	}

}

$maintClass = 'Wikibase\DumpJson';
require_once( RUN_MAINTENANCE_IF_MAIN );
