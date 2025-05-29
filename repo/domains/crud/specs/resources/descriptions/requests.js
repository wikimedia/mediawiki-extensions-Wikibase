'use strict';

const requestParts = require( '../../global/request-parts' );

module.exports = {
	"SetDescriptionInLanguage": {
		"type": "object",
		"properties": {
			"description": { "type": "string" },
			...requestParts.MediawikiEdit.properties
		},
		"required": [ "description" ]
	},
};
