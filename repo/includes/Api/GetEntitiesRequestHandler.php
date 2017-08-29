<?php

namespace Wikibase\Repo\Api;

// An object taking care of handling (i.e. doing the actual action exposed by the API) for a given
// GetEntitiesRequest.
// This might include fetching the relevant entity(entities) from the store, getting its data,
// and including the data relevant to the action and the request to a GetEntitiesResponse obbject.
class GetEntitiesRequestHandler {

	// A map of functions handling GetEntitiesRequestElements, e.g. mapping entity types
	// to callable doing the actual handling
	private $handlers = [];

	public function handle( GetEntitiesRequest $request ) {
		$response = new GetEntitiesResponse();

		foreach ( $request->getElements() as $element ) {
			$type = $element->getEntityId()->getEntityType();
			if ( array_key_exists( $type, $this->handlers ) ) {
				// Process the $element according (e.g. load the entity with $id, grab additional
				// $extra data, get all the relevant data and add it to the $response
				call_user_func( $this->handlers, $element );
			}
		}

		return $response;
	}

}
