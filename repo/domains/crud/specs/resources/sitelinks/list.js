'use strict';

const requestParts = require( '../../global/request-parts' );
const responseParts = require( '../../global/response-parts' );

const PatchSitelinksRequest = {
	schema: requestParts.PatchRequest,
	example: {
		patch: [
			{ op: 'add', path: '/ruwiki/title', value: 'Джейн Доу' }
		],
		tags: [],
		bot: false,
		comment: 'Add sitelink to ruwiki'
	}
};

module.exports = {
	"get": {
		"operationId": "getSitelinks",
		"tags": [ "sitelinks" ],
		"summary": "Retrieve an Item's Sitelinks",
		"parameters": [
			{ "$ref": "#/components/parameters/ItemId" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfModifiedSince" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" },
			{ "$ref": "#/components/parameters/Authorization" }
		],
		"responses": {
			"200": { "$ref": "#/components/responses/Sitelinks" },
			"304": { "$ref": "#/components/responses/NotModified" },
			"308": { "$ref": "#/components/responses/MovedPermanently" },
			"400": { "$ref": "#/components/responses/InvalidEntityIdInput" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	},
	"patch": {
		"operationId": "patchSitelinks",
		"tags": [ "sitelinks" ],
		"summary": "Change an Item's Sitelinks",
		"parameters": [
			{ "$ref": "#/components/parameters/ItemId" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" }
		],
		"requestBody": {
			"required": true,
			"content": {
				"application/json-patch+json": PatchSitelinksRequest,
				"application/json": PatchSitelinksRequest
			}
		},
		"responses": {
			"200": { "$ref": "#/components/responses/Sitelinks" },
			"400": { "$ref": "#/components/responses/InvalidPatch" },
			"403": { "$ref": "#/components/responses/PermissionDenied" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"409": { "$ref": "#/components/responses/CannotApplyItemPatch" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"422": {
				"description": "Applying the provided JSON Patch results in invalid Sitelinks",
				"content": {
					"application/json": {
						"schema": responseParts.ErrorSchema,
						"examples": {
							"patch-result-referenced-resource-not-found": { "$ref": "#/components/examples/PatchResultResourceNotFoundExample" },
							"patch-result-invalid-value": { "$ref": "#/components/examples/PatchResultInvalidValueExample" },
							"patch-result-missing-field": { "$ref": "#/components/examples/PatchResultMissingFieldExample" },
							"patch-result-invalid-key": { "$ref": "#/components/examples/PatchResultInvalidKeyExample" },
							"patch-result-modified-read-only-value": { "$ref": "#/components/examples/PatchResultModifiedReadOnlyValue" },
							"data-policy-violation": { "$ref": "#/components/examples/DataPolicyViolationExample" }
						}
					}
				},
				"headers": {
					"Content-Language": {
						"description": "Language code of the language in which error message is provided",
						"schema": { "type": "string" },
						"required": true
					}
				}
			},
			"429": { "$ref": "#/components/responses/RequestLimitReached" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	}
};
