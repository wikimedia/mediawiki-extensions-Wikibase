/* eslint no-undef:0 */ // it's confused about "module" and "process" because .eslintrc doesn't extend wikimedia/server

module.exports = {
	parallel: !!process.env.ZUUL_PIPELINE, // run tests in parallel in CI but not locally
	recursive: true,
	ext: 'Test.js'
};
