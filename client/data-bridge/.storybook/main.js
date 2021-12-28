module.exports = {
	core: {
		builder: 'webpack5',
	},
	stories: [ '../stories/**/*.stories.js' ],
	addons: [
		'@storybook/addon-actions',
		'@storybook/addon-links',
		'@storybook/addon-a11y',
		'@storybook/addon-docs',
		'@storybook/addon-controls',
	],
};
