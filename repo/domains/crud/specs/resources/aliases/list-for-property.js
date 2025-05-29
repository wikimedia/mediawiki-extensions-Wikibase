'use strict';

const requestParts = require( '../../global/request-parts' );

const PatchPropertyAliasesRequestContent = {
	"schema": requestParts.PatchRequest,
	"example": {
		"patch": [
			{ "op": "add", "path": "/en/-", "value": "is an" }
		],
		"tags": [],
		"bot": false,
		"comment": "Add English alias"
	}
};

const PropertyAliasesResponse = {
	"description": "Property's aliases by language",
	"headers": {
		"ETag": { "$ref": "#/components/headers/ETag" },
		"Last-Modified": { "$ref": "#/components/headers/Last-Modified" },
		"X-Authenticated-User": { "$ref": "#/components/headers/X-Authenticated-User" }
	},
	"content": {
		"application/json": {
			"schema": { "$ref": "#/components/schemas/Aliases" },
			"example": {
				"en": [ "is a", "is an" ],
				"ru": [ "представляет собой", "является" ]
			}
		}
	}
};

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
			"200": PropertyAliasesResponse,
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
				"application/json-patch+json": PatchPropertyAliasesRequestContent,
				"application/json": PatchPropertyAliasesRequestContent
			}
		},
		"responses": {
			"200": PropertyAliasesResponse,
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
