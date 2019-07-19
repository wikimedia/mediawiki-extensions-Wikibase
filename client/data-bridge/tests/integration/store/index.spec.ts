import EntityRevision from '@/datamodel/EntityRevision';
import { services } from '@/services';
import { createStore } from '@/store';
import { BRIDGE_INIT } from '@/store/actionTypes';

describe( 'store', () => {
	it( 'returns correct target value', () => {
		services.setEntityRepository( {
			async getEntity( _id: string, _revision?: number ): Promise<EntityRevision> {
				return {
					revisionId: 0,
					entity: {
						id: 'Q42',
						statements: {
							'P31': [ {
								type: 'statement',
								id: 'opaque statement ID',
								rank: 'normal',
								mainsnak: {
									snaktype: 'value',
									property: 'P31',
									datatype: 'string',
									datavalue: {
										type: 'string',
										value: 'a string value',
									},
								},
							} ],
						},
					},
				};
			},
		} );
		const store = createStore();

		expect( store.getters.targetValue ).toBe( null );

		return store.dispatch( BRIDGE_INIT, {
			editFlow: 'overwrite',
			targetProperty: 'P31',
			targetEntity: 'Q42',
		} ).then( () => {
			expect( store.getters.targetValue ).toBe( 'a string value' );
		} );
	} );
} );
