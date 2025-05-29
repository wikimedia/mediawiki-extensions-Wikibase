'use strict';

const requestParts = require( '../../global/request-parts' );

const PatchItemDescriptionsRequest = {
	"schema": requestParts.PatchRequest,
	"example": {
		"patch": [
			{ "op": "replace", "path": "/en", "value": "famous person" }
		],
		"tags": [],
		"bot": false,
		"comment": "update English description"
	}
};

const ItemDescriptionsResponse = {
	"description": "Item's descriptions by language",
	"headers": {
		"ETag": { "$ref": "#/components/headers/ETag" },
		"Last-Modified": { "$ref": "#/components/headers/Last-Modified" },
		"X-Authenticated-User": { "$ref": "#/components/headers/X-Authenticated-User" }
	},
	"content": {
		"application/json": {
			"schema": { "$ref": "#/components/schemas/Descriptions" },
			"example": {
				"en": "famous person",
				"ru": "известная личность"
			}
		}
	}
};

module.exports = {
	"get": {
		"operationId": "getItemDescriptions",
		"tags": [ "descriptions" ],
		"summary": "Retrieve an Item's descriptions",
		"parameters": [
			{ "$ref": "#/components/parameters/ItemId" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfModifiedSince" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" },
			{ "$ref": "#/components/parameters/Authorization" }
		],
		"responses": {
			"200": ItemDescriptionsResponse,
			"304": { "$ref": "#/components/responses/NotModified" },
			"308": { "$ref": "#/components/responses/MovedPermanently" },
			"400": { "$ref": "#/components/responses/InvalidEntityIdInput" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	},
	"patch": {
		"operationId": "patchItemDescriptions",
		"tags": [ "descriptions" ],
		"summary": "Change an Item's descriptions",
		"parameters": [
			{ "$ref": "#/components/parameters/ItemId" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" }
		],
		"requestBody": {
			"description": "Payload containing a JSON Patch document to be applied to an Item's descriptions and edit metadata",
			"required": true,
			"content": {
				"application/json-patch+json": PatchItemDescriptionsRequest,
				"application/json": PatchItemDescriptionsRequest
			}
		},
		"responses": {
			"200": ItemDescriptionsResponse,
			"400": { "$ref": "#/components/responses/InvalidPatch" },
			"403": { "$ref": "#/components/responses/PermissionDenied" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"409": { "$ref": "#/components/responses/CannotApplyItemPatch" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"422": { "$ref": "#/components/responses/InvalidPatchedDescriptions" },
			"429": { "$ref": "#/components/responses/RequestLimitReached" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	}
};
