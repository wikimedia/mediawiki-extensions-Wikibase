'use strict';

const requests = require( './requests' );
const responses = require( './responses' );

module.exports = {
	"get": {
		"operationId": "getStatement",
		"tags": [ "statements" ],
		"summary": "Retrieve a single Statement",
		"description": "This endpoint is also accessible through `/entities/items/{item_id}/statements/{statement_id}` and `/entities/properties/{property_id}/statements/{statement_id}`",
		"parameters": [
			{ "$ref": "#/components/parameters/StatementId" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfModifiedSince" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" },
			{ "$ref": "#/components/parameters/Authorization" }
		],
		"responses": {
			"200": {
				...responses.ItemStatement,
				"description": "The requested Statement. Please note that the value of the `ETag` header field refers to the subject's revision ID."
			},
			"304": { "$ref": "#/components/responses/NotModified" },
			"400": responses.InvalidRetrieveStatementInput,
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	},
	"put": {
		"operationId": "replaceStatement",
		"tags": [ "statements" ],
		"summary": "Replace a single Statement",
		"description": "This endpoint is also accessible through `/entities/items/{item_id}/statements/{statement_id}` and `/entities/properties/{property_id}/statements/{statement_id}`",
		"parameters": [
			{ "$ref": "#/components/parameters/StatementId" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" }
		],
		"requestBody": requests.ItemStatement,
		"responses": {
			"200": {
				...responses.ItemStatement,
				"description": "A Wikibase Statement. Please note that the value of the ETag header field refers to the subject's revision ID."
			},
			"400": responses.InvalidReplaceStatementInput,
			"403": { "$ref": "#/components/responses/PermissionDenied" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"429": { "$ref": "#/components/responses/RequestLimitReached" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	},
	"patch": {
		"operationId": "patchStatement",
		"tags": [ "statements" ],
		"summary": "Change elements of a single Statement",
		"description": "This endpoint is also accessible through `/entities/items/{item_id}/statements/{statement_id}` and `/entities/properties/{property_id}/statements/{statement_id}`",
		"parameters": [
			{ "$ref": "#/components/parameters/StatementId" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" }
		],
		"requestBody": requests.PatchItemStatement,
		"responses": {
			"200": {
				...responses.ItemStatement,
				"description": "A Wikibase Statement. Please note that the value of the `ETag` header field refers to the subject's revision ID."
			},
			"400": { "$ref": "#/components/responses/InvalidPatch" },
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
		"operationId": "deleteStatement",
		"tags": [ "statements" ],
		"summary": "Delete a single Statement",
		"description": "This endpoint is also accessible through `/entities/items/{item_id}/statements/{statement_id}` and `/entities/properties/{property_id}/statements/{statement_id}`",
		"parameters": [
			{ "$ref": "#/components/parameters/StatementId" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" }
		],
		"requestBody": { "$ref": "#/components/requestBodies/Delete" },
		"responses": {
			"200": responses.StatementDeleted,
			"400": responses.InvalidRemoveStatementInput,
			"403": { "$ref": "#/components/responses/PermissionDenied" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"429": { "$ref": "#/components/responses/RequestLimitReached" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	}
};
