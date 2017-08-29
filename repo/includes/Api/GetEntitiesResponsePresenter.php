<?php

namespace Wikibase\Repo\Api;

// An object turning the GetEntitiesResponse into an output presented to the API user

// Could also be made an interface and have an implementation adding the Response to MW API output.
// That would add a bit more of an abstraction but also make for a better separation from MW API framework.
class GetEntitiesResponsePresenter {

	private $presenters = [];

	public function present( GetEntitiesResponse $response ) {
		// enumerate over the individual entries in the $response, for each add get the appropriate presenter
		// from $presenter, and put the data that is expected to be presented to the output.
		foreach ( $response->getElements() as $element ) {
			$type = $element->getEntityId()->getEntityType();
			if ( array_key_exists( $type, $this->presenters ) ) {
				// Also probably pass some output specific object, so the $response can actually be presented!
				call_user_func( $this->presenters[$type], $element );
			}
		}
	}

}
