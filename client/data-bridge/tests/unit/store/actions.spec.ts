import EditFlow from '@/definitions/EditFlow';
import actions from '@/store/actions';
import {
	NS_ENTITY,
	NS_STATEMENTS,
} from '@/store/namespaces';
import {
	ENTITY_INIT,
	ENTITY_SAVE,
} from '@/store/entity/actionTypes';
import {
	BRIDGE_INIT,
	BRIDGE_SAVE,
	BRIDGE_SET_TARGET_VALUE,
} from '@/store/actionTypes';
import {
	PROPERTY_TARGET_SET,
	EDITFLOW_SET,
	APPLICATION_STATUS_SET,
	TARGET_LABEL_SET,
} from '@/store/mutationTypes';
import {
	ENTITY_ID,
} from '@/store/entity/getterTypes';
import {
	STATEMENTS_IS_AMBIGUOUS,
	STATEMENTS_PROPERTY_EXISTS,
} from '@/store/entity/statements/getterTypes';
import { mainSnakGetterTypes } from '@/store/entity/statements/mainSnakGetterTypes';
import { mainSnakActionTypes } from '@/store/entity/statements/mainSnakActionTypes';
import newMockStore from './newMockStore';
import { action, getter } from '@wmde/vuex-helpers/dist/namespacedStoreMethods';
import ApplicationStatus from '@/definitions/ApplicationStatus';

