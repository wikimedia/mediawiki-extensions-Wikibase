const { bundle, loadConfig } = require( '@redocly/openapi-core' );
const path = require( 'path' );

module.exports = function ( _ ) {
	const done = this.async();

	this.addContextDependency( path.dirname( this.resourcePath ) );

	loadConfig( { configPath: 'redocly.yaml' } )
		.then( ( config ) => {
			bundle( { ref: this.resourcePath, config, dereference: true } )
				.then( ( result ) => {
					const baseUrl = process.env.API_URL || 'https://wikibase.example/w/rest.php';

					done( null, JSON.stringify( {
						...result.bundle.parsed,
						servers: [ { url: baseUrl + '/wikibase/v1' } ]
					} ) );
				} )
				.catch( ( { message } ) => done( message ) );
		} )
		.catch( ( { message } ) => done( message ) );
};
