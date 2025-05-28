'use strict';

const parameters = require( './global/parameters' );

const openapi = {
	"openapi": "3.1.0",
	"info": {
		"title": "Wikibase Search Domain REST API",
		"version": "0.1",
		"description": "OpenAPI fragment of the Wikibase Search domain REST API"
	},
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
		"/v0/search/items": {
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
		"/v0/search/properties": {
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
	// if executed directly, print the definition
	console.log( JSON.stringify( openapi, null, 2 ) ); // eslint-disable-line no-console
}
