<?php

namespace Wikibase\Lib\Store\Sql;

use Psr\Log\LoggerInterface;
use DBAccessBase;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataModel\Assert\RepositoryNameAssert;
use Wikibase\Lib\Store\EntityTermStoreWriter;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @license GPL-2.0-or-later
 */
class PropertyTermStoreWriter extends DBAccessBase implements EntityTermStoreWriter {

	/**
	 * @var string
	 */
	private $repositoryName;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var EntitySource
	 */
	private $entitySource;

	/**
	 * @var DataAccessSettings
	 */
	private $dataAccessSettings;

	/**
	 * @param EntitySource $entitySource
	 * @param DataAccessSettings $dataAccessSettings
	 * @param LoggerInterface $logger
	 * @param string|bool $wikiDb
	 * @param string $repositoryName
	 */
	public function __construct(
		EntitySource $entitySource,
		DataAccessSettings $dataAccessSettings,
		LoggerInterface $logger,
		$wikiDb = false,
		$repositoryName = ''
	) {
		RepositoryNameAssert::assertParameterIsValidRepositoryName( $repositoryName, '$repositoryName' );

		$databaseName = $dataAccessSettings->useEntitySourceBasedFederation()
					  ? $entitySource->getDatabaseName()
					  : $wikiDb;

		parent::__construct( $databaseName );

		$this->repositoryName = $repositoryName;
		$this->entitySource = $entitySource;
		$this->dataAccessSettings = $dataAccessSettings;
		$this->logger = $logger;
	}

	/**
	 * Saves the terms of the provided property in the term store.
	 *
	 * @param Property $property
	 */
	public function saveTerms( EntityDocument $property ) {
		Assert::parameterType( Property::class, $property, '$property' );
	}

	/**
	 * Deletes the terms of the provided property from the term store.
	 *
	 * @param PropertyId $propertyId
	 */
	public function deleteTerms( EntityId $propertyId ) {
		Assert::parameterType( PropertyId::class, $propertyId, '$propertyId' );
	}
}
