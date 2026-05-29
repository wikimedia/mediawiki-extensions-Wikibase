'use strict';

// parallel is enabled by default, but can be disabled by setting MOCHA_PARALLEL to 'false'
module.exports = {
	parallel: process.env.MOCHA_PARALLEL !== 'false',
	recursive: true,
	ext: 'Test.js'
};
