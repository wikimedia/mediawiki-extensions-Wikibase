<?php

namespace Wikibase\Lib\Store\Sql;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Store\EntityInfoBuilder;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;

/**
 * A factory for SqlEntityInfoBuilder instances.
 *
 * @see EntityInfoBuilder
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class SqlEntityInfoBuilderFactory implements EntityInfoBuilderFactory {

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var bool
	 */
	private $wiki;

	/**
	 * @param EntityIdParser $idParser
	 * @param string|bool $wiki The wiki's database to connect to.
	 *        Must be a value LBFactory understands. Defaults to false, which is the local wiki.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( EntityIdParser $idParser, $wiki = false ) {
		if ( !is_string( $wiki ) && $wiki !== false ) {
			throw new InvalidArgumentException( '$wiki must be a string or false.' );
		}

		$this->idParser = $idParser;
		$this->wiki = $wiki;
	}

	/**
	 * @see EntityInfoBuilderFactory::newEntityInfoBuilder
	 *
	 * @param EntityId[] $ids
	 *
	 * @return EntityInfoBuilder
	 */
	public function newEntityInfoBuilder( array $ids ) {
		return new SqlEntityInfoBuilder( $ids, $this->idParser, $this->wiki );
	}
}
