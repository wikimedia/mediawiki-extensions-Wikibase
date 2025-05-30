'use strict';

const schemaParts = require( './schema-parts' );

module.exports = {
	"Item": {
		"type": "object",
		"properties": {
			"id": {
				"type": "string",
				"readOnly": true
			},
			"type": {
				"type": "string",
				"const": "item",
				"readOnly": true
			},
			"labels": { "$ref": "#/components/schemas/Labels" },
			"descriptions": { "$ref": "#/components/schemas/Descriptions" },
			"aliases": { "$ref": "#/components/schemas/Aliases" },
			"sitelinks": schemaParts.Sitelinks,
			"statements": {
				"type": "object",
				"additionalProperties": {
					"type": "array",
					"items": { "$ref": "#/components/schemas/Statement" }
				}
			}
		}
	},
	"Property": {
		"type": "object",
		"properties": {
			"id": {
				"type": "string",
				"readOnly": true
			},
			"type": {
				"type": "string",
				"const": "property",
				"readOnly": true
			},
			"data_type": {
				"type": "string"
			},
			"labels": { "$ref": "#/components/schemas/Labels" },
			"descriptions": { "$ref": "#/components/schemas/Descriptions" },
			"aliases": { "$ref": "#/components/schemas/Aliases" },
			"statements": {
				"type": "object",
				"additionalProperties": {
					"type": "array",
					"items": { "$ref": "#/components/schemas/Statement" }
				}
			}
		},
		"required": [ "data_type" ]
	},
	"Labels": {
		"type": "object",
		"additionalProperties": {
			"type": "string"
		}
	},
	"Descriptions": {
		"type": "object",
		"additionalProperties": {
			"type": "string"
		}
	},
	"Aliases": {
		"type": "object",
		"additionalProperties": {
			"type": "array",
			"items": { "type": "string" }
		}
	},
	"Sitelink": {
		"type": "object",
		"properties": {
			"title": {
				"type": "string"
			},
			"badges": {
				"type": "array",
				"items": { "type": "string" }
			},
			"url": {
				"type": "string",
				"readOnly": true
			}
		},
		"required": [ "title" ]
	},
	"Statement": {
		"type": "object",
		"properties": {
			"id": {
				"description": "The globally unique identifier for this Statement",
				"type": "string",
				"readOnly": true
			},
			"rank": {
				"description": "The rank of the Statement",
				"type": "string",
				"enum": [ "deprecated", "normal", "preferred" ],
				"default": "normal"
			},
			...schemaParts.PropertyValuePair.properties,
			"qualifiers": {
				"type": "array",
				"items": schemaParts.PropertyValuePair,
				"default": []
			},
			"references": {
				"type": "array",
				"items": {
					"type": "object",
					"properties": {
						"hash": {
							"description": "Hash of the Reference",
							"type": "string",
							"readOnly": true
						},
						"parts": {
							"type": "array",
							"items": schemaParts.PropertyValuePair,
						}
					}
				},
				"default": []
			}
		}
	},
};
