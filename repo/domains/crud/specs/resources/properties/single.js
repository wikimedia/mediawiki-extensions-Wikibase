'use strict';

const responseParts = require( '../../global/response-parts' );
const requestParts = require( '../../global/request-parts' );

const PatchPropertyRequest = {
	"schema": requestParts.PatchRequest,
	"example": {
		"patch": [
			{ "op": "add", "path": "/aliases/en/-", "value": "is an" }
		],
		"tags": [],
		"bot": false,
		"comment": "add 'is an' as an English alias"
	}
};

module.exports = {
	"get": {
		"operationId": "getProperty",
		"tags": [ "properties" ],
		"summary": "Retrieve a single Wikibase Property by ID",
		"parameters": [
			{ "$ref": "#/components/parameters/PropertyId" },
			{ "$ref": "#/components/parameters/PropertyFields" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfModifiedSince" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" },
			{ "$ref": "#/components/parameters/Authorization" }
		],
		"responses": {
			"200": { "$ref": "#/components/responses/Property" },
			"400": {
				"description": "The request cannot be processed",
				"content": {
					"application/json": {
						"schema": responseParts.ErrorSchema,
						"examples": {
							"invalid-path-parameter": { "$ref": "#/components/examples/InvalidPathParameterExample" },
							"invalid-query-parameter": { "$ref": "#/components/examples/InvalidQueryParameterExample" }
						}
					}
				},
				"headers": {
					"Content-Language": {
						"description": "Language code of the language in which error message is provided",
						"schema": { "type": "string" },
						"required": true
					}
				}
			},
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	},
	"patch": {
		"operationId": "patchProperty",
		"tags": [ "properties" ],
		"summary": "Change a single Wikibase Property by ID",
		"parameters": [
			{ "$ref": "#/components/parameters/PropertyId" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" }
		],
		"requestBody": {
			"required": true,
			"content": {
				"application/json-patch+json": PatchPropertyRequest,
				"application/json": PatchPropertyRequest,
			}
		},
		"responses": {
			"200": { "$ref": "#/components/responses/Property" },
			"400": { "$ref": "#/components/responses/InvalidPatch" },
			"403": { "$ref": "#/components/responses/PermissionDenied" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"409": { "$ref": "#/components/responses/CannotApplyPropertyPatch" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"422": { "$ref": "#/components/responses/InvalidPatchedProperty" },
			"429": { "$ref": "#/components/responses/RequestLimitReached" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	}
};
