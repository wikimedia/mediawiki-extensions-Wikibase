module.exports = function ( config ) {
	config.set( {
		frameworks: [ 'qunit' ],

		files: [
			'node_modules/jquery/dist/jquery.js',

			// TODO: install JS dependencies using npm
			'node_modules/wikibase-data-values/lib/util/util.inherit.js',
			'node_modules/wikibase-data-values/src/dataValues.js',
			'node_modules/wikibase-data-values/src/DataValue.js',
			'node_modules/wikibase-data-values/src/values/StringValue.js',
			'node_modules/wikibase-data-values/src/values/UnDeserializableValue.js',
			'node_modules/wikibase-data-model/src/__namespace.js',
			'node_modules/wikibase-data-model/src/GroupableCollection.js',
			'node_modules/wikibase-data-model/src/Group.js',
			'node_modules/wikibase-data-model/src/Snak.js',
			'node_modules/wikibase-data-model/src/Set.js',
			'node_modules/wikibase-data-model/src/List.js',
			'node_modules/wikibase-data-model/src/*.js',

			'src/__namespace.js',
			'src/Serializers/Serializer.js',
			'src/Serializers/*.js',
			'src/SerializerFactory.js',
			'src/StrategyProvider.js',
			'src/Deserializers/Deserializer.js',
			'src/Deserializers/*.js',
			'src/DeserializerFactory.js',
			'tests/MockEntity.js',
			'tests/MockEntity.tests.js',
			'tests/StrategyProvider.tests.js',
			'tests/SerializerFactory.tests.js',
			'tests/Serializers/*.js',
			'tests/Deserializers/*.js',
			'tests/DeserializerFactory.tests.js'
		],

		port: 9876,

		logLevel: config.LOG_INFO,
		browsers: [ 'PhantomJS' ]
	} );
};
