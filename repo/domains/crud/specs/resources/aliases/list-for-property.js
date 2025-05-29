'use strict';

const requestParts = require( '../../global/request-parts' );
const responses = require( './responses' );
const examples = require( './examples' );

module.exports = {
	"get": {
		"operationId": "getPropertyAliases",
		"tags": [ "aliases" ],
		"summary": "Retrieve a Property's aliases",
		"parameters": [
			{ "$ref": "#/components/parameters/PropertyId" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfModifiedSince" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" },
			{ "$ref": "#/components/parameters/Authorization" }
		],
		"responses": {
			"200": responses.PropertyAliases,
			"304": { "$ref": "#/components/responses/NotModified" },
			"400": { "$ref": "#/components/responses/InvalidEntityIdInput" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	},
	"patch": {
		"operationId": "patchPropertyAliases",
		"tags": [ "aliases" ],
		"summary": "Change a Property's aliases",
		"parameters": [
			{ "$ref": "#/components/parameters/PropertyId" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" }
		],
		"requestBody": {
			"description": "Payload containing a JSON Patch document to be applied to a Property's aliases and edit metadata",
			"required": true,
			"content": {
				"application/json-patch+json": {
					"schema": requestParts.PatchRequest,
					"example": examples.PatchPropertyAliases
				},
				"application/json": {
					"schema": requestParts.PatchRequest,
					"example": examples.PatchPropertyAliases
				}
			}
		},
		"responses": {
			"200": responses.PropertyAliases,
			"400": { "$ref": "#/components/responses/InvalidPatch" },
			"403": { "$ref": "#/components/responses/PermissionDenied" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"409": { "$ref": "#/components/responses/CannotApplyPropertyPatch" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"422": { "$ref": "#/components/responses/InvalidPatchedAliases" },
			"429": { "$ref": "#/components/responses/RequestLimitReached" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	}
};
