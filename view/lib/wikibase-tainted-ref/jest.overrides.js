// Throw an exception in case console.error/warn was called in a test,
// so that jest fails.
( () => {
	// eslint-disable-next-line no-console
	const error = console.error;

	// eslint-disable-next-line no-console
	console.warn = console.error = function ( message, ...params ) {
		error.apply( console, [ message, ...params ] );
		throw ( message instanceof Error ? message : new Error( message ) );
	};
} )();
