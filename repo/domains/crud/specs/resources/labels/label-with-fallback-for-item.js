'use strict';

const responses = require( './responses' );

module.exports = {
	"get": {
		"operationId": "getItemLabelWithFallback",
		"tags": [ "labels" ],
		"summary": "Retrieve an Item's label in a specific language, with language fallback",
		"description": "If a label is defined in the requested language, the API responds with a 200 status code and includes the label in the response payload. If a label only exists in a fallback language, the API returns a 307 status code and provides the location of the label.",
		"parameters": [
			{ "$ref": "#/components/parameters/ItemId" },
			{ "$ref": "#/components/parameters/LanguageCode" },
			{ "$ref": "#/components/parameters/IfNoneMatch" },
			{ "$ref": "#/components/parameters/IfModifiedSince" },
			{ "$ref": "#/components/parameters/IfMatch" },
			{ "$ref": "#/components/parameters/IfUnmodifiedSince" },
			{ "$ref": "#/components/parameters/Authorization" }
		],
		"responses": {
			"200": {
				...responses.ItemLabel,
				"description": "Item's label in a specific language. If a label only exists in a fallback language, the API returns a 307 status code and provides its location."
			},
			"304": { "$ref": "#/components/responses/NotModified" },
			"307": responses.LabelMovedTemporarily,
			"308": { "$ref": "#/components/responses/MovedPermanently" },
			"400": { "$ref": "#/components/responses/InvalidTermByLanguageInput" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	}
};
