<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\FederatedProperties;

use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Logger\LoggerFactory;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Repo\Api\EntitySearchHelper;

/**
 * @license GPL-2.0-or-later
 */
class ApiServiceFactory {

	/** @var HttpRequestFactory */
	private $httpRequestFactory;

	/** @var array */
	private $contentModelMappings;

	/** @var DataTypeDefinitions */
	private $dataTypeDefinitions;

	/**
	 * @var string
	 */
	private $federatedPropertiesSourceScriptUrl;

	/**
	 * @var string
	 */
	private $serverName;

	/**
	 * @var ApiEntityLookup|null
	 */
	private $apiEntityLookupInstance = null;

	/**
	 * @var ApiEntityNamespaceInfoLookup|null
	 */
	private $apiEntityNamespaceInfoLookup = null;

	/**
	 * @var EntitySourceDefinitions
	 */
	private $entitySourceDefinitions;

	public function __construct(
		HttpRequestFactory $httpRequestFactory,
		array $contentModelMappings,
		DataTypeDefinitions $dataTypeDefinitions,
		EntitySourceDefinitions $entitySourceDefinitions,
		string $federatedPropertiesSourceScriptUrl,
		string $serverName
	) {
		$this->httpRequestFactory = $httpRequestFactory;
		$this->contentModelMappings = $contentModelMappings;
		$this->dataTypeDefinitions = $dataTypeDefinitions;
		$this->entitySourceDefinitions = $entitySourceDefinitions;
		$this->federatedPropertiesSourceScriptUrl = $federatedPropertiesSourceScriptUrl;
		$this->serverName = $serverName;
	}

	private function getUrlForScriptFile( $scriptFile ): string {
		return $this->federatedPropertiesSourceScriptUrl . $scriptFile;
	}

	private function newFederatedPropertiesApiClient(): GenericActionApiClient {
		return new GenericActionApiClient(
			$this->httpRequestFactory,
			$this->getUrlForScriptFile( 'api.php' ),
			LoggerFactory::getInstance( 'Wikibase.FederatedProperties' ),
			$this->serverName
		);
	}

	public function newApiEntitySearchHelper(): EntitySearchHelper {
		$apiSource = $this->entitySourceDefinitions->getApiSourceForEntityType( Property::ENTITY_TYPE );

		return $apiSource === null ? new NullEntitySearchHelper() : new ApiEntitySearchHelper(
			$this->newFederatedPropertiesApiClient(),
			$this->dataTypeDefinitions->getTypeIds(),
			$apiSource
		);
	}

	/**
	 * Returns the singleton instance of ApiEntityNamespaceInfoLookup
	 * @return ApiEntityNamespaceInfoLookup
	 */
	private function getApiEntityNamespaceInfoLookup(): ApiEntityNamespaceInfoLookup {
		if ( $this->apiEntityNamespaceInfoLookup === null ) {
			$this->apiEntityNamespaceInfoLookup = new ApiEntityNamespaceInfoLookup(
				$this->newFederatedPropertiesApiClient(),
				$this->contentModelMappings
			);
		}
		return $this->apiEntityNamespaceInfoLookup;
	}

	public function newApiEntityTitleTextLookup(): ApiEntityTitleTextLookup {
		return new ApiEntityTitleTextLookup(
			$this->getApiEntityNamespaceInfoLookup()
		);
	}

	public function newApiEntityUrlLookup(): ApiEntityUrlLookup {
		return new ApiEntityUrlLookup(
			$this->newApiEntityTitleTextLookup(),
			$this->federatedPropertiesSourceScriptUrl
		);
	}

	public function newApiPropertyDataTypeLookup(): ApiPropertyDataTypeLookup {
		return new ApiPropertyDataTypeLookup(
			$this->getApiEntityLookup()
		);
	}

	public function newApiPrefetchingTermLookup(): ApiPrefetchingTermLookup {
		return new ApiPrefetchingTermLookup(
			$this->getApiEntityLookup()
		);
	}

	/**
	 * Returns the singleton instance of ApiEntityLookup
	 * @return ApiEntityLookup
	 */
	public function getApiEntityLookup(): ApiEntityLookup {
		if ( $this->apiEntityLookupInstance === null ) {
			$this->apiEntityLookupInstance = new ApiEntityLookup( $this->newFederatedPropertiesApiClient() );
		}
		return $this->apiEntityLookupInstance;
	}

	public function newApiEntityExistenceChecker(): ApiEntityExistenceChecker {
		return new ApiEntityExistenceChecker( $this->getApiEntityLookup() );
	}

}
