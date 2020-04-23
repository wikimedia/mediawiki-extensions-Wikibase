<?php

namespace Wikibase\Repo\IO;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\EntityId\EntityIdPager;
use Wikibase\Lib\Reporting\ExceptionHandler;
use Wikibase\Lib\Reporting\RethrowingExceptionHandler;

/**
 * EntityIdReader reads entity IDs from a file, one per line.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityIdReader implements EntityIdPager {

	/**
	 * @var LineReader
	 */
	private $reader;

	/**
	 * @var EntityIdParser
	 */
	private $parser;

	/**
	 * @var string|null
	 */
	private $entityType;

	/**
	 * @var ExceptionHandler
	 */
	private $exceptionHandler;

	/**
	 * @param LineReader $reader
	 * @param EntityIdParser $parser
	 * @param null|string $entityType The desired entity type, or null for any type.
	 */
	public function __construct( LineReader $reader, EntityIdParser $parser, $entityType = null ) {
		$this->reader = $reader;
		$this->parser = $parser;
		$this->entityType = $entityType;

		$this->exceptionHandler = new RethrowingExceptionHandler();
	}

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
	 * @param string $line
	 * @return EntityId|null
	 */
	protected function lineToId( $line ) {
		$line = trim( $line );

		try {
			$id = $this->parser->parse( $line );
		} catch ( EntityIdParsingException $ex ) {
			$this->exceptionHandler->handleException( $ex, 'bad-entity-id', "Failed to parse Entity ID $line" );
			$id = null;
		}

		return $id;
	}

	/**
	 * Closes the underlying input stream
	 */
	public function dispose() {
		$this->reader->dispose();
	}

	/**
	 * Returns the next ID (or null if there are no more ids).
	 *
	 * @return EntityId|null
	 */
	protected function next() {
		$id = null;

		while ( $id === null ) {
			$this->reader->next();

			if ( !$this->reader->valid() ) {
				break;
			}

			$line = trim( $this->reader->current() );

			if ( $line === '' ) {
				continue;
			}

			$id = $this->lineToId( $line );

			if ( !$id ) {
				continue;
			}

			if ( $this->entityType !== null && $id->getEntityType() !== $this->entityType ) {
				$id = null;
				continue;
			}
		}

		return $id;
	}

	/**
	 * Fetches the next batch of IDs. Calling this has the side effect of advancing the
	 * internal state of the page, typically implemented by some underlying resource
	 * such as a file pointer or a database connection.
	 *
	 * @note After some finite number of calls, this method should eventually return
	 * an empty list of IDs, indicating that no more IDs are available.
	 *
	 * @param int $limit The maximum number of IDs to return.
	 *
	 * @return EntityId[] A list of EntityIds matching the given parameters. Will
	 * be empty if there are no more entities to list from the given offset.
	 */
	public function fetchIds( $limit ) {
		$ids = [];
		while ( $limit > 0 ) {
			$id = $this->next();

			if ( $id === null ) {
				break;
			}

			$ids[] = $id;
			$limit--;
		}

		return $ids;
	}

}
