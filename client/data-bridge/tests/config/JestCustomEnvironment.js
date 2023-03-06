const JSDOMEnvironment = require( 'jest-environment-jsdom' ).TestEnvironment;

class JestCustomEnvironment extends JSDOMEnvironment {
	constructor( config, context ) {
		super( config, context );
		Object.assign( context.console, {
			error( ...args ) {
				// eslint-disable-next-line no-console
				console.error( ...args );
				throw new Error(
					`Unexpected call of console.error() with:\n\n${args.join( ', ' )}`,
					this.error,
				);
			},

			warn( ...args ) {
				// eslint-disable-next-line no-console
				console.warn( ...args );
				throw new Error(
					`Unexpected call of console.warn() with:\n\n${args.join( ', ' )}`,
					this.warn,
				);
			},
		} );
	}
}

module.exports = JestCustomEnvironment;
