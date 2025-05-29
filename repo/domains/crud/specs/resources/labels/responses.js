'use strict';

module.exports = {
	"ItemLabel": {
		"description": "A label in a specific language",
		"headers": {
			"ETag": { "$ref": "#/components/headers/ETag" },
			"Last-Modified": { "$ref": "#/components/headers/Last-Modified" },
			"X-Authenticated-User": { "$ref": "#/components/headers/X-Authenticated-User" }
		},
		"content": {
			"application/json": {
				"schema": { "type": "string" },
				"example": "Jane Doe"
			}
		}
	},
	"PropertyLabel": {
		"description": "A label in a specific language",
		"headers": {
			"ETag": { "$ref": "#/components/headers/ETag" },
			"Last-Modified": { "$ref": "#/components/headers/Last-Modified" },
			"X-Authenticated-User": { "$ref": "#/components/headers/X-Authenticated-User" }
		},
		"content": {
			"application/json": {
				"schema": { "type": "string" },
				"example": "instance of"
			}
		}
	},
	"LabelDeleted": {
		"description": "The resource was deleted",
		"headers": {
			"Content-Language": { "$ref": "#/components/headers/Content-Language" },
			"X-Authenticated-User": { "$ref": "#/components/headers/X-Authenticated-User" }
		},
		"content": {
			"application/json": {
				"schema": { "type": "string" },
				"example": "Label deleted"
			}
		}
	},
	"LabelMovedTemporarily": {
		"description": "A label in a fallback language exists at the indicated location",
		"headers": {
			"Location": {
				"description": "The URL to which the requested resource has been moved",
				"schema": { "type": "string" },
				"required": true
			}
		}
	}
};
