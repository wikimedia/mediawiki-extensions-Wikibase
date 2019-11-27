export function expectError( promise: Promise<any> ): Promise<any> {
	return promise.then(
		( _ ) => {
			throw new Error( 'should not have resolved with a value' );
		},
		( error ) => error,
	);
}
