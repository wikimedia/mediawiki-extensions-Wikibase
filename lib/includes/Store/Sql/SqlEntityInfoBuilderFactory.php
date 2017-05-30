<?php

namespace Wikibase\Lib\Store\Sql;

use InvalidArgumentException;
use Wikibase\DataModel\Assert\RepositoryNameAssert;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\Store\EntityInfoBuilder;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityNamespaceLookup;

/**
 * A factory for SqlEntityInfoBuilder instances.
 *
 * @see EntityInfoBuilder
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class SqlEntityInfoBuilderFactory implements EntityInfoBuilderFactory {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var EntityIdComposer
	 */
	private $entityIdComposer;

	/**
	 * @var EntityNamespaceLookup
	 */
	private $entityNamespaceLookup;

	/**
	 * @var string|bool
	 */
	private $wiki;

	/**
	 * @var string
	 */
	private $repositoryName;

	/**
	 * @var bool
	 */
	private $readFullEntityIdColumn = false;

	/**
	 * @param EntityIdParser $entityIdParser
	 * @param EntityIdComposer $entityIdComposer
	 * @param EntityNamespaceLookup $entityNamespaceLookup
	 * @param string|bool $wiki The wiki's database to connect to.
	 *        Must be a value LBFactory understands. Defaults to false, which is the local wiki.
	 * @param string $repositoryName The name of the repository (use an empty string for the local repository)
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		EntityIdParser $entityIdParser,
		EntityIdComposer $entityIdComposer,
		EntityNamespaceLookup $entityNamespaceLookup,
		$wiki = false,
		$repositoryName = ''
	) {
		if ( !is_string( $wiki ) && $wiki !== false ) {
			throw new InvalidArgumentException( '$wiki must be a string or false.' );
		}
		RepositoryNameAssert::assertParameterIsValidRepositoryName( $repositoryName, '$repositoryName' );

		$this->entityIdParser = $entityIdParser;
		$this->entityIdComposer = $entityIdComposer;
		$this->wiki = $wiki;
		$this->repositoryName = $repositoryName;
		$this->entityNamespaceLookup = $entityNamespaceLookup;
	}

	/**
	 * @see EntityInfoBuilderFactory::newEntityInfoBuilder
	 *
	 * @param EntityId[] $entityIds
	 *
	 * @return EntityInfoBuilder
	 */
	public function newEntityInfoBuilder( array $entityIds ) {
		$sqlEntityInfoBuilder = new SqlEntityInfoBuilder(
			$this->entityIdParser,
			$this->entityIdComposer,
			$this->entityNamespaceLookup,
			$entityIds,
			$this->wiki,
			$this->repositoryName
		);

		$sqlEntityInfoBuilder->setReadFullEntityIdColumn( $this->readFullEntityIdColumn );

		return $sqlEntityInfoBuilder;
	}

	/**
	 * @param bool $readFullEntityIdColumn
	 */
	public function setReadFullEntityIdColumn( $readFullEntityIdColumn ) {
		$this->readFullEntityIdColumn = $readFullEntityIdColumn;
	}

}
