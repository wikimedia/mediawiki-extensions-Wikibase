'use strict';

module.exports = {
	"ItemDescription": {
		"description": "Item's description in a specific language",
		"headers": {
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
			"X-Authenticated-User": {
				"description": "Optional username of the user making the request",
				"schema": { "type": "string" }
			}
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
			"X-Authenticated-User": {
				"description": "Optional username of the user making the request",
				"schema": { "type": "string" }
			}
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
			"Content-Language": {
				"description": "Language code of the language in which response is provided",
				"schema": { "type": "string" },
				"required": true
			},
			"X-Authenticated-User": {
				"description": "Optional username of the user making the request",
				"schema": { "type": "string" }
			}
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
