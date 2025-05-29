'use strict';

const requestParts = require( '../../global/request-parts' );

module.exports = {
	"AddAliasesInLanguage": {
		"type": "object",
		"properties": {
			"aliases": {
				"type": "array",
				"items": { "type": "string" }
			},
			...requestParts.MediawikiEdit.properties
		},
		"required": [ "aliases" ],
	},
};
