module.exports = {
	plugins: [
		'jest-formatting',
	],
	env: {
		jest: true,
	},
	rules: {
		'@typescript-eslint/no-explicit-any': 'off',
		'@typescript-eslint/no-non-null-assertion': 'off',
		'@typescript-eslint/no-object-literal-type-assertion': 'off',
		'@typescript-eslint/no-empty-function': 'off',
		'@typescript-eslint/ban-ts-comment': 'off',
		'@typescript-eslint/ban-types': 'off',

		'jest-formatting/padding-around-describe-blocks': 2,
		'jest-formatting/padding-around-test-blocks': 2,
	},
};
