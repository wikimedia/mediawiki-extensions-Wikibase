'use strict';

const requestParts = require( '../../global/request-parts' );

const PatchItemLabelsRequestContent = {
	"schema": requestParts.PatchRequest,
	"example": {
		"patch": [
			{ "op": "replace", "path": "/en", "value": "Jane Doe" }
		],
		"tags": [],
		"bot": false,
		"comment": "replace English label"
	},
};

const ItemLabelsResponse = {
	"description": "Item's labels by language",
	"headers": {
		"ETag": { "$ref": "#/components/headers/ETag" },
		"Last-Modified": { "$ref": "#/components/headers/Last-Modified" },
		"X-Authenticated-User": { "$ref": "#/components/headers/X-Authenticated-User" }
	},
	"content": {
		"application/json": {
			"schema": { "$ref": "#/components/schemas/Labels" },
			"example": {
				"en": "Jane Doe",
				"ru": "Джейн Доу"
			}
		}
	}
};

module.exports = {
	"get": {
		"operationId": "getItemLabels",
		"tags": [ "labels" ],
		"summary": "Retrieve an Item's labels",
		"parameters": [
			{ "$ref": "#/components/parameters/ItemId" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfModifiedSince" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" },
			{ "$ref": "#/components/parameters/Authorization" }
		],
		"responses": {
			"200": ItemLabelsResponse,
			"304": { "$ref": "#/components/responses/NotModified" },
			"308": { "$ref": "#/components/responses/MovedPermanently" },
			"400": { "$ref": "#/components/responses/InvalidEntityIdInput" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	},
	"patch": {
		"operationId": "patchItemLabels",
		"tags": [ "labels" ],
		"summary": "Change an Item's Labels",
		"parameters": [
			{ "$ref": "#/components/parameters/ItemId" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" }
		],
		"requestBody": {
			"description": "Payload containing a JSON Patch document to be applied to Labels and edit metadata",
			"required": true,
			"content": {
				"application/json-patch+json": PatchItemLabelsRequestContent,
				"application/json": PatchItemLabelsRequestContent,
			}
		},
		"responses": {
			"200": ItemLabelsResponse,
			"400": { "$ref": "#/components/responses/InvalidPatch" },
			"403": { "$ref": "#/components/responses/PermissionDenied" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"409": { "$ref": "#/components/responses/CannotApplyItemPatch" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"422": { "$ref": "#/components/responses/InvalidPatchedLabels" },
			"429": { "$ref": "#/components/responses/RequestLimitReached" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	}
};
