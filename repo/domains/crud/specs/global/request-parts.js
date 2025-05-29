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

module.exports = {
	MediawikiEdit,
	PatchRequest,
};
