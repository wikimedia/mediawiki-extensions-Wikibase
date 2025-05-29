'use strict';

const requestParts = require( '../../global/request-parts' );
const examples = require( './examples' );

module.exports = {
	"PatchSitelinks": {
		"required": true,
		"content": {
			"application/json-patch+json": {
				"schema": requestParts.PatchRequest,
				"example": examples.PatchSitelinksExample
			},
			"application/json": {
				"schema": requestParts.PatchRequest,
				"example": examples.PatchSitelinksExample
			}
		}
	},
	"Sitelink": {
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
					"sitelink": {
						"title": "Jane Doe",
						"badges": []
					},
					"tags": [],
					"bot": false,
					"comment": "Add enwiki sitelink"
				}
			}
		}
	}
};
