<?php

namespace Wikibase\Repo\Api;

// An object representing the request to the API. As the API action allows to provide multiple entries
// (entities) in a single request, that are handled completely separate, this would probably consists of "elements"
// representing each of those entries.
// This is meant as an abstract object, created by a GetEntitiesRequestParser from the "physical" request
// (be it HTTP request, JSON object, whetever the framework provides).
class GetEntitiesRequest {

	/**
	 * @var GetEntitiesRequestElement[]
	 */
	private $elements = [];

	public function addElement( GetEntitiesRequestElement $element ) {
		$this->elements[] = $element;
	}

	/**
	 * @return GetEntitiesRequestElement[]
	 */
	public function getElements() {
		return $this->elements;
	}

}
