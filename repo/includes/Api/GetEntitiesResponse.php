<?php

namespace Wikibase\Repo\Api;

// An object representing results of GetEntitiesRequest, that can be then presented in a desired manner.
// As single GetEntities request accepts an input of multiple entities (IDs) which are actually handled
// completely individually, this would/could also consist of individual "elements", as they are intended
// as "separate" responses, just happening to be mashed in a single response "document".
class GetEntitiesResponse {

	/*
	 * @var GetEntitiesResponseElement[]
	 */
	private $elements = [];

	public function addElement( GetEntitiesResponseElement $element ) {
		$this->elements[] = $element;
	}

	/**
	 * @return GetEntitiesResponseElement[]
	 */
	public function getElements() {
		return $this->elements;
	}

}
