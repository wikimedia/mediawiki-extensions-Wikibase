'use strict';

/* eslint-disable quote-props, quotes */
module.exports = {
	root: true,
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
		"no-console": "error",
		"template-curly-spacing": 'off',
	},
	overrides: [
		{
			files: [ "*.json" ],
			rules: {
				"max-len": "off",
			}
		}
	]
};
