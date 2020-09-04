<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\FederatedProperties;

use Exception;
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
	private static $apiEntityLookupInstance = null;

	/**
	 * @var ApiEntityNamespaceInfoLookup|null
	 */
	private static $apiEntityNamespaceInfoLookup = null;

	public static function resetClassStatics() {
		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			throw new Exception( 'Cannot reset ApiServiceFactory class statics outside of tests.' );
		}
		self::$apiEntityLookupInstance = null;
		self::$apiEntityNamespaceInfoLookup = null;
	}

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
			WikibaseRepo::getDefaultInstance()->getDataTypeDefinitions()->getTypeIds()
		);
	}

	/**
	 * Returns the singleton instance of ApiEntityNamespaceInfoLookup
	 * @return ApiEntityNamespaceInfoLookup
	 */
	private function getApiEntityNamespaceInfoLookup(): ApiEntityNamespaceInfoLookup {
		if ( self::$apiEntityNamespaceInfoLookup === null ) {
			self::$apiEntityNamespaceInfoLookup = new ApiEntityNamespaceInfoLookup(
				$this->newFederatedPropertiesApiClient(),
				WikibaseRepo::getDefaultInstance()->getContentModelMappings()
			);
		}
		return self::$apiEntityNamespaceInfoLookup;
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
		if ( self::$apiEntityLookupInstance === null ) {
			self::$apiEntityLookupInstance = new ApiEntityLookup( $this->newFederatedPropertiesApiClient() );
		}
		return self::$apiEntityLookupInstance;
	}

	public function newApiEntityExistenceChecker(): ApiEntityExistenceChecker {
		return new ApiEntityExistenceChecker( $this->getApiEntityLookup() );
	}

}
