<?php

namespace Wikibase\Repo\Api;

// Transforms the actual request (coming from the API framework etc) to the abstract
// GetEntitiesRequest that will be handled further.

// For a better separation from the MW API framework and easier testing could be also made an interface
// providing getRequest method for some generic input, and make the MW API specific parser and implementation
// of that. It could also take over or the "parameter parsing" stuff currently done in GetEntities class,
// using a code from ApiBase MW core class etc.
class GetEntitiesRequestParser {

	// A map of parsers defined for particular style of input
	// E.g. could map a pattern matching the ID to a callable turning it into "element"
	// consisting of that ID and possible additional data.
	// Would probably include "parsers" for "core" wikibase functionality (i.e. recognizing
	// item and property IDs) but must also be allowing extensions to add their
	// custom parsers (i.e. to parse "L6-F1" to an "element" consisting of LexemeID L6 and
	// additional data specifying it is about the lexeme's form "F1".
	private $parsers = [];

	public function getRequest( array $params ) {
		$request = new GetEntitiesRequest();

		if ( !isset( $params['ids'] ) ) {
			return $request;
		}

		// Route a particular request entry into a "parser" knowing how to handle this one,
		// and add it to the $request
		foreach ( $params['ids'] as $id ) {
			foreach ( $this->parsers as $pattern => $parser ) {
				if ( preg_match( $pattern, $id ) ) {
					$element = call_user_func( $parser, $id );
					$request->addElement( $element );
				}
			}
		}
		// Also handle site + titles pairs and other possible input
		//...

		return $request;
	}

}
