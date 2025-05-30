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
			"200": {
				"description": "The Statements of an Item",
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
						"schema": {
							"type": "object",
							"additionalProperties": {
								"type": "array",
								"items": responses.StatementSchema,
							}
						},
						"example": {
							"P694": [
								{
									"id": "Q24$BB728546-A400-4116-A772-16D54B62AC2B",
									"rank": "normal",
									"property": {
										"id": "P694",
										"data_type": "wikibase-item"
									},
									"value": {
										"type": "value",
										"content": "Q626683"
									},
									"qualifiers": [],
									"references": []
								}
							],
							"P476": [
								{
									"id": "Q24$F3B2F956-B6AB-4984-8D89-BEE0FFFA3385",
									"rank": "normal",
									"property": {
										"id": "P476",
										"data_type": "time"
									},
									"value": {
										"type": "value",
										"content": {
											"time": "+1986-01-27T00:00:00Z",
											"precision": 11,
											"calendarmodel": "http://www.wikidata.org/entity/Q1985727"
										}
									},
									"qualifiers": [],
									"references": []
								}
							],
							"P17": [
								{
									"id": "Q24$9966A1CA-F3F5-4B1D-A534-7CD5953169DA",
									"rank": "normal",
									"property": {
										"id": "P17",
										"data_type": "string"
									},
									"value": {
										"type": "value",
										"content": "Senior Team Supervisor"
									},
									"qualifiers": [
										{
											"property": {
												"id": "P706",
												"data_type": "time"
											},
											"value": {
												"type": "value",
												"content": {
													"time": "+2023-06-13T00:00:00Z",
													"precision": 11,
													"calendarmodel": "http://www.wikidata.org/entity/Q1985727"
												}
											}
										}
									],
									"references": [
										{
											"hash": "7ccd777f870b71a4c5056c7fd2a83a22cc39be6d",
											"parts": [
												{
													"property": {
														"id": "P709",
														"data_type": "url"
													},
													"value": {
														"type": "value",
														"content": "https://news.example.org"
													}
												}
											]
										}
									]
								}
							]
						}
					}
				}
			},
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
