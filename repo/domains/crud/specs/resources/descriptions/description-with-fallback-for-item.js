'use strict';

const responses = require( './responses' );

module.exports = {
	"get": {
		"operationId": "getItemDescriptionWithFallback",
		"tags": [ "descriptions" ],
		"summary": "Retrieve an Item's description in a specific language, with language fallback",
		"description": "If a description is defined in the requested language, the API responds with a 200 status code and includes the description in the response payload. If a description only exists in a fallback language, the API returns a 307 status code and provides the location of the description.",
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
				...responses.ItemDescription,
				"description": "Item's description in a specific language. If a description only exists in a fallback language, the API returns a 307 status code and provides its location."
			},
			"304": { "$ref": "#/components/responses/NotModified" },
			"307": responses.DescriptionMovedTemporarily,
			"308": { "$ref": "#/components/responses/MovedPermanently" },
			"400": { "$ref": "#/components/responses/InvalidTermByLanguageInput" },
			"404": { "$ref": "#/components/responses/ResourceNotFound" },
			"412": { "$ref": "#/components/responses/PreconditionFailedError" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	}
};