describe( 'root/actions', () => {
	const entityLabelRepository = {
		getLabel: jest.fn( () => Promise.resolve() ),
	};

	describe( BRIDGE_INIT, () => {
		function mockedStore(
			targetProperty?: string,
			gettersOverride?: any,
		): any {
			// TODO can this profit from hotUpdateDeep?
			return newMockStore( {
				state: {
					targetProperty,
				},
				getters: { ...{
					[ getter(
						NS_ENTITY,
						NS_STATEMENTS,
						STATEMENTS_PROPERTY_EXISTS,
					) ]: jest.fn( () => {
						return true;
					} ),
					[ getter(
						NS_ENTITY,
						NS_STATEMENTS,
						STATEMENTS_IS_AMBIGUOUS,
					)
					]: jest.fn( () => {
						return false;
					} ),
					[ getter(
						NS_ENTITY,
						NS_STATEMENTS,
						mainSnakGetterTypes.snakType,
					) ]: jest.fn( () => {
						return 'value';
					} ),
					[ getter(
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

			return ( actions as Function )( entityLabelRepository )[ BRIDGE_INIT ]( context, information ).then( () => {
				expect( context.commit ).toHaveBeenCalledWith(
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

			return ( actions as Function )( entityLabelRepository )[ BRIDGE_INIT ]( context, information ).then( () => {
				expect( context.commit ).toHaveBeenCalledWith(
					PROPERTY_TARGET_SET,
					propertyId,
				);
			} );
		} );

		it( `dispatches to ${action( NS_ENTITY, ENTITY_INIT )}$`, () => {
			const entityId = 'Q42';
			const context = mockedStore();

			const information = {
				editFlow: EditFlow.OVERWRITE,
				propertyId: '',
				entityId,
			};

			return ( actions as Function )( entityLabelRepository )[ BRIDGE_INIT ]( context, information ).then( () => {
				expect( context.dispatch ).toHaveBeenCalledWith(
					action( NS_ENTITY, ENTITY_INIT ),
					{ 'entity': entityId },
				);
			} );
		} );

		describe( TARGET_LABEL_SET, () => {
			it( `commits to ${TARGET_LABEL_SET}, on successful label request`, () => {
				const propertyId = 'P42';
				const context = mockedStore();

				const information = {
					editFlow: EditFlow.OVERWRITE,
					propertyId,
					entityId: '',
				};

				const term = {
					language: 'zxx',
					value: propertyId,
				};
				const entityLabelRepository = {
					getLabel: jest.fn( () => {
						return Promise.resolve( term );
					} ),
				};

				return ( actions as Function )( entityLabelRepository )[ BRIDGE_INIT ](
					context,
					information,
				).then( () => {
					expect( context.commit ).toHaveBeenCalledWith(
						TARGET_LABEL_SET,
						term,
					);
					expect( entityLabelRepository.getLabel ).toHaveBeenCalledWith( propertyId );
				} );
			} );

			it( 'does nothing on reject', () => {
				const propertyId = 'P42';
				const context = mockedStore();

				const information = {
					editFlow: EditFlow.OVERWRITE,
					propertyId,
					entityId: '',
				};

				const entityLabelRepository = {
					getLabel: jest.fn( () => {
						return Promise.reject( 'Fehler' );
					} ),
				};

				return ( actions as Function )( entityLabelRepository )[ BRIDGE_INIT ](
					context,
					information,
				).then( () => {
					expect( context.commit ).not.toHaveBeenCalledWith( TARGET_LABEL_SET );
					expect( entityLabelRepository.getLabel ).toHaveBeenCalledWith( propertyId );
				} );
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

				return ( actions as Function )( entityLabelRepository )[ BRIDGE_INIT ](
					context,
					information,
				).then( () => {
					expect( context.commit ).toHaveBeenCalledWith( APPLICATION_STATUS_SET, ApplicationStatus.READY );
				} );
			} );

			describe( 'error state', () => {
				it( `commits to ${APPLICATION_STATUS_SET} on fail entity lookup`, () => {
					const context = newMockStore( {
						dispatch: () => Promise.reject(),
					} );

					return ( actions as Function )( entityLabelRepository )[ BRIDGE_INIT ](
						context,
						information,
					).catch( () => {
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
							[ getter( NS_ENTITY, ENTITY_ID ) ]: entityId,
							[ getter(
								NS_ENTITY,
								NS_STATEMENTS,
								STATEMENTS_PROPERTY_EXISTS,
							) ]: jest.fn( () => {
								return false;
							} ),
						},
					);

					return ( actions as Function )( entityLabelRepository )[ BRIDGE_INIT ](
						context,
						information,
					).then( () => {
						expect(
							context.getters[ getter(
								NS_ENTITY,
								NS_STATEMENTS,
								STATEMENTS_PROPERTY_EXISTS,
							) ],
						).toHaveBeenCalledWith( entityId, targetProperty );
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
							[ getter( NS_ENTITY, ENTITY_ID ) ]: entityId,
							[ getter(
								NS_ENTITY,
								NS_STATEMENTS,
								STATEMENTS_IS_AMBIGUOUS,
							) ]: jest.fn( () => {
								return true;
							} ),
						},
					);

					return ( actions as Function )( entityLabelRepository )[ BRIDGE_INIT ](
						context,
						information,
					).then( () => {
						expect(
							context.getters[ getter(
								NS_ENTITY,
								NS_STATEMENTS,
								STATEMENTS_IS_AMBIGUOUS,
							) ],
						).toHaveBeenCalledWith( entityId, targetProperty );
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
							[ getter( NS_ENTITY, ENTITY_ID ) ]: entityId,
							[ getter(
								NS_ENTITY,
								NS_STATEMENTS,
								mainSnakGetterTypes.snakType,
							) ]: jest.fn( () => {
								return 'novalue';
							} ),
						},
					);

					return ( actions as Function )( entityLabelRepository )[ BRIDGE_INIT ](
						context,
						information,
					).then( () => {
						expect(
							context.getters[ getter(
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
							[ getter( NS_ENTITY, ENTITY_ID ) ]: entityId,
							[ getter(
								NS_ENTITY,
								NS_STATEMENTS,
								mainSnakGetterTypes.dataValueType,
							) ]: jest.fn( () => {
								return 'noStringType';
							} ),
						},
					);

					return ( actions as Function )( entityLabelRepository )[ BRIDGE_INIT ](
						context, information,
					).then( () => {
						expect(
							context.getters[ getter(
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

	describe( BRIDGE_SET_TARGET_VALUE, () => {
		it( 'rejects if the store is not ready and switch into error state', async () => {
			const context = newMockStore( {
				state: {
					applicationStatus: ApplicationStatus.INITIALIZING,
				},
			} );
			await expect(
				( actions as Function )( entityLabelRepository )[ BRIDGE_SET_TARGET_VALUE ](
					context,
					{
						path: null,
						value: {
							type: 'string',
							value: 'a string',
						},
					},
				),
			).rejects.toBeDefined();
			expect( context.dispatch ).toHaveBeenCalledTimes( 0 );
			expect( context.commit ).toHaveBeenCalledWith(
				APPLICATION_STATUS_SET,
				ApplicationStatus.ERROR,
			);
		} );

		it( 'propagtes errors and switch into error state', async () => {
			const sampleError = 'sampleError';
			const context = newMockStore( {
				state: {
					applicationStatus: ApplicationStatus.READY,
				},
				dispatch: jest.fn( () => {
					return new Promise( () => {
						throw new Error( sampleError );
					} );
				} ),
			} );

			await expect(
				( actions as Function )( entityLabelRepository )[ BRIDGE_SET_TARGET_VALUE ](
					context,
					{
						value: {
							type: 'string',
							value: 'a string',
						},
					},
				),
			).rejects.toStrictEqual( Error( sampleError ) );
			expect( context.commit ).toHaveBeenCalledWith(
				APPLICATION_STATUS_SET,
				ApplicationStatus.ERROR,
			);
		} );

		it( 'determine the current path and passes down the new data value', () => {
			const entityId = 'Q42';
			const targetProperty = 'P23';
			const context = newMockStore( {
				state: {
					applicationStatus: ApplicationStatus.READY,
					targetProperty,
				},
				getters: {
					[ getter( NS_ENTITY, ENTITY_ID ) ]: entityId,
				} as any,
			} );

			const dataValue = {
				type: 'string',
				value: 'Töfften',
			};

			const payload = {
				path: {
					entityId,
					propertyId: targetProperty,
					index: 0,
				},
				value: dataValue,
			};

			return ( actions as Function )( entityLabelRepository )[ BRIDGE_SET_TARGET_VALUE ](
				context,
				dataValue,
			).then( () => {
				expect( context.dispatch ).toHaveBeenCalledWith(
					action(
						NS_ENTITY,
						NS_STATEMENTS,
						mainSnakActionTypes.setStringDataValue,
					),
					payload,
				);
			} );
		} );
	} );

	describe( BRIDGE_SAVE, () => {
		it( 'rejects if the store is not ready and switch into error state', async () => {
			const context = newMockStore( {
				state: {
					applicationStatus: ApplicationStatus.INITIALIZING,
				},
			} );

			await expect(
				( actions as Function )( entityLabelRepository )[ BRIDGE_SAVE ]( context ),
			).rejects.toBeDefined();
			expect( context.dispatch ).toHaveBeenCalledTimes( 0 );
			expect( context.commit ).toHaveBeenCalledWith(
				APPLICATION_STATUS_SET,
				ApplicationStatus.ERROR,
			);
		} );

		it( 'propagtes errors and switch into error state', async () => {
			const sampleError = new Error( 'sampleError' );
			const context = newMockStore( {
				state: {
					applicationStatus: ApplicationStatus.READY,
				},
				dispatch: jest.fn( () => {
					return new Promise( () => {
						throw sampleError;
					} );
				} ),
			} );

			await expect(
				( actions as Function )( entityLabelRepository )[ BRIDGE_SAVE ]( context ),
			).rejects.toBe( sampleError );

			expect( context.commit ).toHaveBeenCalledWith(
				APPLICATION_STATUS_SET,
				ApplicationStatus.ERROR,
			);
		} );

		it( `dispatches ${ENTITY_SAVE}`, () => {
			const context = newMockStore( {
				state: {
					applicationStatus: ApplicationStatus.READY,
				},
				dispatch: jest.fn( () => {
					return Promise.resolve();
				} ),
			} );

			return ( actions as Function )( entityLabelRepository )[ BRIDGE_SAVE ]( context ).then( () => {
				expect( context.dispatch ).toHaveBeenCalledWith( action( NS_ENTITY, ENTITY_SAVE ) );
				expect( context.dispatch ).toHaveBeenCalledTimes( 1 );
			} );
		} );
	} );
} );
