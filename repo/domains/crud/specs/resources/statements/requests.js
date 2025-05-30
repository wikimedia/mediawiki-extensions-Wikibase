'use strict';

const requestParts = require( '../../global/request-parts' );
const examples = require( './examples' );

const PropertyValuePairRequestRequired = {
	"properties": {
		"property": {
			"required": [ "id" ]
		},
		"value": {
			"required": [ "type" ]
		}
	},
	"required": [ "property", "value" ]
};

const StatementRequestRequired = {
	"allOf": [
		PropertyValuePairRequestRequired,
		{
			"properties": {
				"qualifiers": {
					"items": PropertyValuePairRequestRequired
				},
				"references": {
					"items": {
						"properties": {
							"parts": {
								"items": PropertyValuePairRequestRequired
							}
						},
						"required": [ "parts" ]
					}
				}
			}
		}
	]
};

const StatementRequest = {
	"allOf": [
		{
			"type": "object",
			"properties": {
				"statement": {
					"allOf": [
						{ "$ref": "#/components/schemas/Statement" },
						StatementRequestRequired
					]
				}
			},
			"required": [ "statement" ]
		},
		requestParts.MediawikiEdit
	]
};

const PatchItemStatementContent = {
	"schema": requestParts.PatchRequest,
	"example": examples.PatchItemStatementRequest,
};

const PatchPropertyStatementContent = {
	"schema": requestParts.PatchRequest,
	"example": examples.PatchPropertyStatementRequest,
};

module.exports = {
	"ItemStatement": {
		"description": "Payload containing a Wikibase Statement object and edit metadata",
		"required": true,
		"content": {
			"application/json": {
				"schema": StatementRequest,
				"example": {
					"statement": {
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
					},
					"tags": [],
					"bot": false,
					"comment": "Example edit using the Wikibase REST API"
				}
			}
		}
	},
	"PatchItemStatement": {
		"description": "Payload containing a JSON Patch document to be applied to the Statement and edit metadata",
		"required": true,
		"content": {
			"application/json-patch+json": PatchItemStatementContent,
			"application/json": PatchItemStatementContent,
		}
	},
	"PropertyStatement": {
		"description": "Payload containing a Wikibase Statement object and edit metadata",
		"required": true,
		"content": {
			"application/json": {
				"schema": StatementRequest,
				"example": {
					"statement": {
						"property": { "id": "P1628" },
						"value": {
							"type": "value",
							"content": "http://www.w3.org/1999/02/22-rdf-syntax-ns#type"
						}
					},
					"tags": [],
					"bot": false,
					"comment": "Example edit using the Wikibase REST API"
				}
			}
		}
	},
	"PatchPropertyStatement": {
		"description": "Payload containing a JSON Patch document to be applied to the Statement and edit metadata",
		"required": true,
		"content": {
			"application/json-patch+json": PatchPropertyStatementContent,
			"application/json": PatchPropertyStatementContent,
		}
	}
};
