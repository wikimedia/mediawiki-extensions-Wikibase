'use strict';

const requestParts = require( '../../global/request-parts' );

const PropertyLabelsResponse = {
	"description": "Property's labels by language",
	"headers": {
		"ETag": { "$ref": "#/components/headers/ETag" },
		"Last-Modified": { "$ref": "#/components/headers/Last-Modified" },
		"X-Authenticated-User": { "$ref": "#/components/headers/X-Authenticated-User" }
	},
	"content": {
		"application/json": {
			"schema": { "$ref": "#/components/schemas/Labels" },
			"example": {
				"en": "instance of",
				"ru": "это частный случай понятия"
			}
		}
	}
};

const PatchPropertyLabelsRequestContent = {
	"schema": requestParts.PatchRequest,
	"example": {
		"patch": [
			{ "op": "replace", "path": "/en", "value": "instance of" }
		],
		"tags": [],
		"bot": false,
		"comment": "replace English label"
	},
};

module.exports = {
	"get": {
		"operationId": "getPropertyLabels",
		"tags": [ "labels" ],
		"summary": "Retrieve a Property's labels",
		"parameters": [
			{ "$ref": "#/components/parameters/PropertyId" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfModifiedSince" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" },
			{ "$ref": "#/components/parameters/Authorization" }
		],
		"responses": {
			"200": PropertyLabelsResponse,
			"304": { "$ref": "#/components/responses/NotModified" },
			"400": { "$ref": "#/components/responses/InvalidEntityIdInput" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	},
	"patch": {
		"operationId": "patchPropertyLabels",
		"tags": [ "labels" ],
		"summary": "Change a Property's Labels",
		"parameters": [
			{ "$ref": "#/components/parameters/PropertyId" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" }
		],
		"requestBody": {
			"description": "Payload containing a JSON Patch document to be applied to Labels and edit metadata",
			"required": true,
			"content": {
				"application/json-patch+json": PatchPropertyLabelsRequestContent,
				"application/json": PatchPropertyLabelsRequestContent
			}
		},
		"responses": {
			"200": PropertyLabelsResponse,
			"400": { "$ref": "#/components/responses/InvalidPatch" },
			"403": { "$ref": "#/components/responses/PermissionDenied" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"409": { "$ref": "#/components/responses/CannotApplyPropertyPatch" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"422": { "$ref": "#/components/responses/InvalidPatchedLabels" },
			"429": { "$ref": "#/components/responses/RequestLimitReached" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	}
};
