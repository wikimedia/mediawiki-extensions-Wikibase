<?php

namespace Wikibase\Dumpers;

use InvalidArgumentException;
use MWContentSerializationException;
use MWException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Reporting\ExceptionHandler;
use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\Lib\Reporting\NullMessageReporter;
use Wikibase\Lib\Reporting\RethrowingExceptionHandler;
use Wikibase\Lib\Serializers\Serializer;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Store\EntityIdPager;

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
	private $out;

	/**
	 * @var Serializer
	 */
	private $entitySerializer;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var int Total number of shards a request should be split into
	 */
	private $shardingFactor = 1;

	/**
	 * @var int Number of the requested shard
	 */
	private $shard = 0;

	/**
	 * @var bool
	 */
	private $useSnippets = false;

	/**
	 * @var string|null
	 */
	private $entityType = null;

	/**
	 * @var MessageReporter
	 */
	private $progressReporter;

	/**
	 * @var ExceptionHandler
	 */
	private $exceptionHandler;

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
	 * @throws InvalidArgumentException
	 */
	public function setBatchSize( $batchSize ) {
		if ( !is_int( $batchSize ) || $batchSize < 1 ) {
			throw new InvalidArgumentException( '$batchSize must be a positive integer.' );
		}

		$this->batchSize = $batchSize;
	}

	/**
	 * @param bool $useSnippets Whether to output valid json (false) or only comma separated entities
	 */
	public function setUseSnippets( $useSnippets ) {
		$this->useSnippets = (bool)$useSnippets;
	}

	/**
	 * @see setBatchSize()
	 *
	 * @return int
	 */
	public function getBatchSize() {
		return $this->batchSize;
	}

	/**
	 * @param MessageReporter $progressReporter
	 */
	public function setProgressReporter( MessageReporter $progressReporter ) {
		$this->progressReporter = $progressReporter;
	}

	/**
	 * @return MessageReporter
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
	 * for the first dump, setShardingFilter( 4, 1 ) for the second dump, etc.
	 *
	 * @param int $shardingFactor
	 * @param int $shard
	 *
	 * @throws InvalidArgumentException
	 */
	public function setShardingFilter( $shardingFactor, $shard ) {
		if ( !is_int( $shardingFactor ) || $shardingFactor < 1 ) {
			throw new InvalidArgumentException( '$shardingFactor must be a positive integer.' );
		}

		if ( !is_int( $shard ) || $shard < 0 ) {
			throw new InvalidArgumentException( '$shard must be a non-negative integer.' );
		}

		if ( $shard >= $shardingFactor ) {
			throw new InvalidArgumentException( '$shard must be smaller than $shardingFactor.' );
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

		if ( !$this->useSnippets ) {
			$this->writeToDump( "[\n" );
		}

		$dumpCount = 0;

		// Iterate over batches of IDs, maintaining the current position of the pager in the $position variable.
		while ( $ids = $idPager->fetchIds( $this->batchSize ) ) {
			$this->dumpEntities( $ids, $dumpCount );

			$this->progressReporter->reportMessage( 'Processed ' . $dumpCount . ' entities.' );
		};

		if ( !$this->useSnippets ) {
			$this->writeToDump( "\n]\n" );
		}
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param int &$dumpCount The number of entities already dumped (will be updated).
	 */
	private function dumpEntities( array $entityIds, &$dumpCount ) {
		foreach ( $entityIds as $entityId ) {
			if ( !$this->idMatchesFilters( $entityId ) ) {
				continue;
			}

			try {
				$json = $this->generateJsonForEntityId( $entityId );

				if ( $dumpCount > 0 ) {
					$this->writeToDump( ",\n" );
				}

				$this->writeToDump( $json );
				$dumpCount++;
			} catch ( StorageException $ex ) {
				$this->exceptionHandler->handleException( $ex, 'failed-to-dump', 'Failed to dump '. $entityId );
			}
		}
	}

	private function generateJsonForEntityId( EntityId $entityId ) {
		try {
			$entity = $this->entityLookup->getEntity( $entityId );

			if ( !$entity ) {
				throw new StorageException( 'Entity not found: ' . $entityId->getSerialization() );
			}
		} catch( MWContentSerializationException $ex ) {
			throw new StorageException( 'Deserialization error for '
				. $entityId->getSerialization() );
		}

		$data = $this->entitySerializer->getSerialized( $entity );
		$json = $this->encode( $data );

		return $json;
	}

	private function idMatchesFilters( EntityId $entityId ) {
		return $this->idMatchesShard( $entityId )
			&& $this->idMatchesType( $entityId );
	}

	private function idMatchesShard( EntityId $entityId ) {
		// Shorten out
		if ( $this->shardingFactor === 1 ) {
			return true;
		}

		$hash = sha1( $entityId->getSerialization() );
		$shard = (int)hexdec( substr( $hash, 0, 8 ) ); // 4 bytes of the hash
		$shard = abs( $shard ); // avoid negative numbers on 32 bit systems
		$shard %= $this->shardingFactor; // modulo number of shards

		return $shard === $this->shard;
	}

	private function idMatchesType( EntityId $entityId ) {
		return $this->entityType === null
			|| ( $entityId->getEntityType() === $this->entityType );
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
	 * @return int
	 *
	 * @see setJsonFlags
	 */
	public function getJsonFlags() {
		return $this->jsonFlags;
	}

}
