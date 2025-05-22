'use strict';

/* eslint-disable quotes */
module.exports = {
	extends: [
		"wikimedia/mocha"
	],
	rules: {
		"mocha/no-setup-in-describe": 0,
		"mocha/no-skipped-tests": "error",
		"n/no-missing-require": "off"
	}
};
