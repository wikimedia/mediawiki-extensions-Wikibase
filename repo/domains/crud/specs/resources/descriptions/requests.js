'use strict';

const requestParts = require( '../../global/request-parts' );

module.exports = {
	"SetDescriptionInLanguage": {
		"allOf": [
			{
				"type": "object",
				"properties": {
					"description": { "type": "string" }
				},
				"required": [ "description" ]
			},
			requestParts.MediawikiEdit
		]
	}
};
