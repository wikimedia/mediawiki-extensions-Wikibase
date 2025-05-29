'use strict';

const requestParts = require( '../../global/request-parts' );
const examples = require( './examples' );

module.exports = {
	"Item": {
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
	"PatchItem": {
		"required": true,
		"content": {
			"application/json-patch+json": {
				"schema": requestParts.PatchRequest,
				"example": examples.PatchItemExample
			},
			"application/json": {
				"schema": requestParts.PatchRequest,
				"example": examples.PatchItemExample
			}
		}
	}
};
