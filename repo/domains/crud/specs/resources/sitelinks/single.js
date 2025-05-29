'use strict';

const requestParts = require( '../../global/request-parts' );
const responseParts = require( '../../global/response-parts' );

module.exports = {
	"get": {
		"operationId": "getSitelink",
		"tags": [ "sitelinks" ],
		"summary": "Retrieve an Item's Sitelink",
		"parameters": [
			{ "$ref": "#/components/parameters/ItemId" },
			{ "$ref": "#/components/parameters/SiteId" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfModifiedSince" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" },
			{ "$ref": "#/components/parameters/Authorization" }
		],
		"responses": {
			"200": { "$ref": "#/components/responses/Sitelink" },
			"304": { "$ref": "#/components/responses/NotModified" },
			"308": { "$ref": "#/components/responses/MovedPermanently" },
			"400": {
				"description": "The request cannot be processed",
				"content": {
					"application/json": {
						"schema": responseParts.ErrorSchema,
						"examples": {
							"invalid-path-parameter": { "$ref": "#/components/examples/InvalidPathParameterExample" }
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
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	},
	"put": {
		"operationId": "setSitelink",
		"tags": [ "sitelinks" ],
		"summary": "Add / Replace an Item's Sitelink",
		"parameters": [
			{ "$ref": "#/components/parameters/ItemId" },
			{ "$ref": "#/components/parameters/SiteId" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfModifiedSince" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" },
			{ "$ref": "#/components/parameters/Authorization" }
		],
		"requestBody": {
			"description": "Payload containing a Wikibase Sitelink object and edit metadata",
			"required": true,
			"content": {
				"application/json": {
					"schema": {
						"allOf": [
							{
								"type": "object",
								"properties": {
									"sitelink": { "$ref": "#/components/schemas/Sitelink" }
								},
								"required": [ "sitelink" ]
							},
							requestParts.MediawikiEdit
						]
					},
					"example": {
						"sitelink": { "title": "Jane Doe", "badges": [] },
						"tags": [],
						"bot": false,
						"comment": "Add enwiki sitelink"
					}
				}
			}
		},
		"responses": {
			"200": {
				"$ref": "#/components/responses/Sitelink",
				"description": "The updated Sitelink"
			},
			"201": {
				"$ref": "#/components/responses/Sitelink",
				"description": "The newly added Sitelink"
			},
			"400": {
				"description": "The request cannot be processed",
				"content": {
					"application/json": {
						"schema": responseParts.ErrorSchema,
						"examples": {
							"invalid-path-parameter": { "$ref": "#/components/examples/InvalidPathParameterExample" },
							"invalid-value": { "$ref": "#/components/examples/InvalidValueExample" },
							"missing-field": { "$ref": "#/components/examples/MissingFieldExample" },
							"value-too-long": { "$ref": "#/components/examples/ValueTooLongExample" },
							"referenced-resource-not-found": { "$ref": "#/components/examples/ReferencedResourceNotFoundExample" },
							"resource-too-large": { "$ref": "#/components/examples/ResourceTooLargeExample" }
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
			"403": { "$ref": "#/components/responses/PermissionDenied" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"409": { "$ref": "#/components/responses/ItemRedirected" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"422": { "$ref": "#/components/responses/DataPolicyViolation" },
			"429": { "$ref": "#/components/responses/RequestLimitReached" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	},
	"delete": {
		"operationId": "deleteSitelink",
		"tags": [ "sitelinks" ],
		"summary": "Delete an Item's Sitelink",
		"parameters": [
			{ "$ref": "#/components/parameters/ItemId" },
			{ "$ref": "#/components/parameters/SiteId" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfModifiedSince" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" },
			{ "$ref": "#/components/parameters/Authorization" }
		],
		"requestBody": { "$ref": "#/components/requestBodies/Delete" },
		"responses": {
			"200": {
				"description": "The resource was deleted",
				"headers": {
					"Content-Language": { "$ref": "#/components/headers/Content-Language" },
					"X-Authenticated-User": { "$ref": "#/components/headers/X-Authenticated-User" }
				},
				"content": {
					"application/json": {
						"schema": { "type": "string" },
						"example": "Sitelink deleted"
					}
				}
			},
			"400": {
				"description": "The request cannot be processed",
				"content": {
					"application/json": {
						"schema": responseParts.ErrorSchema,
						"examples": {
							"invalid-path-parameter": { "$ref": "#/components/examples/InvalidPathParameterExample" },
							"invalid-value": { "$ref": "#/components/examples/InvalidValueExample" },
							"value-too-long": { "$ref": "#/components/examples/ValueTooLongExample" }
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
			"403": { "$ref": "#/components/responses/PermissionDenied" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"409": { "$ref": "#/components/responses/ItemRedirected" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"429": { "$ref": "#/components/responses/RequestLimitReached" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	}
};
