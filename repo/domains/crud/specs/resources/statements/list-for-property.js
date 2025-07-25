'use strict';

const requests = require( './requests' );
const responses = require( './responses' );
const examples = require( './examples' );

module.exports = {
	"get": {
		"operationId": "getPropertyStatements",
		"tags": [ "statements" ],
		"summary": "Retrieve Statements from a Property",
		"parameters": [
			{ "$ref": "#/components/parameters/PropertyId" },
			{ "$ref": "#/components/parameters/PropertyFilter" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfModifiedSince" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" },
			{ "$ref": "#/components/parameters/Authorization" }
		],
		"responses": {
			"200": responses.PropertyStatements,
			"304": { "$ref": "#/components/responses/NotModified" },
			"400": { "$ref": "#/components/responses/InvalidRetrieveStatementsInput" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	},
	"post": {
		"operationId": "addPropertyStatement",
		"tags": [ "statements" ],
		"summary": "Add a new Statement to a Property",
		"parameters": [
			{ "$ref": "#/components/parameters/PropertyId" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" },
			{ "$ref": "#/components/parameters/IfNoneMatch" }
		],
		"requestBody": requests.PropertyStatement,
		"responses": {
			"201": {
				"description": "The newly created Statement. Please note that the value of the `ETag` header field refers to the Property's revision ID.",
				"headers": {
					"ETag": { "$ref": "#/components/headers/ETag" },
					"Last-Modified": { "$ref": "#/components/headers/Last-Modified" },
					"Location": { "$ref": "#/components/headers/Location" },
					"X-Authenticated-User": { "$ref": "#/components/headers/X-Authenticated-User" }
				},
				"content": {
					"application/json": {
						"schema": responses.StatementSchema,
						"example": examples.PropertyStatementResponse
					}
				}
			},
			"400": { "$ref": "#/components/responses/InvalidNewStatementInput" },
			"403": { "$ref": "#/components/responses/PermissionDenied" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"429": { "$ref": "#/components/responses/RequestLimitReached" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	}
};
