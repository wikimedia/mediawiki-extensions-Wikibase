<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\FederatedProperties;

/**
 * Exception for when api response status in not OK or status code is not 200
 */
class ApiRequestException extends FederatedPropertiesException {
}
