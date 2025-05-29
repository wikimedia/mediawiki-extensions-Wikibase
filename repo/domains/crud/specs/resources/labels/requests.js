'use strict';

const requestParts = require( '../../global/request-parts' );
const examples = require( './examples' );

module.exports = {
	"PatchItemLabels": {
		"description": "Payload containing a JSON Patch document to be applied to Labels and edit metadata",
		"required": true,
		"content": {
			"application/json-patch+json": {
				"schema": requestParts.PatchRequest,
				"example": examples.PatchItemLabelsExample
			},
			"application/json": {
				"schema": requestParts.PatchRequest,
				"example": examples.PatchItemLabelsExample
			}
		}
	},
	"PatchPropertyLabels": {
		"description": "Payload containing a JSON Patch document to be applied to Labels and edit metadata",
		"required": true,
		"content": {
			"application/json-patch+json": {
				"schema": requestParts.PatchRequest,
				"example": examples.PatchPropertyLabelsExample
			},
			"application/json": {
				"schema": requestParts.PatchRequest,
				"example": examples.PatchPropertyLabelsExample
			}
		}
	},
	"ItemLabel": {
		"description": "Payload containing an Item label in the specified language and edit metadata",
		"required": true,
		"content": {
			"application/json": {
				"schema": {
					"allOf": [
						{
							"type": "object",
							"properties": {
								"label": { "type": "string" }
							},
							"required": [ "label" ]
						},
						requestParts.MediawikiEdit
					]
				},
				"example": {
					"label": "Jane Doe",
					"tags": [],
					"bot": false,
					"comment": "Update the English label"
				}
			}
		}
	},
	"PropertyLabel": {
		"description": "Payload containing a Property label in the specified language and edit metadata",
		"required": true,
		"content": {
			"application/json": {
				"schema": {
					"allOf": [
						{
							"type": "object",
							"properties": {
								"label": { "type": "string" }
							},
							"required": [ "label" ]
						},
						requestParts.MediawikiEdit
					]
				},
				"example": {
					"label": "instance of",
					"tags": [],
					"bot": false,
					"comment": "Update the English label"
				}
			}
		}
	}
};
