export async function sleep( milliseconds: number ): Promise<typeof setTimeout> {
	return new Promise( ( resolve ) => setTimeout( resolve, milliseconds ) );
}

export async function budge(): Promise<typeof setTimeout> {
	return sleep( 0 );
}

export async function waitFor(
	condition: () => boolean,
	timeout = 300,
	interval = 10,
): Promise<unknown> {

	const limit = Math.ceil( timeout / interval );
	let rounds = 0;

	return new Promise( ( resolve, reject ) => {
		function conditionalWait(): void {
			rounds++;
			Promise.resolve( condition() )
				.then(
					( done: boolean ) => {
						if ( done === true ) {
							return resolve( null );
						}

						if ( rounds < limit ) {
							setTimeout( conditionalWait, interval );
							return;
						} else {
							return reject( new Error( `Test timed out in ${timeout}ms.` ) );
						}
					},
					( error: Error ) => {
						if ( rounds < limit ) {
							setTimeout( conditionalWait, interval );
							return;
						}

						return reject( error );
					},
				);
		}

		setTimeout( conditionalWait, 0 );
	} );
}
