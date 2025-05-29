'use strict';

const schemaParts = require( './schema-parts' );
const responseParts = require( './response-parts' );

module.exports = {
	"ItemRedirected": {
		"description": "The specified Item was redirected",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"redirected-item": { "$ref": "#/components/examples/RedirectedItemExample" }
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
	"DataPolicyViolation": {
		"description": "The edit request violates data policy",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
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
	"PermissionDenied": {
		"description": "The access to resource was denied",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"permission-denied": { "$ref": "#/components/examples/PermissionDeniedExample" }
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
	"RequestLimitReached": {
		"description": "Too many requests",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"request-limit-reached": {
						"value": {
							"code": "request-limit-reached",
							"message": "Exceeded the limit of actions that can be performed in a given span of time",
							"context": { "reason": "{reason_code}" }
						}
					}
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
	"ResourceNotFound": {
		"description": "The specified resource was not found",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"resource-not-found": { "$ref": "#/components/examples/ResourceNotFoundExample" }
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
	"InvalidEntityIdInput": {
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
	"InvalidRetrieveStatementsInput": {
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
	},
	"InvalidNewStatementInput": {
		"description": "The request cannot be processed",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"invalid-path-parameter": { "$ref": "#/components/examples/InvalidPathParameterExample" },
					"invalid-value": { "$ref": "#/components/examples/InvalidValueExample" },
					"missing-field": { "$ref": "#/components/examples/MissingFieldExample" },
					"value-too-long": { "$ref": "#/components/examples/ValueTooLongExample" },
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
	"InvalidSetLabelInput": {
		"description": "The request cannot be processed",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"invalid-path-parameter": { "$ref": "#/components/examples/InvalidPathParameterExample" },
					"value-too-long": { "$ref": "#/components/examples/ValueTooLongExample" },
					"invalid-value": { "$ref": "#/components/examples/InvalidValueExample" },
					"missing-field": { "$ref": "#/components/examples/MissingFieldExample" },
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
	"InvalidSetDescriptionInput": {
		"description": "The request cannot be processed",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"invalid-path-parameter": { "$ref": "#/components/examples/InvalidPathParameterExample" },
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
	"InvalidRemoveLabelInput": {
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
	"InvalidRemoveDescriptionInput": {
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
	"InvalidAddAliasesInput": {
		"description": "The request cannot be processed",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"invalid-path-parameter": { "$ref": "#/components/examples/InvalidPathParameterExample" },
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
	"InvalidTermByLanguageInput": {
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
	"InvalidPatch": {
		"description": "The provided JSON Patch request is invalid",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"invalid-path-parameter": { "$ref": "#/components/examples/InvalidPathParameterExample" },
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
	"CannotApplyItemPatch": {
		"description": "The provided JSON Patch cannot be applied",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"redirected-item": { "$ref": "#/components/examples/RedirectedItemExample" },
					"patch-test-failed": { "$ref": "#/components/examples/PatchTestFailedExample" },
					"patch-target-not-found": { "$ref": "#/components/examples/PatchTargetNotFoundExample" }
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
	"CannotApplyPropertyPatch": {
		"description": "The provided JSON Patch cannot be applied",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"patch-test-failed": { "$ref": "#/components/examples/PatchTestFailedExample" },
					"patch-target-not-found": { "$ref": "#/components/examples/PatchTargetNotFoundExample" }
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
	"InvalidPatchedItem": {
		"description": "Applying the provided JSON Patch results in an invalid Property",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"patch-result-invalid-value": { "$ref": "#/components/examples/PatchResultInvalidValueExample" },
					"patched-statement-group-property-id-mismatch": { "$ref": "#/components/examples/PatchedStatementGroupPropertyIdMismatchExample" },
					"patch-result-referenced-resource-not-found": { "$ref": "#/components/examples/PatchResultResourceNotFoundExample" },
					"patch-result-missing-field": { "$ref": "#/components/examples/PatchResultMissingFieldExample" },
					"patch-result-invalid-key": { "$ref": "#/components/examples/PatchResultInvalidKeyExample" },
					"patch-result-value-too-long": { "$ref": "#/components/examples/PatchResultValueTooLongExample" },
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
	"InvalidPatchedProperty": {
		"description": "Applying the provided JSON Patch results in an invalid Property",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"patch-result-missing-field": { "$ref": "#/components/examples/PatchResultMissingFieldExample" },
					"patched-statement-group-property-id-mismatch": {
						"$ref": "#/components/examples/PatchedStatementGroupPropertyIdMismatchExample"
					},
					"patch-result-invalid-key": { "$ref": "#/components/examples/PatchResultInvalidKeyExample" },
					"patch-result-invalid-value": { "$ref": "#/components/examples/PatchResultInvalidValueExample" },
					"patch-result-referenced-resource-not-found": { "$ref": "#/components/examples/PatchResultResourceNotFoundExample" },
					"patch-result-value-too-long": { "$ref": "#/components/examples/PatchResultValueTooLongExample" },
					"patch-result-modified-read-only-value": {
						"$ref": "#/components/examples/PatchResultModifiedReadOnlyValue"
					},
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
	"InvalidPatchedLabels": {
		"description": "Applying the provided JSON Patch results in invalid Labels",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"patch-result-invalid-key": { "$ref": "#/components/examples/PatchResultInvalidKeyExample" },
					"patch-result-invalid-value": { "$ref": "#/components/examples/PatchResultInvalidValueExample" },
					"patch-result-value-too-long": { "$ref": "#/components/examples/PatchResultValueTooLongExample" },
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
	"InvalidPatchedDescriptions": {
		"description": "Applying the provided JSON Patch results in invalid descriptions",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"patch-result-invalid-key": { "$ref": "#/components/examples/PatchResultInvalidKeyExample" },
					"patch-result-invalid-value": { "$ref": "#/components/examples/PatchResultInvalidValueExample" },
					"patch-result-value-too-long": { "$ref": "#/components/examples/PatchResultValueTooLongExample" },
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
	"InvalidPatchedAliases": {
		"description": "Applying the provided JSON Patch results in invalid Aliases",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"patch-result-invalid-value": { "$ref": "#/components/examples/PatchResultInvalidValueExample" },
					"patch-result-invalid-key": { "$ref": "#/components/examples/PatchResultInvalidKeyExample" },
					"patch-result-value-too-long": { "$ref": "#/components/examples/PatchResultValueTooLongExample" }
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
	"CannotApplyStatementPatch": {
		"description": "The provided JSON Patch cannot be applied",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"patch-test-failed": { "$ref": "#/components/examples/PatchTestFailedExample" },
					"patch-target-not-found": { "$ref": "#/components/examples/PatchTargetNotFoundExample" }
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
	"InvalidPatchedStatement": {
		"description": "Applying the provided JSON Patch results in an invalid Statement",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"examples": {
					"patch-result-missing-field": { "$ref": "#/components/examples/PatchResultMissingFieldExample" },
					"patch-result-invalid-value": { "$ref": "#/components/examples/PatchResultInvalidValueExample" },
					"patch-result-modified-read-only-value": { "$ref": "#/components/examples/PatchResultModifiedReadOnlyValue" },
					"patch-result-referenced-resource-not-found": { "$ref": "#/components/examples/PatchResultResourceNotFoundExample" }
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
	"PreconditionFailedError": {
		"description": "The condition defined by a conditional request header is not fulfilled"
	},
	"UnexpectedError": {
		"description": "An unexpected error has occurred",
		"content": {
			"application/json": {
				"schema": responseParts.ErrorSchema,
				"example": {
					"code": "unexpected-error",
					"message": "Unexpected Error"
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
	"Item": {
		"description": "A single Wikibase Item",
		"headers": {
			"ETag": { "$ref": "#/components/headers/ETag" },
			"Last-Modified": { "$ref": "#/components/headers/Last-Modified" },
			"X-Authenticated-User": { "$ref": "#/components/headers/X-Authenticated-User" }
		},
		"content": {
			"application/json": {
				"schema": responseParts.ItemResponse,
				"example": {
					"id": "Q24",
					"type": "item",
					"labels": {
						"en": "Jane Doe",
						"ru": "Джейн Доу"
					},
					"descriptions": {
						"en": "famous person",
						"ru": "известная личность"
					},
					"aliases": {
						"en": [
							"Jane M. Doe",
							"JD"
						],
						"ru": [
							"Джейн М. Доу"
						]
					},
					"statements": {
						"P694": [
							{
								"id": "Q24$BB728546-A400-4116-A772-16D54B62AC2B",
								"rank": "normal",
								"property": {
									"id": "P694",
									"data_type": "wikibase-item"
								},
								"value": {
									"type": "value",
									"content": "Q626683"
								},
								"qualifiers": [],
								"references": []
							}
						],
						"P476": [
							{
								"id": "Q24$F3B2F956-B6AB-4984-8D89-BEE0FFFA3385",
								"rank": "normal",
								"property": {
									"id": "P476",
									"data_type": "time"
								},
								"value": {
									"type": "value",
									"content": {
										"time": "+1986-01-27T00:00:00Z",
										"precision": 11,
										"calendarmodel": "http://www.wikidata.org/entity/Q1985727"
									}
								},
								"qualifiers": [],
								"references": []
							}
						],
						"P17": [
							{
								"id": "Q24$9966A1CA-F3F5-4B1D-A534-7CD5953169DA",
								"rank": "normal",
								"property": {
									"id": "P17",
									"data_type": "string"
								},
								"value": {
									"type": "value",
									"content": "Senior Team Supervisor"
								},
								"qualifiers": [
									{
										"property": {
											"id": "P706",
											"data_type": "time"
										},
										"value": {
											"type": "value",
											"content": {
												"time": "+2023-06-13T00:00:00Z",
												"precision": 11,
												"calendarmodel": "http://www.wikidata.org/entity/Q1985727"
											}
										}
									}
								],
								"references": [
									{
										"hash": "7ccd777f870b71a4c5056c7fd2a83a22cc39be6d",
										"parts": [
											{
												"property": {
													"id": "P709",
													"data_type": "url"
												},
												"value": {
													"type": "value",
													"content": "https://news.example.org"
												}
											}
										]
									}
								]
							}
						]
					},
					"sitelinks": {
						"enwiki": {
							"title": "Jane Doe",
							"badges": [],
							"url": "https://enwiki.example.org/wiki/Jane_Doe"
						},
						"ruwiki": {
							"title": "Джейн Доу",
							"badges": [],
							"url": "https://ruwiki.example.org/wiki/Джейн_Доу"
						}
					}
				}
			}
		}
	},
	"Sitelinks": {
		"description": "A list of Sitelinks by Item id",
		"headers": {
			"ETag": { "$ref": "#/components/headers/ETag" },
			"Last-Modified": { "$ref": "#/components/headers/Last-Modified" },
			"X-Authenticated-User": { "$ref": "#/components/headers/X-Authenticated-User" }
		},
		"content": {
			"application/json": {
				"schema": schemaParts.Sitelinks,
				"example": {
					"enwiki": {
						"title": "Jane Doe",
						"badges": [],
						"url": "https://enwiki.example.org/wiki/Jane_Doe"
					},
					"ruwiki": {
						"title": "Джейн Доу",
						"badges": [],
						"url": "https://ruwiki.example.org/wiki/Джейн_Доу"
					}
				}
			}
		}
	},
	"Sitelink": {
		"description": "A Sitelink by Item id",
		"headers": {
			"ETag": { "$ref": "#/components/headers/ETag" },
			"Last-Modified": { "$ref": "#/components/headers/Last-Modified" },
			"X-Authenticated-User": { "$ref": "#/components/headers/X-Authenticated-User" }
		},
		"content": {
			"application/json": {
				"schema": { "$ref": "#/components/schemas/Sitelink" },
				"example": {
					"title": "Jane Doe",
					"badges": [],
					"url": "https://enwiki.example.org/wiki/Jane_Doe"
				}
			}
		}
	},
	"Property": {
		"description": "A single Wikibase Property",
		"headers": {
			"ETag": { "$ref": "#/components/headers/ETag" },
			"Last-Modified": { "$ref": "#/components/headers/Last-Modified" },
			"X-Authenticated-User": { "$ref": "#/components/headers/X-Authenticated-User" }
		},
		"content": {
			"application/json": {
				"schema": responseParts.PropertyResponse,
				"example": {
					"id": "P694",
					"type": "property",
					"data_type": "wikibase-item",
					"labels": {
						"en": "instance of",
						"ru": "это частный случай понятия"
					},
					"descriptions": {
						"en": "the subject is a concrete object (instance) of this class, category, or object group",
						"ru": "данный элемент представляет собой конкретный объект (экземпляр / частный случай) класса, категории."
					},
					"aliases": {
						"en": [
							"is a",
							"is an"
						],
						"ru": [
							"представляет собой",
							"является"
						]
					},
					"statements": {
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
		}
	},
	"NotModified": {
		"description": "The specified resource has not been modified since last provided revision number or date",
		"headers": {
			"ETag": { "$ref": "#/components/headers/ETag" }
		}
	},
	"MovedPermanently": {
		"description": "The specified resource has permanently moved to the indicated location",
		"headers": {
			"Location": {
				"description": "The URL to which the requested resource has been moved",
				"schema": { "type": "string" },
				"required": true
			}
		}
	}
};
