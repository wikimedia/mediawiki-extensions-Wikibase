<?php

namespace Wikibase\Dumpers;

use ExceptionHandler;
use InvalidArgumentException;
use MessageReporter;
use MWException;
use NullMessageReporter;
use RethrowingExceptionHandler;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityIdPager;
use Wikibase\EntityLookup;
use Wikibase\Lib\Serializers\Serializer;
use Wikibase\StorageException;

/**
 * JsonDumpGenerator generates an JSON dump of a given set of entities.
 *
 * @since 0.5
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class JsonDumpGenerator {

	/**
	 * @var int flags to use with json_encode as a bit field, see PHP's JSON_XXX constants.
	 */
	public $jsonFlags = 0;

	/**
	 * @var int The max number of entities to process in a single batch.
	 * Also controls the interval for progress reports.
	 */
	public $batchSize = 100;

	/**
	 * @var resource File handle for output
	 */
	protected $out;

	/**
	 * @var Serializer
	 */
	protected $entitySerializer;

	/**
	 * @var EntityLookup
	 */
	protected $entityLookup;

	/**
	 * @var int
	 */
	protected $shardingFactor = 1;

	/*
	 * @var int
	 */
	protected $shard = 0;

	/*
	 * @var string|null
	 */
	protected $entityType = null;

	/**
	 * @var MessageReporter
	 */
	protected $progressReporter;

	/**
	 * @var ExceptionHandler
	 */
	protected $exceptionHandler;

	/**
	 * @param resource $out
	 * @param EntityLookup $lookup
	 * @param Serializer $entitySerializer
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $out, EntityLookup $lookup, Serializer $entitySerializer ) {
		if ( !is_resource( $out ) ) {
			throw new InvalidArgumentException( '$out must be a file handle!' );
		}

		$this->out = $out;
		$this->entitySerializer = $entitySerializer;
		$this->entityLookup = $lookup;

		$this->progressReporter = new NullMessageReporter();
		$this->exceptionHandler = new RethrowingExceptionHandler();
	}

	/**
	 * Sets the batch size for processing. The batch size is used as the limit
	 * when listing IDs via the EntityIdPager::getNextBatchOfIds() method, and
	 * also controls the interval of progress reports.
	 *
	 * @param int $batchSize
	 *
	 * @throws \InvalidArgumentException
	 */
	public function setBatchSize( $batchSize ) {
		if ( !is_int( $batchSize ) || $batchSize < 1 ) {
			throw new InvalidArgumentException( '$batchSize must be a positive integer' );
		}

		$this->batchSize = $batchSize;
	}

	/**
	 * Returns the batch size.
	 *
	 * @see setBatchSize()
	 *
	 * @return int
	 */
	public function getBatchSize() {
		return $this->batchSize;
	}

	/**
	 * @param \MessageReporter $progressReporter
	 */
	public function setProgressReporter( MessageReporter $progressReporter ) {
		$this->progressReporter = $progressReporter;
	}

	/**
	 * @return \MessageReporter
	 */
	public function getProgressReporter() {
		return $this->progressReporter;
	}

	/**
	 * @param ExceptionHandler $exceptionHandler
	 */
	public function setExceptionHandler( ExceptionHandler $exceptionHandler ) {
		$this->exceptionHandler = $exceptionHandler;
	}

	/**
	 * @return ExceptionHandler
	 */
	public function getExceptionHandler() {
		return $this->exceptionHandler;
	}

	/**
	 * Set the sharding factor and desired shard.
	 * For instance, to generate four dumps in parallel, use setShardingFilter( 4, 0 )
	 * for the first dump, setShardingFilter( 4, 1 ) for the second dump,
	 * etc.
	 *
	 * @param int $shardingFactor
	 * @param int $shard
	 *
	 * @throws InvalidArgumentException
	 */
	public function setShardingFilter( $shardingFactor, $shard ) {
		if ( !is_int( $shardingFactor ) || $shardingFactor < 1 ) {
			throw new InvalidArgumentException( '$shardingFactor must be an integer > 0' );
		}

		if ( !is_int( $shard ) || $shard < 0 ) {
			throw new InvalidArgumentException( '$shard must be an integer >= 0' );
		}

		if ( $shard >= $shardingFactor ) {
			throw new InvalidArgumentException( '$shard must be smaller than $shardingFactor' );
		}

		$this->shardingFactor = $shardingFactor;
		$this->shard = $shard;
	}

	/**
	 * Set the entity type to be included in the output.
	 *
	 * @param string|null $type The desired type (use null for any type).
	 *
	 * @throws InvalidArgumentException
	 */
	public function setEntityTypeFilter( $type ) {
		$this->entityType = $type;
	}

	/**
	 * Generates a JSON dump, writing to the file handle provided to the constructor.
	 *
	 * @param EntityIdPager $idPager an Iterator that returns EntityId instances
	 */
	public function generateDump( EntityIdPager $idPager ) {

		$json = "[\n"; //TODO: make optional
		$this->writeToDump( $json );

		$dumpCount = 0;

		// Iterate over badges of IDs, maintaining the current position of the pager in the $position variable.
		while ( $ids = $idPager->getNextBatchOfIds( $this->entityType, $this->batchSize, $position ) ) {
			$this->dumpEntities( $ids, $dumpCount );

			$this->progressReporter->reportMessage( 'Processed ' . $dumpCount . ' entities.' );
		};

		$json = "\n]\n"; //TODO: make optional
		$this->writeToDump( $json );
	}

	/**
	 * @param array $ids
	 * @param int &$dumpCount The number of entities already dumped (will be updated).
	 */
	protected function dumpEntities( array $ids, &$dumpCount ) {
		/* @var EntityId $id */
		foreach ( $ids as $id ) {
			if ( !$this->idMatchesFilters( $id ) ) {
				continue;
			}

			try {
				$entity = $this->entityLookup->getEntity( $id );

				if ( !$entity ) {
					throw new StorageException( 'Entity not found: ' . $id->getSerialization() );
				}

				$data = $this->entitySerializer->getSerialized( $entity );
				$json = $this->encode( $data );

				if ( $dumpCount > 0 ) {
					$this->writeToDump( ",\n" );
				}

				$this->writeToDump( $json );
				$dumpCount++;
			} catch ( StorageException $ex ) {
				$this->exceptionHandler->handleException( $ex, 'failed-to-dump', 'Failed to dump '. $id );
			}
		}
	}

	private function idMatchesFilters( EntityId $id ) {
		return $this->idMatchesShard( $id )
			&& $this->idMatchesType( $id );
	}

	private function idMatchesShard( EntityId $id ) {
		$hash = sha1( $id->getSerialization() );
		$n = (int)hexdec( substr( $hash, 0, 8 ) ); // 4 bytes of the hash
		if( $n < 0 ) {
			$n = -$n;
		}
		$n = $n % $this->shardingFactor; // modulo number of shards
		return $n === $this->shard;
	}

	private function idMatchesType( EntityId $id ) {
		return $this->entityType === null
			|| ( $id->getEntityType() === $this->entityType );
	}

	/**
	 * Encodes the given data as JSON
	 *
	 * @param $data
	 *
	 * @return string
	 * @throws MWException
	 */
	public function encode( $data ) {
		$json = json_encode( $data, $this->jsonFlags );

		if ( $json === false ) {
			throw new StorageException( 'Failed to encode data structure.' );
		}

		return $json;
	}

	/**
	 * Writers the given string to the output provided to the constructor.
	 *
	 * @param $json
	 */
	private function writeToDump( $json ) {
		//TODO: use output stream object
		fwrite( $this->out, $json );
	}

	/**
	 * Flags to use with json_encode as a bit field, see PHP's JSON_XXX constants.
	 *
	 * @param int $jsonFlags
	 */
	public function setJsonFlags( $jsonFlags ) {
		$this->jsonFlags = $jsonFlags;
	}

	/**
	 * Flags to use with json_encode as a bit field, see PHP's JSON_XXX constants.
	 *
	 * @return int
	 */
	public function getJsonFlags() {
		return $this->jsonFlags;
	}
}
