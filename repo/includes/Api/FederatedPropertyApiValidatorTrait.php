<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Api;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Trait for Action API endpoints to disable altering properties when
 * federated properties is enabled.
 *
 * @license GPL-2.0-or-later
 * @author Tobias Andersson
 */
trait FederatedPropertyApiValidatorTrait {

	/**
	 * @var ApiErrorReporter|null
	 */
	protected $errorReporter;

	/**
	 * @var bool
	 */
	private $federatedPropertiesEnabled;

	protected function validateAlteringEntityById( ?EntityId $entityId ) {
		if ( $this->federatedPropertiesEnabled && $entityId instanceof PropertyId ) {
			$this->errorReporter->dieWithError(
				'wikibase-federated-properties-local-property-api-error-message',
				'param-illegal'
			);
		}
	}

}
