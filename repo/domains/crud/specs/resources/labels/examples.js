'use strict';

module.exports = {
	"PatchItemLabelsExample": {
		"patch": [
			{ "op": "replace", "path": "/en", "value": "Jane Doe" }
		],
		"tags": [],
		"bot": false,
		"comment": "replace English label"
	},
	"PatchPropertyLabelsExample": {
		"patch": [
			{ "op": "replace", "path": "/en", "value": "instance of" }
		],
		"tags": [],
		"bot": false,
		"comment": "replace English label"
	}
};
