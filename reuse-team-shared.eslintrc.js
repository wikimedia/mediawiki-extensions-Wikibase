'use strict';

/* eslint-disable quote-props, quotes */
module.exports = {
	extends: [
		"wikimedia",
		"wikimedia/node",
		"wikimedia/language/es2022"
	],
	env: {
		"node": true
	},
	rules: {
		"camelcase": "off",
		"comma-dangle": "off",
		"max-len": [ "warn", { code: 130 } ],
		"no-implicit-coercion": [ "error", { disallowTemplateShorthand: true } ],
		"template-curly-spacing": "off"
	},
	overrides: [
		{
			files: [ "*.json" ],
			parser: "eslint-plugin-json-es",
			extends: "plugin:eslint-plugin-json-es/recommended",
			rules: {
				"max-len": "off"
			}
		}
	]
};
