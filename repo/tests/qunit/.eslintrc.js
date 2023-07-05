module.exports = {
	extends: [
		'wikimedia/qunit',
		'../../../.eslintrc.js'
	],
	globals: {
		sinon: false
	},
	rules: {
		'qunit/resolve-async': 'off'
	}
};
