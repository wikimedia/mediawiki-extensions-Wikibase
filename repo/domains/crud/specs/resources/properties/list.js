'use strict';

const requestParts = require( '../../global/request-parts' );
const responseParts = require( '../../global/response-parts' );

module.exports = {
	"post": {
		"operationId": "addProperty",
		"tags": [ "properties" ],
		"summary": "Create a Wikibase Property",
		"parameters": [
			{ "$ref": "#/components/parameters/Authorization" }
		],
		"requestBody": {
			"description": "Payload containing a Wikibase Property and edit metadata",
			"required": true,
			"content": {
				"application/json": {
					"schema": {
						"allOf": [
							{
								"type": "object",
								"properties": {
									"property": { "$ref": "#/components/schemas/Property" }
								},
								"required": [ "property" ]
							},
							requestParts.MediawikiEdit
						]
					},
					"example": {
						"property": {
							"data_type": "wikibase-item",
							"labels": {
								"en": "instance of",
								"ru": "это частный случай понятия"
							},
							"descriptions": {
								"en": "the subject is a concrete object (instance) of this class, category, or object group",
								"ru": "данный элемент представляет собой конкретный объект (экземпляр / частный случай) класса, категории"
							},
							"aliases": {
								"en": [
									"is a",
									"is an"
								],
								"ru": [
									"представляет собой",
									"является"
								]
							},
							"statements": {
								"P1628": [
									{
										"property": { "id": "P1628" },
										"value": {
											"type": "value",
											"content": "http://www.w3.org/1999/02/22-rdf-syntax-ns#type"
										}
									}
								]
							}
						}
					}
				}
			}
		},
		"responses": {
			"201": { "$ref": "#/components/responses/Property" },
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
