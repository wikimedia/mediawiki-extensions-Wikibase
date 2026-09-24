'use strict';

const parameterSets = require( '../../global/parameter-sets' );
const requests = require( './requests' );
const responses = require( './responses' );

module.exports = {
	"get": {
		"operationId": "getPropertyStatement",
		"tags": [ "statements" ],
		"summary": "Retrieve a single Statement from a Property",
		"description": "This endpoint is also accessible through `/statements/{statement_id}`",
		"parameters": [
			{ "$ref": "#/components/parameters/PropertyId" },
			{ "$ref": "#/components/parameters/PropertyStatementId" },
			...parameterSets.ReadConditionalHeaders,
			{ "$ref": "#/components/parameters/Authorization" }
		],
		"responses": {
			"200": {
				...responses.PropertyStatement,
				"description": "The requested Statement. Please note that the value of the `ETag` header field refers to the Property's revision ID."
			},
			"304": { "$ref": "#/components/responses/NotModified" },
			"400": responses.InvalidRetrievePropertyStatementInput,
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	},
	"put": {
		"operationId": "replacePropertyStatement",
		"tags": [ "statements" ],
		"summary": "Replace a single Statement of a Property",
		"description": "This endpoint is also accessible through `/statements/{statement_id}`",
		"parameters": [
			{ "$ref": "#/components/parameters/PropertyId" },
			{ "$ref": "#/components/parameters/PropertyStatementId" },
			...parameterSets.EditConditionalHeaders
		],
		"requestBody": requests.PropertyStatement,
		"responses": {
			"200": responses.PropertyStatement,
			"400": responses.InvalidReplacePropertyStatementInput,
			"403": { "$ref": "#/components/responses/PermissionDenied" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"429": { "$ref": "#/components/responses/RequestLimitReached" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	},
	"patch": {
		"operationId": "patchPropertyStatement",
		"tags": [ "statements" ],
		"summary": "Change elements of a single Statement of a Property",
		"description": "This endpoint is also accessible through `/statements/{statement_id}`.",
		"parameters": [
			{ "$ref": "#/components/parameters/PropertyId" },
			{ "$ref": "#/components/parameters/PropertyStatementId" },
			...parameterSets.EditConditionalHeaders
		],
		"requestBody": requests.PatchPropertyStatement,
		"responses": {
			"200": responses.PropertyStatement,
			"400": responses.InvalidPropertyStatementPatch,
			"403": { "$ref": "#/components/responses/PermissionDenied" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"409": { "$ref": "#/components/responses/CannotApplyStatementPatch" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"422": { "$ref": "#/components/responses/InvalidPatchedStatement" },
			"429": { "$ref": "#/components/responses/RequestLimitReached" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	},
	"delete": {
		"operationId": "deletePropertyStatement",
		"tags": [ "statements" ],
		"summary": "Delete a single Statement from a Property",
		"description": "This endpoint is also accessible through `/statements/{statement_id}`.",
		"parameters": [
			{ "$ref": "#/components/parameters/PropertyId" },
			{ "$ref": "#/components/parameters/PropertyStatementId" },
			...parameterSets.EditConditionalHeaders
		],
		"requestBody": { "$ref": "#/components/requestBodies/Delete" },
		"responses": {
			"200": responses.StatementDeleted,
			"400": responses.InvalidRemovePropertyStatementInput,
			"403": { "$ref": "#/components/responses/PermissionDenied" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"429": { "$ref": "#/components/responses/RequestLimitReached" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	}
};
