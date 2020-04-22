module.exports = {
	plugins: [
		require( 'autoprefixer' ),
		require( 'postcss-prefixwrap' )(
			'.wb-db-app',
			{ // base selector; see App.vue
				ignoredSelectors: [ ':root' ],
			},
		),
	],
};
