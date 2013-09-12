<?php

namespace Wikibase;
use Disposable;
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
class DumpJasonInfo extends Maintenance {

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

	public function __construct() {
		parent::__construct();

		$this->mDescription = 'Generate a JSON dump from entities in the repository.';

		//TODO: filter by entity type
		//TODO: shard by id congruence class ( id % n == m )
		//$this->addOption( 'rebuild-all', "Update property info for all properties (per default, only missing entries are created)" );
		//$this->addOption( 'start-row', "The ID of the first row to update (useful for continuing aborted runs)", false, true );

		$this->addOption( 'list-file', "A file containing one entity ID per line", false, true );
	}

	public function finalSetup() {
		parent::finalSetup();

		$serializerOptions = new EntitySerializationOptions( new EntityIdFormatter( new FormatterOptions() ) );
		$this->entitySerializer = new EntitySerializer( $serializerOptions );

		//TODO: allow injection for unit tests
		$this->entityPerPage = new EntityPerPageTable();
		$this->entityLookup = WikibaseRepo::getDefaultInstance()->getEntityLookup();
	}

	/**
	 * Outputs a message vis the output() method.
	 *
	 * @since 0.4
	 *
	 * @param $msg
	 */
	public function report( $msg ) {
		$this->output( "$msg\n" );
	}

	/**
	 * Do the actual work. All child classes will need to implement this
	 */
	public function execute() {
		$output = fopen( 'php://stdout', 'wa' ); //TODO: Allow injection of an OutputStream
		$dumper = new JsonDumpGenerator( $output, $this->entityLookup, $this->entitySerializer );

		$idStream = $this->makeIdStream();
		$dumper->generateDump( $idStream );

		if ( $idStream instanceof Disposable ) {
			// close stream / free resources
			$idStream->dispose();
		}
	}

	/**
	 * @return Iterator a stream of EntityId objects
	 */
	public function makeIdStream() {
		$listFile = $this->getOption( 'list-file' );

		if ( $listFile !== null ) {
			//TODO: allow filtering by entity type, id congruence class ( id % n == m ), etc.
			$stream = $this->makeIdFileStream( $listFile );
		} else {
			$stream = $this->entityPerPage->getEntities();
		}

		return $stream;
	}

	/**
	 * @param $listFile
	 *
	 * @return Traversable
	 * @throws \MWException
	 */
	protected function makeIdFileStream( $listFile ) {
		$input = fopen( $listFile, 'r' );

		if ( !$input ) {
			throw new \MWException( "Failed to open ID file: $input" );
		}

		$stream = new EntityIdReader( $input );
		return $stream;
	}
}

$maintClass = 'Wikibase\DumpJasonInfo';
require_once( RUN_MAINTENANCE_IF_MAIN );
