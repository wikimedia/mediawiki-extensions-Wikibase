const JSDOMEnvironment = require( 'jest-environment-jsdom' );

class JestCustomEnvironment extends JSDOMEnvironment {
	constructor( config, context ) {
		super( config, context );
		Object.assign( context.console, {
			error( ...args ) {
				throw new Error(
					`Unexpected call of console.error() with:\n\n${args.join( ', ' )}`,
					this.error,
				);
			},

			warn( ...args ) {
				throw new Error(
					`Unexpected call of console.warn() with:\n\n${args.join( ', ' )}`,
					this.warn,
				);
			},
		} );
	}
}

module.exports = JestCustomEnvironment;
