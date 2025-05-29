'use strict';

const requests = require( './requests' );
const responses = require( './responses' );

module.exports = {
	"post": {
		"operationId": "addItem",
		"tags": [ "items" ],
		"summary": "Create a Wikibase Item",
		"parameters": [
			{ "$ref": "#/components/parameters/Authorization" }
		],
		"requestBody": requests.Item,
		"responses": {
			"201": { "$ref": "#/components/responses/Item" },
			"400": responses.InvalidNewItemInput,
			"403": { "$ref": "#/components/responses/PermissionDenied" },
			"422": { "$ref": "#/components/responses/DataPolicyViolation" },
			"429": { "$ref": "#/components/responses/RequestLimitReached" },
			"500": { "$ref": "#/components/responses/UnexpectedError" }
		}
	}
};
