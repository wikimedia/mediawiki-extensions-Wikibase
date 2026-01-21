'use strict';

const parameters = require( './global/parameters' );

const openapi = {
	"openapi": "3.1.0",
	"tags": [
		{
			"name": "item search",
			"description": "Simple item search"
		},
		{
			"name": "property search",
			"description": "Simple property search"
		}
	],
	"paths": {
		"/v1/search/items": {
			"get": {
				"operationId": "simpleItemSearch",
				"tags": [ "item search" ],
				"summary": "Simple Item search by label and aliases",
				"parameters": [
					{
						...parameters.SearchQuery,
						"example": "potato"
					},
					{ "$ref": "#/components/parameters/SearchLanguage" },
					{ "$ref": "#/components/parameters/Limit" },
					{ "$ref": "#/components/parameters/Offset" },
				],
				"responses": {
					"200": { "$ref": "#/components/responses/SearchItemSuccess" },
					"400": { "$ref": "#/components/responses/BadRequest" }
				}
			}
		},
		"/v1/search/properties": {
			"get": {
				"operationId": "simplePropertySearch",
				"tags": [ "property search" ],
				"summary": "Simple Property search by label and aliases",
				"parameters": [
					{
						...parameters.SearchQuery,
						"example": "taxon"
					},
					{ "$ref": "#/components/parameters/SearchLanguage" },
					{ "$ref": "#/components/parameters/Limit" },
					{ "$ref": "#/components/parameters/Offset" },
				],
				"responses": {
					"200": { "$ref": "#/components/responses/SearchPropertySuccess" },
					"400": { "$ref": "#/components/responses/BadRequest" }
				}
			}
		},
		"/v1/suggest/items": {
			"get": {
				"operationId": "suggestItems",
				"tags": [ "item search" ],
				"summary": "Simple Item search by prefix, for labels and aliases",
				"parameters": [
					{
						...parameters.SearchQuery,
						"example": "pota"
					},
					{ "$ref": "#/components/parameters/SearchLanguage" },
					{ "$ref": "#/components/parameters/Limit" },
					{ "$ref": "#/components/parameters/Offset" },
				],
				"responses": {
					"200": { "$ref": "#/components/responses/SuggestItemSuccess" },
					"400": { "$ref": "#/components/responses/BadRequest" }
				}
			}
		},
		"/v1/suggest/properties": {
			"get": {
				"operationId": "suggestProperties",
				"tags": [ "property search" ],
				"summary": "Simple Property search by prefix, for labels and aliases",
				"parameters": [
					{
						...parameters.SearchQuery,
						"example": "taxon"
					},
					{ "$ref": "#/components/parameters/SearchLanguage" },
					{ "$ref": "#/components/parameters/Limit" },
					{ "$ref": "#/components/parameters/Offset" },
				],
				"responses": {
					"200": { "$ref": "#/components/responses/SuggestPropertySuccess" },
					"400": { "$ref": "#/components/responses/BadRequest" }
				}
			}
		}
	},
	"components": {
		"parameters": parameters.components,
		"responses": require( './global/responses' ),
	},
};

// export the definition for use in other modules (useful in mocha tests and helpers, for example)
module.exports = { openapi };

if ( require.main === module ) {
	// If executed directly, output the OpenAPI fragment as JSON.
	// This is used in the "spec:join" script to generate the full OpenAPI spec via Redocly
	console.log( JSON.stringify( openapi, null, 2 ) ); // eslint-disable-line no-console
}
