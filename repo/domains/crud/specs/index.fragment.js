'use strict';

const openapi = {
	"openapi": "3.1.0",
	"info": {
		"title": "Wikibase CRUD Domain REST API",
		"version": "1.1",
		"description": "OpenAPI fragment of the Wikibase CRUD domain REST API"
	},
	"paths": {
		"/v1/property-data-types": {
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
		"/v1/entities/items": require( './resources/items/list' ),
		"/v1/entities/items/{item_id}": require( './resources/items/single' ),
		"/v1/entities/items/{item_id}/sitelinks": require( './resources/sitelinks/list' ),
		"/v1/entities/items/{item_id}/sitelinks/{site_id}": require( './resources/sitelinks/single' ),
		"/v1/entities/items/{item_id}/labels": require( './resources/labels/list-for-item' ),
		"/v1/entities/items/{item_id}/labels/{language_code}": require( './resources/labels/label-in-language-for-item' ),
		"/v1/entities/items/{item_id}/labels_with_language_fallback/{language_code}": require( './resources/labels/label-with-fallback-for-item' ),
		"/v1/entities/items/{item_id}/descriptions": require( './resources/descriptions/list-for-item' ),
		"/v1/entities/items/{item_id}/descriptions/{language_code}": require( './resources/descriptions/description-in-language-for-item' ),
		"/v1/entities/items/{item_id}/descriptions_with_language_fallback/{language_code}": require( './resources/descriptions/description-with-fallback-for-item' ),
		"/v1/entities/items/{item_id}/aliases": require( './resources/aliases/list-for-item' ),
		"/v1/entities/items/{item_id}/aliases/{language_code}": require( './resources/aliases/aliases-in-language-for-item' ),
		"/v1/entities/items/{item_id}/statements": require( './resources/statements/list-for-item' ),
		"/v1/entities/items/{item_id}/statements/{statement_id}": require( './resources/statements/single-for-item' ),
		"/v1/entities/properties": require( './resources/properties/list' ),
		"/v1/entities/properties/{property_id}": require( './resources/properties/single' ),
		"/v1/entities/properties/{property_id}/labels": require( './resources/labels/list-for-property' ),
		"/v1/entities/properties/{property_id}/labels/{language_code}": require( './resources/labels/label-in-language-for-property' ),
		"/v1/entities/properties/{property_id}/labels_with_language_fallback/{language_code}": require( './resources/labels/label-with-fallback-for-property' ),
		"/v1/entities/properties/{property_id}/descriptions": require( './resources/descriptions/list-for-property' ),
		"/v1/entities/properties/{property_id}/descriptions/{language_code}": require( './resources/descriptions/description-in-language-for-property' ),
		"/v1/entities/properties/{property_id}/descriptions_with_language_fallback/{language_code}": require( './resources/descriptions/description-with-fallback-for-property' ),
		"/v1/entities/properties/{property_id}/aliases": require( './resources/aliases/list-for-property' ),
		"/v1/entities/properties/{property_id}/aliases/{language_code}": require( './resources/aliases/aliases-in-language-for-property' ),
		"/v1/entities/properties/{property_id}/statements": require( './resources/statements/list-for-property' ),
		"/v1/entities/properties/{property_id}/statements/{statement_id}": require( './resources/statements/single-for-property' ),
		"/v1/statements/{statement_id}": require( './resources/statements/single' )
	},
	"components": {
		"parameters": require( './global/parameters' ),
		"requestBodies": require( './global/requests' ),
		"responses": require( './global/responses' ),
		"schemas": require( './global/schemas' ),
		"examples": require( './global/examples' ),
	},
	"tags": require( './global/tags' )
};

// export the definition for use in other modules (useful in mocha tests and helpers, for example)
module.exports = { openapi };

if ( require.main === module ) {
	// if executed directly, print the definition
	console.log( JSON.stringify( openapi, null, 2 ) ); // eslint-disable-line no-console
}
