'use strict';

const requestParts = require( '../../global/request-parts' );

const PatchItemAliasesRequestContent = {
	"schema": requestParts.PatchRequest,
	"example": {
		"patch": [
			{ "op": "add", "path": "/en/-", "value": "JD" }
		],
		"tags": [],
		"bot": false,
		"comment": "Add English alias"
	}
};

const ItemAliasesResponse = {
	"description": "Item's aliases by language",
	"headers": {
		"ETag": { "$ref": "#/components/headers/ETag" },
		"Last-Modified": { "$ref": "#/components/headers/Last-Modified" },
		"X-Authenticated-User": { "$ref": "#/components/headers/X-Authenticated-User" }
	},
	"content": {
		"application/json": {
			"schema": { "$ref": "#/components/schemas/Aliases" },
			"example": {
				"en": [ "Jane M. Doe", "JD" ],
				"ru": [ "Джейн М. Доу" ]
			}
		}
	}
};

module.exports = {
	"get": {
		"operationId": "getItemAliases",
		"tags": [ "aliases" ],
		"summary": "Retrieve an Item's aliases",
		"parameters": [
			{ "$ref": "#/components/parameters/ItemId" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfModifiedSince" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" },
			{ "$ref": "#/components/parameters/Authorization" }
		],
		"responses": {
			"200": ItemAliasesResponse,
			"304": { "$ref": "#/components/responses/NotModified" },
			"308": { "$ref": "#/components/responses/MovedPermanently" },
			"400": { "$ref": "#/components/responses/InvalidEntityIdInput" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	},
	"patch": {
		"operationId": "patchItemAliases",
		"tags": [ "aliases" ],
		"summary": "Change an Item's aliases",
		"parameters": [
			{ "$ref": "#/components/parameters/ItemId" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" }
		],
		"requestBody": {
			"description": "Payload containing a JSON Patch document to be applied to an Item's aliases and edit metadata",
			"required": true,
			"content": {
				"application/json-patch+json": PatchItemAliasesRequestContent,
				"application/json": PatchItemAliasesRequestContent,
			}
		},
		"responses": {
			"200": ItemAliasesResponse,
			"400": { "$ref": "#/components/responses/InvalidPatch" },
			"403": { "$ref": "#/components/responses/PermissionDenied" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"409": { "$ref": "#/components/responses/CannotApplyItemPatch" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"422": { "$ref": "#/components/responses/InvalidPatchedAliases" },
			"429": { "$ref": "#/components/responses/RequestLimitReached" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	}
};
