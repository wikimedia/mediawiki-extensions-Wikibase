'use strict';

module.exports = {
	"SearchQuery": {
		"in": "query",
		"name": "q",
		"description": "The term to search labels by",
		"required": true,
		"schema": { "type": "string" }
	},
	"SearchLanguage": {
		"in": "query",
		"name": "language",
		"description": "The language to search labels in",
		"required": true,
		"schema": {
			"type": "string",
			"pattern": "^[a-z]{2}[a-z0-9-]*$"
		},
		"example": "en"
	},
	"Limit": {
		"in": "query",
		"name": "limit",
		"description": "The maximum number of results to return",
		"required": false,
		"schema": {
			"type": "integer",
			"minimum": 1,
			"maximum": 500,
			"default": 10
		},
		"example": 20
	},
	"Offset": {
		"in": "query",
		"name": "offset",
		"description": "The index to start showing results from",
		"required": false,
		"schema": {
			"type": "integer",
			"minimum": 0,
			"default": 0
		},
		"example": 4
	}
};
