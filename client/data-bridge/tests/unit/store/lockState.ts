function lockState( state: any ): void {
	const keys = Object.keys( state );
	keys.forEach( ( key: string ): void => {
		if ( typeof state[ key ] === 'object' && state[ key ] !== null ) {
			lockState( state[ key ] );
		}
	} );
	Object.preventExtensions( state );
}

export default lockState;
