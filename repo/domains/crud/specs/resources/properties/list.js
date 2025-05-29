'use strict';

const requests = require( './requests' );
const responses = require( './responses' );

module.exports = {
	"post": {
		"operationId": "addProperty",
		"tags": [ "properties" ],
		"summary": "Create a Wikibase Property",
		"parameters": [
			{ "$ref": "#/components/parameters/Authorization" }
		],
		"requestBody": requests.Property,
		"responses": {
			"201": { "$ref": "#/components/responses/Property" },
			"400": responses.InvalidNewPropertyInput,
			"403": { "$ref": "#/components/responses/PermissionDenied" },
			"422": { "$ref": "#/components/responses/DataPolicyViolation" },
			"429": { "$ref": "#/components/responses/RequestLimitReached" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	}
};
