<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\FederatedProperties;

/**
 * A class for getting namespaces for federated properties one time per request.
 * @license GPL-2.0-or-later
 */
class ApiEntityNamespaceInfoLookup {

	/**
	 * @var array[]
	 */
	private $namespaces;

	/**
	 * @var GenericActionApiClient
	 */
	private $api;

	/**
	 * @var array
	 */
	private $contentModelMapping;

	public function __construct( GenericActionApiClient $api, array $contentModelMapping ) {
		$this->api = $api;
		$this->contentModelMapping = $contentModelMapping;
	}

	/**
	 * @param string $entityType
	 * @return string|null Namespace name or null if no namespace is found for the entity
	 */
	public function getNamespaceNameForEntityType( string $entityType ): ?string {
		if ( !array_key_exists( $entityType, $this->contentModelMapping ) ) {
			return null;
		}

		$entityContentModel = $this->contentModelMapping[$entityType];
		$this->fetchNamespaces();

		return $this->getNamespaceNameForContentModel( $entityContentModel );
	}

	private function fetchNamespaces() {
		if ( !empty( $this->namespaces ) ) {
			return;
		}

		// @phan-suppress-next-line PhanTypeArraySuspiciousNullable The API response will be JSON here
		$this->namespaces = json_decode( $this->api->get( [
			'action' => 'query',
			'meta' => 'siteinfo',
			'siprop' => 'namespaces',
			'format' => 'json',
		] )->getBody()->getContents(), true )['query']['namespaces'];
	}

	private function getNamespaceNameForContentModel( string $entityContentModel ): ?string {
		foreach ( $this->namespaces as $namespace ) {
			if ( isset( $namespace['defaultcontentmodel'] ) && $namespace['defaultcontentmodel'] === $entityContentModel ) {
				if ( array_key_exists( 'canonical', $namespace ) ) {
					return $namespace['canonical'];
				}
				if ( $namespace['id'] === NS_MAIN ) {
					return '';
				}
			}
		}

		return null;
	}

}
