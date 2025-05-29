'use strict';

const PropertyValueResponseRequired = {
	"required": [ "property", "value" ],
	"properties": {
		"property": {
			"required": [ "id", "data_type" ]
		},
		"value": {
			"required": [ "type" ]
		}
	}
};

const QualifierResponseRequired = PropertyValueResponseRequired;

const ReferenceResponseRequired = {
	"required": [ "hash", "parts" ],
	"properties": {
		"hash": { "type": "string" },
		"parts": {
			"items": PropertyValueResponseRequired
		}
	}
};

const StatementResponseRequired = {
	"allOf": [
		PropertyValueResponseRequired,
		{
			"required": [ "id", "rank", "qualifiers", "references" ],
			"properties": {
				"qualifiers": {
					"items": QualifierResponseRequired
				},
				"references": {
					"items": ReferenceResponseRequired
				}
			}
		}
	]
};

const ItemResponse = {
	"allOf": [
		{ "$ref": "#/components/schemas/Item" },
		{
			"required": [ "id", "type", "labels", "descriptions", "aliases", "statements", "sitelinks" ],
			"properties": {
				"sitelinks": {
					"additionalProperties": {
						"required": [ "title", "badges", "url" ]
					}
				},
				"statements": {
					"additionalProperties": {
						"items": StatementResponseRequired
					}
				}
			}
		}
	]
};

const PropertyResponse = {
	"allOf": [
		{ "$ref": "#/components/schemas/Property" },
		{
			"required": [ "id", "type", "data_type", "labels", "descriptions", "aliases", "statements" ],
			"properties": {
				"statements": {
					"additionalProperties": {
						"items": StatementResponseRequired
					}
				}
			}
		}
	]
};

const ErrorSchema = {
	"type": "object",
	"properties": {
		"code": { "type": "string" },
		"message": { "type": "string" },
		"context": { "type": "object" }
	},
	"required": [ "code", "message" ]
};

module.exports = {
	ItemResponse,
	PropertyResponse,
	StatementResponseRequired,
	QualifierResponseRequired,
	ReferenceResponseRequired,
	PropertyValueResponseRequired,
	ErrorSchema,
};
