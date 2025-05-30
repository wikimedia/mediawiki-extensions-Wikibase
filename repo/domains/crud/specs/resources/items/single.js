'use strict';

const requestParts = require( '../../global/request-parts' );
const responseParts = require( '../../global/response-parts' );

const PatchItemRequestContent = {
	"schema": requestParts.PatchRequest,
	"example": {
		"patch": [
			{ "op": "replace", "path": "/labels/en", "value": "Jane Doe" }
		],
		"tags": [],
		"bot": false,
		"comment": "Update the English label"
	},
};

module.exports = {
	"get": {
		"operationId": "getItem",
		"tags": [ "items" ],
		"summary": "Retrieve a single Wikibase Item by ID",
		"parameters": [
			{ "$ref": "#/components/parameters/ItemId" },
			{ "$ref": "#/components/parameters/ItemFields" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfModifiedSince" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" },
			{ "$ref": "#/components/parameters/Authorization" }
		],
		"responses": {
			"200": { "$ref": "#/components/responses/Item" },
			"308": { "$ref": "#/components/responses/MovedPermanently" },
			"304": { "$ref": "#/components/responses/NotModified" },
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
			404: { "$ref": "#/components/responses/ResourceNotFound" },
			412: { "$ref": "#/components/responses/PreconditionFailedError" },
			500: { "$ref": "#/components/responses/UnexpectedError" }
		}
	},
	"patch": {
		"operationId": "patchItem",
		"tags": [ "items" ],
		"summary": "Change a single Wikibase Item by ID",
		"parameters": [
			{ "$ref": "#/components/parameters/ItemId" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" }
		],
		"requestBody": {
			"required": true,
			"content": {
				"application/json-patch+json": PatchItemRequestContent,
				"application/json": PatchItemRequestContent
			}
		},
		"responses": {
			"200": { "$ref": "#/components/responses/Item" },
			"400": { "$ref": "#/components/responses/InvalidPatch" },
			"403": { "$ref": "#/components/responses/PermissionDenied" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"409": { "$ref": "#/components/responses/CannotApplyItemPatch" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"422": { "$ref": "#/components/responses/InvalidPatchedItem" },
			"429": { "$ref": "#/components/responses/RequestLimitReached" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	}
};
