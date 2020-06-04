module.exports = {
	extends: [
		'wikimedia/node',
	],
	env: {
		es6: true,
		node: true,
	},
	rules: {
		'compat/compat': 'off',
		'no-console': 'off',
		'mediawiki/valid-package-file-require': 'off',
	},
}
