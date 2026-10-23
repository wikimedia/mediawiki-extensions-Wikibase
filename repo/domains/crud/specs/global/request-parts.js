'use strict';

const MediawikiEdit = {
	"type": "object",
	"properties": {
		"tags": {
			"type": "array",
			"items": { "type": "string" },
			"default": []
		},
		"bot": {
			"type": "boolean",
			"default": false
		},
		"comment": { "type": "string" }
	}
};

const PatchRequest = {
	"type": "object",
	"properties": {
		"patch": {
			"description": "A JSON Patch document as defined by RFC 6902",
			"type": "array",
			"items": {
				"type": "object",
				"properties": {
					"op": {
						"description": "The operation to perform",
						"type": "string",
						"enum": [ "add", "copy", "move", "remove", "replace", "test" ]
					},
					"path": {
						"description": "A JSON Pointer",
						"type": "string"
					},
					"from": {
						"description": "A JSON Pointer",
						"type": "string"
					},
					"value": {
						"description": "The value to be used within the operation"
					}
				},
				"required": [ "op", "path" ]
			}
		},
		...MediawikiEdit.properties,
	},
	"required": [ "patch" ]
};

const PropertyValuePairRequestRequired = {
	"properties": {
		"property": {
			"required": [ "id" ]
		},
		"value": {
			"required": [ "type" ]
		}
	},
	"required": [ "property", "value" ]
};

const StatementRequestRequired = {
	"allOf": [
		PropertyValuePairRequestRequired,
		{
			"properties": {
				"qualifiers": {
					"items": PropertyValuePairRequestRequired
				},
				"references": {
					"items": {
						"properties": {
							"parts": {
								"items": PropertyValuePairRequestRequired
							}
						},
						"required": [ "parts" ]
					}
				}
			}
		}
	]
};

module.exports = {
	MediawikiEdit,
	PatchRequest,
	StatementRequestRequired,
	shared: { StatementRequestRequired },
};
