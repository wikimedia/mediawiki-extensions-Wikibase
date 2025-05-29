'use strict';

module.exports = {
	"ItemDescription": {
		"description": "Item's description in a specific language",
		"headers": {
			"ETag": { "$ref": "#/components/headers/ETag" },
			"Last-Modified": { "$ref": "#/components/headers/Last-Modified" },
			"X-Authenticated-User": { "$ref": "#/components/headers/X-Authenticated-User" }
		},
		"content": {
			"application/json": {
				"schema": { "type": "string" },
				"example": "famous person"
			}
		}
	},
	"PropertyDescription": {
		"description": "Property's description in a specific language",
		"headers": {
			"ETag": { "$ref": "#/components/headers/ETag" },
			"Last-Modified": { "$ref": "#/components/headers/Last-Modified" },
			"X-Authenticated-User": { "$ref": "#/components/headers/X-Authenticated-User" }
		},
		"content": {
			"application/json": {
				"schema": { "type": "string" },
				"example": "the subject is a concrete object (instance) of this class, category, or object group"
			}
		}
	},
	"DescriptionDeleted": {
		"description": "The description was deleted",
		"headers": {
			"Content-Language": { "$ref": "#/components/headers/Content-Language" },
			"X-Authenticated-User": { "$ref": "#/components/headers/X-Authenticated-User" }
		},
		"content": {
			"application/json": {
				"schema": { "type": "string" },
				"example": "Description deleted"
			}
		}
	},
	"DescriptionMovedTemporarily": {
		"description": "A description in a fallback language exists at the indicated location",
		"headers": {
			"Location": {
				"description": "The URL to which the requested resource has been moved",
				"schema": { "type": "string" },
				"required": true
			}
		}
	}
};
