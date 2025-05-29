'use strict';

const requests = require( './requests' );
const responses = require( './responses' );
const examples = require( './examples' );

module.exports = {
	"get": {
		"operationId": "getItemStatements",
		"tags": [ "statements" ],
		"summary": "Retrieve Statements from an Item",
		"parameters": [
			{ "$ref": "#/components/parameters/ItemId" },
			{ "$ref": "#/components/parameters/PropertyFilter" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfModifiedSince" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" },
			{ "$ref": "#/components/parameters/Authorization" }
		],
		"responses": {
			"200": responses.ItemStatements,
			"304": { "$ref": "#/components/responses/NotModified" },
			"308": { "$ref": "#/components/responses/MovedPermanently" },
			"400": { "$ref": "#/components/responses/InvalidRetrieveStatementsInput" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	},
	"post": {
		"operationId": "addItemStatement",
		"tags": [ "statements" ],
		"summary": "Add a new Statement to an Item",
		"parameters": [
			{ "$ref": "#/components/parameters/ItemId" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" },
			{ "$ref": "#/components/parameters/IfNoneMatch" }
		],
		"requestBody": requests.ItemStatement,
		"responses": {
			"201": {
				"description": "The newly created Statement. Please note that the value of the `ETag` header field refers to the Item's revision ID.",
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
					"Location": {
						"description": "The URI of the newly created Statement",
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
						"schema": responses.StatementSchema,
						"example": examples.ItemStatementResponse
					}
				}
			},
			"400": { "$ref": "#/components/responses/InvalidNewStatementInput" },
			"403": { "$ref": "#/components/responses/PermissionDenied" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"409": { "$ref": "#/components/responses/ItemRedirected" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"429": { "$ref": "#/components/responses/RequestLimitReached" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	}
};
