'use strict';

/* eslint-disable quotes */
module.exports = {
	extends: [
		"wikimedia/mocha"
	],
	rules: {
		"no-unused-expressions": "off",
		"prefer-arrow-callback": "off",
		"mocha/no-setup-in-describe": 0,
		"mocha/no-skipped-tests": "error",
	}
};
