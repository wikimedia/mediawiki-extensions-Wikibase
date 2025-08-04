'use-strict';

module.exports = {
	rules: {
		// allow {{ 'mustaches' }} that eslint-plugin-vue consideres useless,
		// if they include a comment to explain themselves
		'vue/no-useless-mustaches': [ 'error', {
			ignoreIncludesComment: true
		} ],
		// disable this rule for now, as we use v-html= regularly;
		// can perhaps be reenabled with an ignorePattern later
		'vue/no-v-html': 'off'
	}
};
