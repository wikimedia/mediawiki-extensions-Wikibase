/**
 * @param namespacesAndName namespace1, namespace2, ..., mutationOrActionName
 */
export default function ( ...namespacesAndName: string[] ): string {
	return namespacesAndName.join( '/' );
}
