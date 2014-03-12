<?php

namespace Wikibase\IO;

use Disposable;
use ExceptionHandler;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\EntityIdPager;

/**
 * EntityIdReader reads entity IDs from a file, one per line.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class EntityIdReader implements EntityIdPager, Disposable {

	/**
	 * @var LineReader
	 */
	protected $reader;

	/**
	 * @var ExceptionHandler
	 */
	protected $exceptionHandler;

	/**
	 * @var EntityId|null
	 */
	protected $current = null;

	/**
	 * @param LineReader $reader
	 * @param \Wikibase\DataModel\Entity\EntityIdParser $parser
	 */
	public function __construct( LineReader $reader, EntityIdParser $parser ) {
		$this->reader = $reader;
		$this->parser = $parser;

		$this->exceptionHandler = new \RethrowingExceptionHandler();
	}

	/**
	 * @param \ExceptionHandler $exceptionHandler
	 */
	public function setExceptionHandler( $exceptionHandler ) {
		$this->exceptionHandler = $exceptionHandler;
	}

	/**
	 * @return \ExceptionHandler
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
		while ( $id === null && $this->reader->valid() ) {
			$line = trim( $this->reader->current() );
			$this->reader->next();

			if ( $line === '' ) {
				continue;
			}

			$id = $this->lineToId( $line );
		};

		return $id;
	}

	/**
	 * Lists the IDs of entities of the given type.
	 *
	 * This supports paging via the $offset parameter: to get the next batch of IDs,
	 * call listEntities() again with the $offset provided by the previous call
	 * to listEntities().
	 *
	 * @since 0.5
	 *
	 * @param string|null $entityType The type of entity to return, or null for any type.
	 * @param int $limit The maximum number of IDs to return.
	 * @param mixed &$offset A position marker representing the position to start listing from;
	 * Will be updated to represent the position for the next batch of IDs.
	 * Callers should make no assumptions about the type or content of $offset.
	 * Use null (the default) to start with the first ID.
	 *
	 * @return \Wikibase\EntityId[] A list of EntityIds matching the given parameters. Will
	 * be empty if there are no more entities to list from the given offset.
	 */
	public function listEntities( $entityType, $limit, &$offset = null ) {
		if ( $offset === null ) {
			$offset = $this->reader->getPosition();
		}

		$this->reader->setPosition( $offset );

		$ids = array();
		while ( $limit > 0 ) {
			$id = $this->next();

			if ( $id === null ) {
				break;
			}

			if ( $entityType !== null && $id->getEntityType() !== $entityType ) {
				continue;
			}

			$ids[] = $id;
			$limit--;
		}

		$offset = $this->reader->getPosition();
		return $ids;
	}
}