<?php

namespace Wikibase\Repo\FederatedProperties;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;

/**
 * Simple implementation using individual wbgetentities calls with no caching.
 *
 * If caching is desired it should probably be done in a wrapping service.
 * If Federated Properties needs a PropertyInfoLookup implementation then this
 * service should probably use that rather than doing its own API calls.
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class ApiPropertyDataTypeLookup implements PropertyDataTypeLookup {

	/**
	 * @var GenericActionApiClient
	 */
	private $api;

	/**
	 * @param GenericActionApiClient $api
	 */
	public function __construct( GenericActionApiClient $api ) {
		$this->api = $api;
	}

	/**
	 * @inheritDoc
	 */
	public function getDataTypeIdForProperty( PropertyId $propertyId ) {
		// @phan-suppress-next-line PhanTypeArraySuspiciousNullable The API response will be JSON here
		return json_decode( $this->api->get( [
			'action' => 'wbgetentities',
			'ids' => $propertyId->getSerialization(),
			'props' => 'datatype',
			'format' => 'json'
		] )->getBody(), true )['entities'][$propertyId->getSerialization()]['datatype'];
	}

}
