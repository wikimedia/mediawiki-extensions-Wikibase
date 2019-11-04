import ForeignApiEntityInfoDispatcher from '@/data-access/ForeignApiEntityInfoDispatcher';

export default function mockEntityInfoDispatcher(
	successEntities?: unknown, error?: unknown,
): ForeignApiEntityInfoDispatcher {
	return {
		dispatchEntitiesInfoRequest(): Promise<any> {
			if ( successEntities ) {
				return Promise.resolve( successEntities );
			}
			if ( error ) {
				return Promise.reject( error );
			}

			return Promise.resolve( {
				Q0: {
					id: 'Q0',
					labels: {
						'de': {
							value: 'foo',
							language: 'de',
						},
					},
				},
			} );
		},
	} as any;
}
