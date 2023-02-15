const SwaggerParser = require( '@apidevtools/swagger-parser' );
const path = require( 'path' );

module.exports = function ( _ ) {
	const done = this.async();

	this.addContextDependency( path.dirname( this.resourcePath ) );

	SwaggerParser
		.dereference(
			this.resourcePath,
			{
				resolve: {
					http: false
				}
			}
		)
		.then( ( spec ) => {
			const baseUrl = process.env.API_URL || 'https://wikibase.example/w/rest.php';

			done( null, JSON.stringify( {
				...spec,
				servers: [ { url: baseUrl + '/wikibase/v0'} ]
			} ) )
		} )
		.catch( ( { message } ) => done( message ) );
};
