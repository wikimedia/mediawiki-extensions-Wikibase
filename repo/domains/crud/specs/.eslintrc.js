'use strict';

module.exports = {
	"rules": { },
	"overrides": [
		{
			"files": [ "*.js" ],
			"rules": {
				"brace-style": [ "warn", "1tbs", { "allowSingleLine": true } ],
				"semi": [ "error", "always", { "omitLastInOneLineBlock": true } ],
				// disable for now - easier to switch from JSON to JS if we aren't fussy about  line length and quotes
				"max-len": "off",
				"quotes": "off",
				"quote-props": "off",
			}
		},
	]
};
