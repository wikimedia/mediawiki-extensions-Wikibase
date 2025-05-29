'use strict';

const requests = require( './requests' );

const ItemAliasesInLanguageResponse = {
	"description": "Item's aliases in a specific language",
	"headers": {
		"ETag": { "$ref": "#/components/headers/ETag" },
		"Last-Modified": { "$ref": "#/components/headers/Last-Modified" },
		"X-Authenticated-User": { "$ref": "#/components/headers/X-Authenticated-User" }
	},
	"content": {
		"application/json": {
			"schema": {
				"type": "array",
				"items": { "type": "string" }
			},
			"example": [ "Jane M. Doe", "JD" ]
		}
	}
};

module.exports = {
	"get": {
		"operationId": "getItemAliasesInLanguage",
		"tags": [ "aliases" ],
		"summary": "Retrieve an Item's aliases in a specific language",
		"parameters": [
			{ "$ref": "#/components/parameters/ItemId" },
			{ "$ref": "#/components/parameters/LanguageCode" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfModifiedSince" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" },
			{ "$ref": "#/components/parameters/Authorization" }
		],
		"responses": {
			"200": ItemAliasesInLanguageResponse,
			"304": { "$ref": "#/components/responses/NotModified" },
			"308": { "$ref": "#/components/responses/MovedPermanently" },
			"400": { "$ref": "#/components/responses/InvalidTermByLanguageInput" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	},
	"post": {
		"operationId": "addItemAliasesInLanguage",
		"tags": [ "aliases" ],
		"summary": "Create / Add an Item's aliases in a specific language",
		"parameters": [
			{ "$ref": "#/components/parameters/ItemId" },
			{ "$ref": "#/components/parameters/LanguageCode" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfModifiedSince" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" },
			{ "$ref": "#/components/parameters/Authorization" }
		],
		"requestBody": {
			"description": "Payload containing a list of Item aliases in the specified language and edit metadata",
			"required": true,
			"content": {
				"application/json": {
					"schema": requests.AddAliasesInLanguage,
					"example": {
						"aliases": [ "JD" ],
						"tags": [],
						"bot": false,
						"comment": "Add English alias"
					}
				}
			}
		},
		"responses": {
			"200": {
				...ItemAliasesInLanguageResponse,
				"description": "The updated list of aliases in a specific language",
			},
			"201": {
				...ItemAliasesInLanguageResponse,
				"description": "The newly created list of aliases in a specific language",
			},
			"304": { "$ref": "#/components/responses/NotModified" },
			"400": { "$ref": "#/components/responses/InvalidAddAliasesInput" },
			"403": { "$ref": "#/components/responses/PermissionDenied" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"409": { "$ref": "#/components/responses/ItemRedirected" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"429": { "$ref": "#/components/responses/RequestLimitReached" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	}
};
