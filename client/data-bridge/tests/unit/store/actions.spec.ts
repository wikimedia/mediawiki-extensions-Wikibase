import EditFlow from '@/definitions/EditFlow';
import { actions } from '@/store/actions';
import {
	NS_ENTITY,
	NS_STATEMENTS,
} from '@/store/namespaces';
import {
	ENTITY_INIT,
} from '@/store/entity/actionTypes';
import {
	BRIDGE_INIT,
} from '@/store/actionTypes';
import {
	PROPERTY_TARGET_SET,
	EDITFLOW_SET,
	APPLICATION_STATUS_SET,
} from '@/store/mutationTypes';
import {
	ENTITY_ID,
} from '@/store/entity/getterTypes';
import {
	STATEMENTS_IS_AMBIGUOUS,
	STATEMENTS_PROPERTY_EXISTS,
} from '@/store/entity/statements/getterTypes';
import { mainSnakGetterTypes } from '@/store/entity/statements/mainSnakGetterTypes';
import newMockStore from './newMockStore';
import namespacedStoreEvent from '@/store/namespacedStoreEvent';
import ApplicationStatus from '@/definitions/ApplicationStatus';

describe( 'root/actions', () => {
	describe( BRIDGE_INIT, () => {
		function mockedStore(
			targetProperty?: string,
			gettersOverride?: any,
		): any {
			return newMockStore( {
				state: {
					targetProperty,
				},
				getters: { ...{
					[ namespacedStoreEvent(
						NS_ENTITY,
						NS_STATEMENTS,
						STATEMENTS_PROPERTY_EXISTS,
					) ]: jest.fn( () => {
						return true;
					} ),
					[ namespacedStoreEvent(
						NS_ENTITY,
						NS_STATEMENTS,
						STATEMENTS_IS_AMBIGUOUS,
					)
					]: jest.fn( () => {
						return false;
					} ),
					[ namespacedStoreEvent(
						NS_ENTITY,
						NS_STATEMENTS,
						mainSnakGetterTypes.snakType,
					) ]: jest.fn( () => {
						return 'value';
					} ),
					[ namespacedStoreEvent(
						NS_ENTITY,
						NS_STATEMENTS,
						mainSnakGetterTypes.dataValueType,
					) ]: jest.fn( () => {
						return 'string';
					} ),
				}, ...gettersOverride },
			} );
		}

		it( `commits to ${EDITFLOW_SET}`, () => {
			const editFlow = EditFlow.OVERWRITE;
			const context = mockedStore();

			const information = {
				editFlow,
				propertyId: '',
				entityId: '',
			};

			return actions[ BRIDGE_INIT ]( context, information ).then( () => {
				expect( context.commit ).toBeCalledWith(
					EDITFLOW_SET,
					editFlow,
				);
			} );
		} );

		it( `commits to ${PROPERTY_TARGET_SET}`, () => {
			const propertyId = 'P42';
			const context = mockedStore();

			const information = {
				editFlow: EditFlow.OVERWRITE,
				propertyId,
				entityId: '',
			};

			return actions[ BRIDGE_INIT ]( context, information ).then( () => {
				expect( context.commit ).toBeCalledWith(
					PROPERTY_TARGET_SET,
					propertyId,
				);
			} );
		} );

		it( `dispatches to ${namespacedStoreEvent( NS_ENTITY, ENTITY_INIT )}$`, () => {
			const entityId = 'Q42';
			const context = mockedStore();

			const information = {
				editFlow: EditFlow.OVERWRITE,
				propertyId: '',
				entityId,
			};

			return actions[ BRIDGE_INIT ]( context, information ).then( () => {
				expect( context.dispatch ).toBeCalledWith(
					namespacedStoreEvent( NS_ENTITY, ENTITY_INIT ),
					{ 'entity': entityId },
				);
			} );
		} );

		describe( 'Applicationstatus', () => {
			const information = {
				editFlow: EditFlow.OVERWRITE,
				propertyId: 'P123',
				entityId: 'Q123',
			};

			it( `commits to ${APPLICATION_STATUS_SET} on successful entity lookup`, () => {
				const context = mockedStore();

				return actions[ BRIDGE_INIT ]( context, information ).then( () => {
					expect( context.commit ).toHaveBeenCalledWith( APPLICATION_STATUS_SET, ApplicationStatus.READY );
				} );
			} );

			describe( 'error state', () => {
				it( `commits to ${APPLICATION_STATUS_SET} on fail entity lookup`, () => {
					const context = newMockStore( {
						dispatch: () => Promise.reject(),
					} );

					return actions[ BRIDGE_INIT ]( context, information ).catch( () => {
						expect( context.commit ).toHaveBeenCalledWith(
							APPLICATION_STATUS_SET,
							ApplicationStatus.ERROR,
						);
					} );
				} );

				it( `commits to ${APPLICATION_STATUS_SET} on missing statements`, () => {
					const entityId = 'Q42';
					const targetProperty = 'P23';

					const context = mockedStore(
						targetProperty,
						{
							[ namespacedStoreEvent( NS_ENTITY, ENTITY_ID ) ]: entityId,
							[ namespacedStoreEvent(
								NS_ENTITY,
								NS_STATEMENTS,
								STATEMENTS_PROPERTY_EXISTS,
							) ]: jest.fn( () => {
								return false;
							} ),
						},
					);

					return actions[ BRIDGE_INIT ]( context, information ).then( () => {
						expect(
							context.getters[ namespacedStoreEvent(
								NS_ENTITY,
								NS_STATEMENTS,
								STATEMENTS_PROPERTY_EXISTS,
							) ],
						).toBeCalledWith( entityId, targetProperty );
						expect( context.commit ).toHaveBeenCalledWith(
							APPLICATION_STATUS_SET,
							ApplicationStatus.ERROR,
						);
					} );
				} );

				it( `commits to ${APPLICATION_STATUS_SET} on ambiguous statements`, () => {
					const entityId = 'Q42';
					const targetProperty = 'P23';

					const context = mockedStore(
						targetProperty,
						{
							[ namespacedStoreEvent( NS_ENTITY, ENTITY_ID ) ]: entityId,
							[ namespacedStoreEvent(
								NS_ENTITY,
								NS_STATEMENTS,
								STATEMENTS_IS_AMBIGUOUS,
							) ]: jest.fn( () => {
								return true;
							} ),
						},
					);

					return actions[ BRIDGE_INIT ]( context, information ).then( () => {
						expect(
							context.getters[ namespacedStoreEvent(
								NS_ENTITY,
								NS_STATEMENTS,
								STATEMENTS_IS_AMBIGUOUS,
							) ],
						).toBeCalledWith( entityId, targetProperty );
						expect( context.commit ).toHaveBeenCalledWith(
							APPLICATION_STATUS_SET,
							ApplicationStatus.ERROR,
						);
					} );
				} );

				it( `commits to ${APPLICATION_STATUS_SET} for not value snak types`, () => {
					const entityId = 'Q42';
					const targetProperty = 'P23';

					const context = mockedStore(
						targetProperty,
						{
							[ namespacedStoreEvent( NS_ENTITY, ENTITY_ID ) ]: entityId,
							[ namespacedStoreEvent(
								NS_ENTITY,
								NS_STATEMENTS,
								mainSnakGetterTypes.snakType,
							) ]: jest.fn( () => {
								return 'novalue';
							} ),
						},
					);

					return actions[ BRIDGE_INIT ]( context, information ).then( () => {
						expect(
							context.getters[ namespacedStoreEvent(
								NS_ENTITY,
								NS_STATEMENTS,
								mainSnakGetterTypes.snakType,
							) ],
						).toHaveBeenCalledWith( { entityId, propertyId: targetProperty, index: 0 } );
						expect( context.commit ).toHaveBeenCalledWith(
							APPLICATION_STATUS_SET,
							ApplicationStatus.ERROR,
						);
					} );
				} );

				it( `commits to ${APPLICATION_STATUS_SET} for non string data types`, () => {
					const entityId = 'Q42';
					const targetProperty = 'P23';

					const context = mockedStore(
						targetProperty,
						{
							[ namespacedStoreEvent( NS_ENTITY, ENTITY_ID ) ]: entityId,
							[ namespacedStoreEvent(
								NS_ENTITY,
								NS_STATEMENTS,
								mainSnakGetterTypes.dataValueType,
							) ]: jest.fn( () => {
								return 'noStringType';
							} ),
						},
					);

					return actions[ BRIDGE_INIT ]( context, information ).then( () => {
						expect(
							context.getters[ namespacedStoreEvent(
								NS_ENTITY,
								NS_STATEMENTS,
								mainSnakGetterTypes.dataValueType,
							) ],
						).toHaveBeenCalledWith( { entityId, propertyId: targetProperty, index: 0 } );
						expect( context.commit ).toHaveBeenCalledWith(
							APPLICATION_STATUS_SET,
							ApplicationStatus.ERROR,
						);
					} );
				} );
			} );
		} );
	} );
} );
