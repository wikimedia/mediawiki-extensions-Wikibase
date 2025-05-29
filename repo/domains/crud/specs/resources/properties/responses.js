'use strict';

const responseParts = require( '../../global/response-parts' );

module.exports = {
	"InvalidNewPropertyInput": {
		"description": "The request cannot be processed",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"value-too-long": { "$ref": "#/components/examples/ValueTooLongExample" },
					"statement-group-property-id-mismatch": { "$ref": "#/components/examples/StatementGroupPropertyIdMismatch" },
					"referenced-resource-not-found": { "$ref": "#/components/examples/ReferencedResourceNotFoundExample" },
					"invalid-value": { "$ref": "#/components/examples/InvalidValueExample" },
					"missing-field": { "$ref": "#/components/examples/MissingFieldExample" },
					"invalid-key": { "$ref": "#/components/examples/InvalidKeyExample" },
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
	"InvalidGetPropertyInput": {
		"description": "The request cannot be processed",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"invalid-path-parameter": { "$ref": "#/components/examples/InvalidPathParameterExample" },
					"invalid-query-parameter": { "$ref": "#/components/examples/InvalidQueryParameterExample" }
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
