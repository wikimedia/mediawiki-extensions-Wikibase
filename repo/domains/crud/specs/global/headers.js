'use strict';

module.exports = {
	"Content-Language": {
		"description": "Language code of the language in which response is provided",
		"schema": { "type": "string" },
		"required": true
	},
	"ETag": {
		"description": "Last entity revision number",
		"schema": { "type": "string" },
		"required": true
	},
	"Last-Modified": {
		"description": "Last modified date",
		"schema": { "type": "string" },
		"required": true
	},
	"Location": {
		"description": "The URI of the newly created Statement",
		"schema": { "type": "string" },
		"required": true
	},
	"X-Authenticated-User": {
		"description": "Optional username of the user making the request",
		"schema": { "type": "string" }
	}
};
