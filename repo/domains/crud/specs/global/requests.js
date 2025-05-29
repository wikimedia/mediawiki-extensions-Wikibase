'use strict';

const requestParts = require( './request-parts' );

module.exports = {
	"Delete": {
		"description": "Edit payload containing edit metadata",
		"required": false,
		"content": {
			"application/json": {
				"schema": requestParts.MediawikiEdit,
				"example": {
					"tags": [ ],
					"bot": false,
					"comment": "Example edit using the Wikibase REST API"
				}
			}
		}
	}
};
