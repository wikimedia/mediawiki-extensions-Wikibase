'use strict';

const responses = require( './responses' );
const requests = require( './requests' );

module.exports = {
	"get": {
		"operationId": "getPropertyAliasesInLanguage",
		"tags": [ "aliases" ],
		"summary": "Retrieve a Property's aliases in a specific language",
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
			"200": responses.PropertyAliasesInLanguage,
			"304": { "$ref": "#/components/responses/NotModified" },
			"400": { "$ref": "#/components/responses/InvalidTermByLanguageInput" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	},
	"post": {
		"operationId": "addPropertyAliasesInLanguage",
		"tags": [ "aliases" ],
		"summary": "Create / Add a Property's aliases in a specific language",
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
			"description": "Payload containing a list of Wikibase aliases in the specified language and edit metadata",
			"required": true,
			"content": {
				"application/json": {
					"schema": requests.AddAliasesInLanguage,
					"example": {
						"aliases": [ "is an" ],
						"tags": [],
						"bot": false,
						"comment": "Add English alias"
					}
				}
			}
		},
		"responses": {
			"200": {
				...responses.PropertyAliasesInLanguage,
				"description": "The updated list of aliases in a specific language"
			},
			"201": {
				...responses.PropertyAliasesInLanguage,
				"description": "The newly created list of aliases in a specific language"
			},
			"304": { "$ref": "#/components/responses/NotModified" },
			"400": { "$ref": "#/components/responses/InvalidAddAliasesInput" },
			"403": { "$ref": "#/components/responses/PermissionDenied" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"429": { "$ref": "#/components/responses/RequestLimitReached" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	}
};
