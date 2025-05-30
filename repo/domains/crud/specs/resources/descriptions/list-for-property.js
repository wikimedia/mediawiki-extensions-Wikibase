'use strict';

const requestParts = require( '../../global/request-parts' );

const PatchPropertyDescriptionsRequest = {
	"schema": requestParts.PatchRequest,
	"example": {
		"patch": [
			{ "op": "replace", "path": "/en", "value": "the subject is a concrete object (instance) of this class, category, or object group" }
		],
		"tags": [],
		"bot": false,
		"comment": "update English description"
	}
};

const PropertyDescriptionsResponse = {
	"description": "Property's descriptions by language",
	"headers": {
		"ETag": {
			"description": "Last entity revision number",
			"schema": { "type": "string" },
			"required": true
		},
		"Last-Modified": {
			"description": "Last modified date",
			"schema": { "type": "string" },
			"required": true
		},
		"X-Authenticated-User": {
			"description": "Optional username of the user making the request",
			"schema": { "type": "string" }
		}
	},
	"content": {
		"application/json": {
			"schema": { "$ref": "#/components/schemas/Descriptions" },
			"example": {
				"en": "the subject is a concrete object (instance) of this class, category, or object group",
				"ru": "данный элемент представляет собой конкретный объект (экземпляр / частный случай) класса, категории"
			}
		}
	}
};

module.exports = {
	"get": {
		"operationId": "getPropertyDescriptions",
		"tags": [ "descriptions" ],
		"summary": "Retrieve a Property's descriptions",
		"parameters": [
			{ "$ref": "#/components/parameters/PropertyId" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfModifiedSince" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" },
			{ "$ref": "#/components/parameters/Authorization" }
		],
		"responses": {
			"200": PropertyDescriptionsResponse,
			"304": { "$ref": "#/components/responses/NotModified" },
			"400": { "$ref": "#/components/responses/InvalidEntityIdInput" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	},
	"patch": {
		"operationId": "patchPropertyDescriptions",
		"tags": [ "descriptions" ],
		"summary": "Change a Property's descriptions",
		"parameters": [
			{ "$ref": "#/components/parameters/PropertyId" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" }
		],
		"requestBody": {
			"description": "Payload containing a JSON Patch document to be applied to a Property's descriptions and edit metadata",
			"required": true,
			"content": {
				"application/json-patch+json": PatchPropertyDescriptionsRequest,
				"application/json": PatchPropertyDescriptionsRequest
			}
		},
		"responses": {
			"200": PropertyDescriptionsResponse,
			"400": { "$ref": "#/components/responses/InvalidPatch" },
			"403": { "$ref": "#/components/responses/PermissionDenied" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"409": { "$ref": "#/components/responses/CannotApplyPropertyPatch" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"422": { "$ref": "#/components/responses/InvalidPatchedDescriptions" },
			"429": { "$ref": "#/components/responses/RequestLimitReached" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	}
};
