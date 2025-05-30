'use strict';

const requestParts = require( '../../global/request-parts' );
const responses = require( './responses' );

module.exports = {
	"get": {
		"operationId": "getPropertyLabel",
		"tags": [ "labels" ],
		"summary": "Retrieve a Property's label in a specific language",
		"parameters": [
			{ "$ref": "#/components/parameters/PropertyId" },
			{ "$ref": "#/components/parameters/LanguageCode" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfModifiedSince" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" },
			{ "$ref": "#/components/parameters/Authorization" }
		],
		"responses": {
			"200": responses.PropertyLabel,
			"304": { "$ref": "#/components/responses/NotModified" },
			"400": { "$ref": "#/components/responses/InvalidTermByLanguageInput" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	},
	"put": {
		"operationId": "replacePropertyLabel",
		"tags": [ "labels" ],
		"summary": "Add / Replace a Property's label in a specific language",
		"parameters": [
			{ "$ref": "#/components/parameters/PropertyId" },
			{ "$ref": "#/components/parameters/LanguageCode" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfModifiedSince" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" },
			{ "$ref": "#/components/parameters/Authorization" }
		],
		"requestBody": {
			"description": "Payload containing a Property label in the specified language and edit metadata",
			"required": true,
			"content": {
				"application/json": {
					"schema": {
						"allOf": [
							{
								"type": "object",
								"properties": {
									"label": { "type": "string" }
								},
								"required": [ "label" ]
							},
							requestParts.MediawikiEdit,
						]
					},
					"example": {
						"label": "instance of",
						"tags": [],
						"bot": false,
						"comment": "Update the English label"
					}
				}
			}
		},
		"responses": {
			"200": {
				...responses.PropertyLabel,
				"description": "The updated Label in a specific language"
			},
			"201": {
				...responses.PropertyLabel,
				"description": "The newly added Label in a specific language"
			},
			"304": { "$ref": "#/components/responses/NotModified" },
			"400": { "$ref": "#/components/responses/InvalidSetLabelInput" },
			"403": { "$ref": "#/components/responses/PermissionDenied" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"422": { "$ref": "#/components/responses/DataPolicyViolation" },
			"429": { "$ref": "#/components/responses/RequestLimitReached" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	},
	"delete": {
		"operationId": "deletePropertyLabel",
		"tags": [ "labels" ],
		"summary": "Delete a Property's label in a specific language",
		"parameters": [
			{ "$ref": "#/components/parameters/PropertyId" },
			{ "$ref": "#/components/parameters/LanguageCode" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfModifiedSince" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" },
			{ "$ref": "#/components/parameters/Authorization" }
		],
		"requestBody": { "$ref": "#/components/requestBodies/Delete" },
		"responses": {
			"200": responses.LabelDeleted,
			"400": { "$ref": "#/components/responses/InvalidRemoveLabelInput" },
			"403": { "$ref": "#/components/responses/PermissionDenied" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"429": { "$ref": "#/components/responses/RequestLimitReached" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	}
};
