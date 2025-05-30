'use strict';

const requestParts = require( '../../global/request-parts' );
const responseParts = require( '../../global/response-parts' );

module.exports = {
	"post": {
		"operationId": "addItem",
		"tags": [ "items" ],
		"summary": "Create a Wikibase Item",
		"parameters": [
			{ "$ref": "#/components/parameters/Authorization" }
		],
		"requestBody": {
			"description": "Payload containing a Wikibase Item and edit metadata",
			"required": true,
			"content": {
				"application/json": {
					"schema": {
						"allOf": [
							{
								"type": "object",
								"properties": {
									"item": { "$ref": "#/components/schemas/Item" }
								},
								"required": [ "item" ]
							},
							requestParts.MediawikiEdit
						]
					},
					"example": {
						"item": {
							"labels": {
								"en": "Jane Doe",
								"ru": "Джейн Доу"
							},
							"descriptions": {
								"en": "famous person",
								"ru": "известная личность"
							},
							"aliases": {
								"en": [ "Jane M. Doe", "JD" ],
								"ru": [ "Джейн М. Доу" ]
							},
							"statements": {
								"P694": [
									{
										"property": { "id": "P694" },
										"value": { "type": "value", "content": "Q626683" }
									}
								],
								"P476": [
									{
										"property": { "id": "P476" },
										"value": {
											"type": "value",
											"content": {
												"time": "+1986-01-27T00:00:00Z",
												"precision": 11,
												"calendarmodel": "http://www.wikidata.org/entity/Q1985727"
											}
										}
									}
								],
								"P17": [
									{
										"property": { "id": "P17" },
										"value": { "type": "value", "content": "Senior Team Supervisor" },
										"qualifiers": [
											{
												"property": { "id": "P706" },
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
												"parts": [
													{
														"property": { "id": "P709" },
														"value": { "type": "value", "content": "https://news.example.org" }
													}
												]
											}
										]
									}
								]
							},
							"sitelinks": {
								"enwiki": { "title": "Jane Doe" },
								"ruwiki": { "title": "Джейн Доу" }
							}
						},
						"comment": "Create an Item for Jane Doe"
					}
				}
			}
		},
		"responses": {
			"201": { "$ref": "#/components/responses/Item" },
			"400": {
				"description": "The request cannot be processed",
				"content": {
					"application/json": {
						"schema": responseParts.ErrorSchema,
						"examples": {
							"value-too-long": { "$ref": "#/components/examples/ValueTooLongExample" },
							"statement-group-property-id-mismatch": { "$ref": "#/components/examples/StatementGroupPropertyIdMismatch" },
							"referenced-resource-not-found": { "$ref": "#/components/examples/ReferencedResourceNotFoundExample" },
							"invalid-value": { "$ref": "#/components/examples/InvalidValueExample" },
							"missing-field": { "$ref": "#/components/examples/MissingFieldExample" },
							"invalid-key": { "$ref": "#/components/examples/InvalidKeyExample" },
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
			"422": { "$ref": "#/components/responses/DataPolicyViolation" },
			"429": { "$ref": "#/components/responses/RequestLimitReached" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	}
};
