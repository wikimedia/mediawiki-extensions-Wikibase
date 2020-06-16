<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\FederatedProperties;

/**
 * Exception thrown when the request execution failed before getting a response, e.g. by hitting a timeout.
 *
 * @license GPL-2.0-or-later
 */
class ApiRequestExecutionException extends FederatedPropertiesException {
}
