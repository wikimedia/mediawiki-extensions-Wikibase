'use strict';

const responseParts = require( '../../global/response-parts' );
const examples = require( './examples' );

const PropertyValueSchemaRequired = {
	"properties": {
		"property": {
			"required": [ "id", "data_type" ]
		},
		"value": {
			"required": [ "type" ]
		}
	},
	"required": [ "property", "value" ]
};

const StatementSchemaRequired = {
	"allOf": [
		PropertyValueSchemaRequired,
		{
			"properties": {
				"qualifiers": {
					"items": PropertyValueSchemaRequired
				},
				"references": {
					"items": {
						"properties": {
							"hash": { "type": "string" },
							"parts": {
								"items": PropertyValueSchemaRequired
							}
						},
						"required": [ "hash", "parts" ]
					}
				}
			},
			"required": [ "id", "rank", "qualifiers", "references" ]
		}
	]
};

const StatementSchema = {
	"allOf": [
		{ "$ref": "#/components/schemas/Statement" },
		StatementSchemaRequired
	]
};

module.exports = {
	StatementSchema,
	"InvalidRetrieveStatementInput": {
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
	},
	"InvalidReplaceStatementInput": {
		"description": "The request cannot be processed",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"invalid-path-parameter": { "$ref": "#/components/examples/InvalidPathParameterExample" },
					"invalid-value": { "$ref": "#/components/examples/InvalidValueExample" },
					"missing-field": { "$ref": "#/components/examples/MissingFieldExample" },
					"value-too-long": { "$ref": "#/components/examples/ValueTooLongExample" },
					"cannot-modify-read-only-value": { "$ref": "#/components/examples/CannotModifyReadOnlyValue" },
					"resource-too-large": { "$ref": "#/components/examples/ResourceTooLargeExample" },
					"referenced-resource-not-found": { "$ref": "#/components/examples/ReferencedResourceNotFoundExample" }
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
	"InvalidRemoveStatementInput": {
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
	"ItemStatement": {
		"description": "A Wikibase Statement. Please note that the value of the `ETag` header field refers to the Item's revision ID.",
		"headers": {
			"ETag": { "$ref": "#/components/headers/ETag" },
			"Last-Modified": { "$ref": "#/components/headers/Last-Modified" },
			"X-Authenticated-User": { "$ref": "#/components/headers/X-Authenticated-User" }
		},
		"content": {
			"application/json": {
				"schema": StatementSchema,
				"example": examples.ItemStatementResponse
			}
		}
	},
	"InvalidRetrieveItemStatementInput": {
		"description": "The request cannot be processed",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"invalid-path-parameter": { "$ref": "#/components/examples/InvalidPathParameterExample" },
					"item-statement-id-mismatch": { "$ref": "#/components/examples/ItemStatementIdMismatchExample" }
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
	"InvalidReplaceItemStatementInput": {
		"description": "The request cannot be processed",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"invalid-path-parameter": { "$ref": "#/components/examples/InvalidPathParameterExample" },
					"item-statement-id-mismatch": { "$ref": "#/components/examples/ItemStatementIdMismatchExample" },
					"invalid-value": { "$ref": "#/components/examples/InvalidValueExample" },
					"missing-field": { "$ref": "#/components/examples/MissingFieldExample" },
					"value-too-long": { "$ref": "#/components/examples/ValueTooLongExample" },
					"cannot-modify-read-only-value": { "$ref": "#/components/examples/CannotModifyReadOnlyValue" },
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
	"InvalidRemoveItemStatementInput": {
		"description": "The request cannot be processed",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"invalid-path-parameter": { "$ref": "#/components/examples/InvalidPathParameterExample" },
					"item-statement-id-mismatch": { "$ref": "#/components/examples/ItemStatementIdMismatchExample" },
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
	"InvalidItemStatementPatch": {
		"description": "The provided JSON Patch is invalid",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"invalid-path-parameter": { "$ref": "#/components/examples/InvalidPathParameterExample" },
					"item-statement-id-mismatch": { "$ref": "#/components/examples/ItemStatementIdMismatchExample" },
					"invalid-value": { "$ref": "#/components/examples/InvalidValueExample" },
					"missing-field": { "$ref": "#/components/examples/MissingFieldExample" },
					"value-too-long": { "$ref": "#/components/examples/ValueTooLongExample" },
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
	"PropertyStatement": {
		"description": "A Wikibase Statement. Please note that the value of the `ETag` header field refers to the Property's revision ID.",
		"headers": {
			"ETag": { "$ref": "#/components/headers/ETag" },
			"Last-Modified": { "$ref": "#/components/headers/Last-Modified" },
			"X-Authenticated-User": { "$ref": "#/components/headers/X-Authenticated-User" }
		},
		"content": {
			"application/json": {
				"schema": StatementSchema,
				"example": examples.PropertyStatementResponse
			}
		}
	},
	"PropertyStatements": {
		"description": "The Statements of a Property",
		"headers": {
			"ETag": { "$ref": "#/components/headers/ETag" },
			"Last-Modified": { "$ref": "#/components/headers/Last-Modified" },
			"X-Authenticated-User": { "$ref": "#/components/headers/X-Authenticated-User" }
		},
		"content": {
			"application/json": {
				"schema": {
					"type": "object",
					"additionalProperties": {
						"type": "array",
						"items": StatementSchema
					}
				},
				"example": {
					"P1628": [
						{
							"id": "P694$B4C349A2-C504-4FC5-B7D5-8B781C719D71",
							"rank": "normal",
							"property": {
								"id": "P1628",
								"data_type": "url"
							},
							"value": {
								"type": "value",
								"content": "http://www.w3.org/1999/02/22-rdf-syntax-ns#type"
							},
							"qualifiers": [],
							"references": []
						}
					]
				}
			}
		}
	},
	"InvalidRetrievePropertyStatementInput": {
		"description": "The request cannot be processed",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"invalid-path-parameter": { "$ref": "#/components/examples/InvalidPathParameterExample" },
					"property-statement-id-mismatch": { "$ref": "#/components/examples/PropertyStatementIdMismatchExample" }
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
	"InvalidReplacePropertyStatementInput": {
		"description": "The request cannot be processed",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"invalid-path-parameter": { "$ref": "#/components/examples/InvalidPathParameterExample" },
					"property-statement-id-mismatch": { "$ref": "#/components/examples/PropertyStatementIdMismatchExample" },
					"invalid-value": { "$ref": "#/components/examples/InvalidValueExample" },
					"missing-field": { "$ref": "#/components/examples/MissingFieldExample" },
					"value-too-long": { "$ref": "#/components/examples/ValueTooLongExample" },
					"cannot-modify-read-only-value": { "$ref": "#/components/examples/CannotModifyReadOnlyValue" },
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
	"InvalidRemovePropertyStatementInput": {
		"description": "The request cannot be processed",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"invalid-path-parameter": { "$ref": "#/components/examples/InvalidPathParameterExample" },
					"property-statement-id-mismatch": { "$ref": "#/components/examples/PropertyStatementIdMismatchExample" },
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
	"InvalidPropertyStatementPatch": {
		"description": "The provided JSON Patch is invalid",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"invalid-path-parameter": { "$ref": "#/components/examples/InvalidPathParameterExample" },
					"property-statement-id-mismatch": { "$ref": "#/components/examples/PropertyStatementIdMismatchExample" },
					"invalid-value": { "$ref": "#/components/examples/InvalidValueExample" },
					"missing-field": { "$ref": "#/components/examples/MissingFieldExample" },
					"value-too-long": { "$ref": "#/components/examples/ValueTooLongExample" },
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
	"StatementDeleted": {
		"description": "The requested Statement was deleted",
		"headers": {
			"Content-Language": { "$ref": "#/components/headers/Content-Language" },
			"X-Authenticated-User": { "$ref": "#/components/headers/X-Authenticated-User" }
		},
		"content": {
			"application/json": {
				"schema": { "type": "string" },
				"example": "Statement deleted"
			}
		}
	}
};
