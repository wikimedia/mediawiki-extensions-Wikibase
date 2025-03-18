'use strict';

const DisplayLabel = {
	"type": [ "object", "null" ],
	"properties": {
		"language": { "type": "string" },
		"value": { "type": "string" }
	},
	"additionalProperties": false,
	"required": [ "language", "value" ],
};

const Description = {
	"type": [ "object", "null" ],
	"properties": {
		"language": { "type": "string" },
		"value": { "type": "string" }
	},
	"additionalProperties": false,
	"required": [ "language", "value" ],
};

const Match = {
	"type": "object",
	"properties": {
		"type": { "type": "string" },
		"language": { "type": "string" },
		"text": { "type": "string" }
	},
	"additionalProperties": false,
	"required": [ "type", "text" ],
};

const SearchItemResult = {
	"type": "object",
	"properties": {
		"id": {
			"type": "string",
			"pattern": "^Q[1-9]\\d{0,9}$"
		},
		"display-label": DisplayLabel,
		"description": Description,
		"match": Match
	},
	"additionalProperties": false,
	"required": [ "id", "display-label", "description", "match" ],
};

const SearchPropertyResult = {
	"type": "object",
	"properties": {
		"id": {
			"type": "string",
			"pattern": "^P[1-9]\\d{0,9}$"
		},
		"display-label": DisplayLabel,
		"description": Description,
		"match": Match
	},
	"additionalProperties": false,
	"required": [ "id", "display-label", "description", "match" ],
};

const SearchItemResultList = {
	"type": "object",
	"properties": {
		"results": {
			"type": "array",
			"items": SearchItemResult,
		}
	},
	"additionalProperties": false,
	"required": [ "results" ],
};

const SearchPropertyResultList = {
	"type": "object",
	"properties": {
		"results": {
			"type": "array",
			"items": SearchPropertyResult,
		}
	},
	"additionalProperties": false,
	"required": [ "results" ],
};

module.exports = {
	SearchItemResultList,
	SearchPropertyResultList,
};
