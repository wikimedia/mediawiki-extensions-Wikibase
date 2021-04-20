<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\FederatedProperties;

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 */
class ApiServiceFactory {

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
	 * ApiServiceFactory constructor.
	 * @param string $federatedPropertiesSourceScriptUrl
	 * @param string $serverName
	 */
	public function __construct(
		string $federatedPropertiesSourceScriptUrl,
		string $serverName
	) {
		$this->federatedPropertiesSourceScriptUrl = $federatedPropertiesSourceScriptUrl;
		$this->serverName = $serverName;
	}

	private function getUrlForScriptFile( $scriptFile ): string {
		return $this->federatedPropertiesSourceScriptUrl . $scriptFile;
	}

	private function newFederatedPropertiesApiClient(): GenericActionApiClient {
		return new GenericActionApiClient(
			MediaWikiServices::getInstance()->getHttpRequestFactory(),
			$this->getUrlForScriptFile( 'api.php' ),
			LoggerFactory::getInstance( 'Wikibase.FederatedProperties' ),
			$this->serverName
		);
	}

	public function newApiEntitySearchHelper(): ApiEntitySearchHelper {
		return new ApiEntitySearchHelper(
			$this->newFederatedPropertiesApiClient(),
			WikibaseRepo::getDataTypeDefinitions()->getTypeIds()
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
				WikibaseRepo::getContentModelMappings()
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
