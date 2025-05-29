'use strict';

const responseParts = require( '../../global/response-parts' );

module.exports = {
	"InvalidPatchedItemSitelinks": {
		"description": "Applying the provided JSON Patch results in invalid Sitelinks",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"patch-result-referenced-resource-not-found": { "$ref": "#/components/examples/PatchResultResourceNotFoundExample" },
					"patch-result-invalid-value": { "$ref": "#/components/examples/PatchResultInvalidValueExample" },
					"patch-result-missing-field": { "$ref": "#/components/examples/PatchResultMissingFieldExample" },
					"patch-result-invalid-key": { "$ref": "#/components/examples/PatchResultInvalidKeyExample" },
					"patch-result-modified-read-only-value": { "$ref": "#/components/examples/PatchResultModifiedReadOnlyValue" },
					"data-policy-violation": { "$ref": "#/components/examples/DataPolicyViolationExample" }
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
	"SitelinkDeleted": {
		"description": "The resource was deleted",
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
				"schema": {
					"type": "string"
				},
				"example": "Sitelink deleted"
			}
		}
	},
	"InvalidRemoveSitelinkInput": {
		"description": "The request cannot be processed",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"invalid-path-parameter": { "$ref": "#/components/examples/InvalidPathParameterExample" },
					"invalid-value": { "$ref": "#/components/examples/InvalidValueExample" },
					"value-too-long": { "$ref": "#/components/examples/ValueTooLongExample" }
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
	"InvalidSetSitelinkInput": {
		"description": "The request cannot be processed",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"invalid-path-parameter": { "$ref": "#/components/examples/InvalidPathParameterExample" },
					"invalid-value": { "$ref": "#/components/examples/InvalidValueExample" },
					"missing-field": { "$ref": "#/components/examples/MissingFieldExample" },
					"value-too-long": { "$ref": "#/components/examples/ValueTooLongExample" },
					"referenced-resource-not-found": { "$ref": "#/components/examples/ReferencedResourceNotFoundExample" },
					"resource-too-large": { "$ref": "#/components/examples/ResourceTooLargeExample" }
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
	"InvalidGetSitelinkInput": {
		"description": "The request cannot be processed",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"invalid-path-parameter": { "$ref": "#/components/examples/InvalidPathParameterExample" }
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
	}
};
