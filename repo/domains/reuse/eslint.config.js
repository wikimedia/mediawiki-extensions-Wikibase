const graphqlPlugin = require( '@graphql-eslint/eslint-plugin' );

const schema = './src/Infrastructure/GraphQL/schema.graphql';

module.exports = [ {
	files: [ schema ],
	languageOptions: {
		parser: graphqlPlugin.parser,
		parserOptions: {
			graphQLConfig: {
				schema
			}
		}
	},
	plugins: {
		'@graphql-eslint': graphqlPlugin
	},
	// Using Object.assign() below because the base eslint config complains about the spread operator and configuring
	// eslint properly for this one file here seems not worth it.
	rules: Object.assign( graphqlPlugin.configs[ 'flat/schema-recommended' ].rules, {
		// disabled because not all our object types (can) have an ID field
		'@graphql-eslint/strict-id-in-types': 'off',

		// disabled because not all our types have a description
		'@graphql-eslint/require-description': 'off',

		// disabled because our enums violate the naming conventions
		'@graphql-eslint/naming-convention': 'off'
	} )
} ];
