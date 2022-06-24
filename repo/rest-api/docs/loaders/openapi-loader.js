const SwaggerParser = require( '@apidevtools/swagger-parser' );
const path = require( 'path' );

module.exports = function ( _ ) {
	const done = this.async();

	this.addContextDependency( path.dirname( this.resourcePath ) );

	SwaggerParser
		.bundle(
			this.resourcePath,
			{
				resolve: {
					http: false
				}
			}
		)
		.then( spec => done( null, JSON.stringify( spec ) ) )
		.catch( ( { message } ) => done( message ) );
};
