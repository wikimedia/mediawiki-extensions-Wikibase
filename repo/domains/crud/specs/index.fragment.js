'use strict';

const openapi = {
	"openapi": "3.1.0",
	"paths": {
		"/wikibase/v1/property-data-types": {
			"get": {
				"operationId": "getPropertyDataTypes",
				"tags": [ "Property data types" ],
				"summary": "Retrieve the map of Property data types to value types",
				"responses": {
					"200": {
						"description": "The map of Property data types to value types",
						"content": {
							"application/json": {
								"schema": {
									"type": "object",
									"additionalProperties": {
										"type": "string"
									}
								},
								"example": {
									"data-type": "value-type"
								}
							}
						}
					}
				}
			}
		},
		"/wikibase/v1/entities/items": require( './resources/items/list' ),
		"/wikibase/v1/entities/items/{item_id}": require( './resources/items/single' ),
		"/wikibase/v1/entities/items/{item_id}/sitelinks": require( './resources/sitelinks/list' ),
		"/wikibase/v1/entities/items/{item_id}/sitelinks/{site_id}": require( './resources/sitelinks/single' ),
		"/wikibase/v1/entities/items/{item_id}/labels": require( './resources/labels/list-for-item' ),
		"/wikibase/v1/entities/items/{item_id}/labels/{language_code}": require( './resources/labels/label-in-language-for-item' ),
		"/wikibase/v1/entities/items/{item_id}/labels_with_language_fallback/{language_code}": require( './resources/labels/label-with-fallback-for-item' ),
		"/wikibase/v1/entities/items/{item_id}/descriptions": require( './resources/descriptions/list-for-item' ),
		"/wikibase/v1/entities/items/{item_id}/descriptions/{language_code}": require( './resources/descriptions/description-in-language-for-item' ),
		"/wikibase/v1/entities/items/{item_id}/descriptions_with_language_fallback/{language_code}": require( './resources/descriptions/description-with-fallback-for-item' ),
		"/wikibase/v1/entities/items/{item_id}/aliases": require( './resources/aliases/list-for-item' ),
		"/wikibase/v1/entities/items/{item_id}/aliases/{language_code}": require( './resources/aliases/aliases-in-language-for-item' ),
		"/wikibase/v1/entities/items/{item_id}/statements": require( './resources/statements/list-for-item' ),
		"/wikibase/v1/entities/items/{item_id}/statements/{statement_id}": require( './resources/statements/single-for-item' ),
		"/wikibase/v1/entities/properties": require( './resources/properties/list' ),
		"/wikibase/v1/entities/properties/{property_id}": require( './resources/properties/single' ),
		"/wikibase/v1/entities/properties/{property_id}/labels": require( './resources/labels/list-for-property' ),
		"/wikibase/v1/entities/properties/{property_id}/labels/{language_code}": require( './resources/labels/label-in-language-for-property' ),
		"/wikibase/v1/entities/properties/{property_id}/labels_with_language_fallback/{language_code}": require( './resources/labels/label-with-fallback-for-property' ),
		"/wikibase/v1/entities/properties/{property_id}/descriptions": require( './resources/descriptions/list-for-property' ),
		"/wikibase/v1/entities/properties/{property_id}/descriptions/{language_code}": require( './resources/descriptions/description-in-language-for-property' ),
		"/wikibase/v1/entities/properties/{property_id}/descriptions_with_language_fallback/{language_code}": require( './resources/descriptions/description-with-fallback-for-property' ),
		"/wikibase/v1/entities/properties/{property_id}/aliases": require( './resources/aliases/list-for-property' ),
		"/wikibase/v1/entities/properties/{property_id}/aliases/{language_code}": require( './resources/aliases/aliases-in-language-for-property' ),
		"/wikibase/v1/entities/properties/{property_id}/statements": require( './resources/statements/list-for-property' ),
		"/wikibase/v1/entities/properties/{property_id}/statements/{statement_id}": require( './resources/statements/single-for-property' ),
		"/wikibase/v1/statements/{statement_id}": require( './resources/statements/single' )
	},
	"components": {
		"parameters": require( './global/parameters' ),
		"requestBodies": require( './global/requests' ),
		"responses": require( './global/responses' ),
		"headers": require( './global/headers' ),
		"schemas": require( './global/schemas' ),
		"examples": require( './global/examples' ),
	},
	"tags": require( './global/tags' )
};

// export the definition for use in other modules (useful in mocha tests and helpers, for example)
module.exports = { openapi };

if ( require.main === module ) {
	// If executed directly, output the OpenAPI fragment as JSON.
	// This is used in the "spec:join" script to generate the full OpenAPI spec via Redocly
	console.log( JSON.stringify( openapi, null, 2 ) ); // eslint-disable-line no-console
}
