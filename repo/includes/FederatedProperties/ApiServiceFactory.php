<?php

namespace Wikibase\Repo\FederatedProperties;

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

	public function __construct(
		string $federatedPropertiesSourceScriptUrl
	) {
		$this->federatedPropertiesSourceScriptUrl = $federatedPropertiesSourceScriptUrl;
	}

	private function getUrlForScriptFile( $scriptFile ): string {
		return $this->federatedPropertiesSourceScriptUrl . $scriptFile;
	}

	private function newFederatedPropertiesApiClient(): GenericActionApiClient {
		return new GenericActionApiClient(
			MediaWikiServices::getInstance()->getHttpRequestFactory(),
			$this->getUrlForScriptFile( 'api.php' )
		);
	}

	public function newApiEntitySearchHelper(): ApiEntitySearchHelper {
		return new ApiEntitySearchHelper(
			$this->newFederatedPropertiesApiClient()
		);
	}

	private function newApiEntityNamespaceInfoLookup(): ApiEntityNamespaceInfoLookup {
		return new ApiEntityNamespaceInfoLookup(
			$this->newFederatedPropertiesApiClient(),
			WikibaseRepo::getDefaultInstance()->getContentModelMappings()
		);
	}

	public function newApiEntityTitleTextLookup(): ApiEntityTitleTextLookup {
		return new ApiEntityTitleTextLookup(
			$this->newApiEntityNamespaceInfoLookup()
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
			$this->newFederatedPropertiesApiClient()
		);
	}

}
