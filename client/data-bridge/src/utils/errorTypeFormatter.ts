export default function ( errorType: string ): string {
	return ( errorType || 'error-type-undefined' )
		.toLowerCase()
		.replace( /[^a-z0-9_]+/g, '_' )
		.replace( /^[^a-z0-9]|[^a-z0-9]$/g, '' );
}
