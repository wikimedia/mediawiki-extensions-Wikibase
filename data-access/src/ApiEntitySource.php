<?php

namespace Wikibase\DataAccess;

use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ApiEntitySource implements EntitySource {

	public const TYPE = 'api';

	/**
	 * @var string
	 */
	private $sourceName;

	/**
	 * @var string[]
	 */
	private $entityTypes;

	/**
	 * @var string
	 */
	private $conceptBaseUri;

	/** @var string */
	private $rdfNodeNamespacePrefix;

	/** @var string */
	private $rdfPredicateNamespacePrefix;

	/**
	 * @var string
	 */
	private $interwikiPrefix;

	/**
	 * @var string|null
	 */
	private $repoApiUrl;

	/**
	 * @param string $name Unique name for the source for a given configuration / site, used for indexing the sources internally.
	 *        This does not have to be a wikiname, sitename or dbname, it can for example just be 'properties'.
	 * @param array $entityTypes Array of entityTypes e.g [ 'property' ]
	 * @param string $conceptBaseUri
	 * @param string $rdfNodeNamespacePrefix
	 * @param string $rdfPredicateNamespacePrefix
	 * @param string $interwikiPrefix
	 * @param string|null $repoApiUrl Optional API URL for the remote repository (e.g. 'https://www.wikidata.org/w/api.php')
	 */
	public function __construct(
		$name,
		array $entityTypes,
		$conceptBaseUri,
		$rdfNodeNamespacePrefix,
		$rdfPredicateNamespacePrefix,
		$interwikiPrefix,
		?string $repoApiUrl = null
	) {
		Assert::parameterType( 'string', $name, '$name' );
		Assert::parameterType( 'string', $conceptBaseUri, '$conceptBaseUri' );
		Assert::parameterType( 'string', $rdfNodeNamespacePrefix, '$rdfNodeNamespacePrefix' );
		Assert::parameterType( 'string', $rdfPredicateNamespacePrefix, '$rdfPredicateNamespacePrefix' );
		Assert::parameterType( 'string', $interwikiPrefix, '$interwikiPrefix' );
		Assert::parameter( count( $entityTypes ) > 0, 'Entity types', 'EntityType must be defined' );
		Assert::parameterElementType( 'string', $entityTypes, 'Entity type' );

		$this->sourceName = $name;
		$this->entityTypes = $entityTypes;
		$this->conceptBaseUri = $conceptBaseUri;
		$this->rdfNodeNamespacePrefix = $rdfNodeNamespacePrefix;
		$this->rdfPredicateNamespacePrefix = $rdfPredicateNamespacePrefix;
		$this->interwikiPrefix = $interwikiPrefix;
		$this->repoApiUrl = $repoApiUrl;
	}

	public function getSourceName(): string {
		return $this->sourceName;
	}

	public function getEntityTypes(): array {
		return $this->entityTypes;
	}

	public function getConceptBaseUri(): string {
		return $this->conceptBaseUri;
	}

	public function getRdfNodeNamespacePrefix(): string {
		return $this->rdfNodeNamespacePrefix;
	}

	public function getRdfPredicateNamespacePrefix(): string {
		return $this->rdfPredicateNamespacePrefix;
	}

	public function getInterwikiPrefix(): string {
		return $this->interwikiPrefix;
	}

	public function getType(): string {
		return self::TYPE;
	}

	/**
	 * Get the API URL for this remote source.
	 *
	 * @return string|null The API URL (e.g. 'https://www.wikidata.org/w/api.php'), or null if not configured
	 */
	public function getRepoApiUrl(): ?string {
		return $this->repoApiUrl;
	}
}
