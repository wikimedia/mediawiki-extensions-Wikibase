'use strict';

const requestParts = require( '../../global/request-parts' );

module.exports = {
	"AddAliasesInLanguage": {
		"allOf": [
			{
				"type": "object",
				"properties": {
					"aliases": {
						"type": "array",
						"items": { "type": "string" }
					}
				},
				"required": [ "aliases" ]
			},
			requestParts.MediawikiEdit
		]
	}
};
