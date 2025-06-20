'use strict';

const schemaParts = require( './schema-parts' );

module.exports = {
	"BadRequest": {
		"description": "The request cannot be processed",
		"content": {
			"application/json": {
				"schema": {
					"type": "object",
					"properties": {
						"code": { "type": "string" },
						"message": { "type": "string" },
						"context": { "type": "object" }
					},
					"required": [ "code", "message" ],
					"additionalProperties": false
				},
				"examples": {
					"invalid-query-parameter": {
						"value": {
							"code": "invalid-query-parameter",
							"message": "Invalid query parameter: '{query_parameter}'",
							"context": { "parameter": "{query_parameter}" }
						}
					}
				}
			}
		},
		"headers": {
			"Content-Language": {
				"description": "Language code of the language in which error message is provided",
				"schema": { "type": "string" },
				"required": true
			}
		}
	},
	"SearchItemSuccess": {
		"description": "A list of search results",
		"content": {
			"application/json": {
				"schema": schemaParts.SearchItemResultList,
				"example": {
					"results": [
						{
							"id": "Q123",
							"display-label": { "language": "en", "value": "potato" },
							"description": { "language": "en", "value": "staple food" },
							"match": { "type": "label", "language": "en", "text": "potato" }
						},
						{
							"id": "Q234",
							"display-label": { "language": "en", "value": "potato" },
							"description": { "language": "en", "value": "species of plant" },
							"match": { "type": "label", "language": "en", "text": "potato" }
						}
					]
				}
			}
		}
	},
	"SearchPropertySuccess": {
		"description": "A list of search results",
		"content": {
			"application/json": {
				"schema": schemaParts.SearchPropertyResultList,
				"example": {
					"results": [
						{
							"id": "P123",
							"display-label": { "language": "en", "value": "taxon name" },
							"description": { "language": "en", "value": "scientific name of a taxon" },
							"match": { "type": "label", "language": "en", "text": "taxon" }
						},
						{
							"id": "P234",
							"display-label": { "language": "en", "value": "taxon rank" },
							"description": { "language": "en", "value": "level in a taxonomic hierarchy" },
							"match": { "type": "label", "language": "en", "text": "taxon" }
						}
					]
				}
			}
		}
	},
	"SuggestItemSuccess": {
		"description": "A list of search results",
		"content": {
			"application/json": {
				"schema": schemaParts.SearchItemResultList,
				"example": {
					"results": [
						{
							"id": "Q456",
							"display-label": { "language": "en", "value": "drinking water" },
							"description": { "language": "en", "value": "water safe for consumption" },
							"match": { "type": "alias", "language": "en", "text": "potable water" }
						},
						{
							"id": "Q123",
							"display-label": { "language": "en", "value": "potato" },
							"description": { "language": "en", "value": "staple food" },
							"match": { "type": "label", "language": "en", "text": "potato" }
						}
					]
				}
			}
		}
	},
};
