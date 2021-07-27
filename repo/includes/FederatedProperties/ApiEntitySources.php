<?php

declare( strict_types=1 );

namespace Wikibase\Repo\FederatedProperties;

use LogicException;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\Property;

/**
 * @license GPL-2.0-or-later
 */
class ApiEntitySources {

	private $entitySourceDefinitions;

	public function __construct( EntitySourceDefinitions $entitySourceDefinitions ) {
		$this->entitySourceDefinitions = $entitySourceDefinitions;
	}

	/**
	 * As of Federated Properties v2 there is only one source of federation, so returning a single EntitySource is ok.
	 * This method should never be used if Federated Properties isn't guaranteed to be enabled.
	 */
	public function getApiPropertySource(): EntitySource {
		foreach ( $this->entitySourceDefinitions->getSources() as $source ) {
			if ( $source->getType() === EntitySource::TYPE_API && in_array( Property::ENTITY_TYPE, $source->getEntityTypes() ) ) {
				return $source;
			}
		}

		throw new LogicException( 'No Property API EntitySource defined' );
	}

}
