'use strict';

const requestParts = require( '../../global/request-parts' );
const examples = require( './examples' );

module.exports = {
	"Property": {
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
	"PatchProperty": {
		"required": true,
		"content": {
			"application/json-patch+json": {
				"schema": requestParts.PatchRequest,
				"example": examples.PatchPropertyExample
			},
			"application/json": {
				"schema": requestParts.PatchRequest,
				"example": examples.PatchPropertyExample
			}
		}
	}
};
