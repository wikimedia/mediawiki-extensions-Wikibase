module.exports = {
	extends: [
		'wikimedia/node',
	],
	env: {
		node: true,
	},
	rules: {
		'no-console': 'off',
		'mediawiki/valid-package-file-require': 'off',
	},
}
