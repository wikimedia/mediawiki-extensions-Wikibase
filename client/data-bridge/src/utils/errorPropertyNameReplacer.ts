export default function ( _key: string, value: unknown ): unknown {
	if ( value instanceof Error ) {
		const error: Record<string, string | undefined> = {};
		Object.getOwnPropertyNames( value ).forEach( ( key: string ): void => {
			error[ key ] = value[ key as keyof Error ];
		} );
		return error;
	}
	return value;
}
