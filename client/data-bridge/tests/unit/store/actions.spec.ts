import ApplicationError, { ErrorTypes } from '@/definitions/ApplicationError';
import ApplicationStatus, { ValidApplicationStatus } from '@/definitions/ApplicationStatus';
import { WikibaseRepoConfiguration } from '@/definitions/data-access/WikibaseRepoConfigRepository';
import EditDecision from '@/definitions/EditDecision';
import EditFlow from '@/definitions/EditFlow';
import { BridgeConfigOptions } from '@/presentation/plugins/BridgeConfigPlugin/BridgeConfig';
import Application from '@/store/Application';
import { RootGetters } from '@/store/getters';
import Vue, { VueConstructor } from 'vue';
import PropertyDatatypeRepository from '@/definitions/data-access/PropertyDatatypeRepository';
import {
	BridgePermissionsRepository,
	MissingPermissionsError,
	PageNotEditable,
	CascadeProtectedReason,
} from '@/definitions/data-access/BridgePermissionsRepository';
import { inject } from 'vuex-smart-module';
import { RootActions } from '@/store/actions';
import AppInformation from '@/definitions/AppInformation';
import newMockServiceContainer from '../services/newMockServiceContainer';
import newMockTracker from '../../util/newMockTracker';
import newApplicationState from './newApplicationState';
import { MainSnakPath } from '@/store/statements/MainSnakPath';
import DataValue from '@/datamodel/DataValue';
import { StatementState } from '@/store/statements';
import Statement from '@/datamodel/Statement';
import MediaWikiPurge from '@/definitions/MediaWikiPurge';
import { getMockBridgeRepoConfig } from '../../util/mocks';
import Reference from '@/datamodel/Reference';
import { budge } from '../../util/timer';

const mockBridgeConfig = jest.fn();
jest.mock( '@/presentation/plugins/BridgeConfigPlugin', () => ( {
	__esModule: true,
	default: ( vue: VueConstructor<Vue>, options?: BridgeConfigOptions ) => mockBridgeConfig( vue, options ),
} ) );

describe( 'root/actions', () => {
	const defaultEntityId = 'Q32';
	const defaultPropertyId = 'P71';
	function newMockAppInformation( fields: Partial<AppInformation> = {} ): AppInformation {
		return {
			editFlow: EditFlow.OVERWRITE,
			propertyId: defaultPropertyId,
			entityId: defaultEntityId,
			entityTitle: 'Entity Title',
			originalHref: '',
			pageTitle: '',
			client: { usePublish: true, issueReportingLink: '' },
			...fields,
		};
	}

	describe( 'initBridge', () => {

		const wikibaseRepoConfigRepository = {
			getRepoConfiguration: jest.fn( () => Promise.resolve() ),
		};
		const editAuthorizationChecker: BridgePermissionsRepository = {
			canUseBridgeForItemAndPage: jest.fn().mockResolvedValue( [] ),
		};
		const propertyDatatypeRepository: PropertyDatatypeRepository = {
			getDataType: jest.fn().mockResolvedValue( 'string' ),
		};

		const listOfCommits = [
			'setEditFlow',
			'setPropertyPointer',
			'setEntityTitle',
			'setOriginalHref',
			'setPageTitle',
		];
		it(
			`commits to ${listOfCommits.join( ', ' )}`,
			async () => {
				const editFlow = EditFlow.OVERWRITE;
				const propertyId = 'P42';
				const entityTitle = 'Douglas Adams';
				const originalHref = 'https://example.com/index.php?title=Item:Q42&uselang=en#P31';
				const pageTitle = 'Client_page';
				const information = newMockAppInformation( {
					editFlow,
					propertyId,
					entityTitle,
					originalHref,
					pageTitle,
				} );

				const commit = jest.fn();
				const actions = inject( RootActions, {
					commit,
					dispatch: jest.fn(),
				} );
				// @ts-ignore
				actions.store = {
					$services: newMockServiceContainer( {
						wikibaseRepoConfigRepository,
						editAuthorizationChecker,
						propertyDatatypeRepository,
					} ),
				};

				// @ts-ignore
				actions.entityModule = {
					dispatch: jest.fn(),
				};

				await actions.initBridge( information );
				expect( commit ).toHaveBeenCalledWith( 'setEditFlow', editFlow );
				expect( commit ).toHaveBeenCalledWith( 'setPropertyPointer', propertyId );
				expect( commit ).toHaveBeenCalledWith( 'setEntityTitle', entityTitle );
				expect( commit ).toHaveBeenCalledWith( 'setOriginalHref', originalHref );
				expect( commit ).toHaveBeenCalledWith( 'setPageTitle', pageTitle );
				expect( commit ).toHaveBeenCalledWith( 'setApplicationStatus', ApplicationStatus.READY );
			},
		);

		it( 'dispatches to requestAndSetTargetLabel', async () => {
			const propertyId = 'P42';
			const information = newMockAppInformation( {
				propertyId,
			} );

			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				commit: jest.fn(),
				dispatch,
			} );

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					wikibaseRepoConfigRepository,
					editAuthorizationChecker,
					propertyDatatypeRepository,
				} ),
			};

			// @ts-ignore
			actions.entityModule = {
				dispatch: jest.fn(),
			};

			await actions.initBridge( information );
			expect( dispatch ).toHaveBeenCalledWith( 'requestAndSetTargetLabel', propertyId );
		} );

		it( 'Requests repoConfig, authorization, datatype and dispatches entityInit in that order', async () => {
			const propertyId = 'P42';
			const entityTitle = 'Douglas Adams';
			const pageTitle = 'South_Pole_Telescope';
			const entityId = 'Q42';
			const information = newMockAppInformation( {
				propertyId,
				entityTitle,
				pageTitle,
				entityId,
			} );
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				commit: jest.fn(),
				dispatch,
			} );

			const mockWikibaseRepoConfig = {};
			const wikibaseRepoConfigRepository = {
				getRepoConfiguration: jest.fn().mockResolvedValue( mockWikibaseRepoConfig ),
			};
			const mockListOfAuthorizationErrors: MissingPermissionsError[] = [];
			const editAuthorizationChecker = {
				canUseBridgeForItemAndPage: jest.fn().mockResolvedValue( mockListOfAuthorizationErrors ),
			};
			const propertyDataType = 'string';
			const propertyDatatypeRepository: PropertyDatatypeRepository = {
				getDataType: jest.fn().mockResolvedValue( propertyDataType ),
			};

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					wikibaseRepoConfigRepository,
					editAuthorizationChecker,
					propertyDatatypeRepository,
				} ),
			};
			const ignoredEntityInitResult = {};
			const entityModuleDispatch = jest.fn().mockResolvedValue( ignoredEntityInitResult );

			// @ts-ignore
			actions.entityModule = {
				dispatch: entityModuleDispatch,
			};

			await actions.initBridge( information );
			expect( wikibaseRepoConfigRepository.getRepoConfiguration ).toHaveBeenCalled();
			expect( editAuthorizationChecker.canUseBridgeForItemAndPage ).toHaveBeenCalledWith(
				entityTitle,
				pageTitle,
			);
			expect( propertyDatatypeRepository.getDataType ).toHaveBeenCalledWith( propertyId );
			expect( entityModuleDispatch ).toHaveBeenCalledWith( 'entityInit', { entity: entityId } );
			expect( dispatch ).toHaveBeenCalledTimes( 2 );
			expect( dispatch.mock.calls[ 1 ][ 0 ] ).toBe( 'initBridgeWithRemoteData' );
			expect( dispatch.mock.calls[ 1 ][ 1 ] ).toStrictEqual( {
				information,
				results: [
					mockWikibaseRepoConfig,
					mockListOfAuthorizationErrors,
					propertyDataType,
					ignoredEntityInitResult,
				],
			} );

		} );

		it( 'Commits to addApplicationErrors if there is an error', async () => {
			const information = newMockAppInformation();
			const commit = jest.fn();
			const actions = inject( RootActions, {
				commit,
				dispatch: jest.fn(),
			} );
			const error = new Error( 'This should be logged and propagated' );
			const wikibaseRepoConfigRepository = {
				getRepoConfiguration: jest.fn().mockRejectedValue( error ),
			};

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					wikibaseRepoConfigRepository,
					editAuthorizationChecker,
					propertyDatatypeRepository,
				} ),
			};

			// @ts-ignore
			actions.entityModule = {
				dispatch: jest.fn(),
			};
			await expect( actions.initBridge( information ) ).rejects.toBe( error );
			expect( commit ).toHaveBeenCalledWith(
				'addApplicationErrors',
				[ {
					type: ErrorTypes.APPLICATION_LOGIC_ERROR,
					info: error,
				} ],
			);
		} );
	} );

	describe( 'requestAndSetTargetLabel', () => {

		it( 'commits to setTargetLabel if request was successful', async () => {
			const propertyId = 'Q42';
			const commit = jest.fn();
			const actions = inject( RootActions, {
				commit,
			} );
			const propertyLabel = 'instance of';
			const entityLabelRepository = {
				getLabel: jest.fn().mockResolvedValue( propertyLabel ),
			};

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					entityLabelRepository,
				} ),
			};

			await actions.requestAndSetTargetLabel( propertyId );
			expect( entityLabelRepository.getLabel ).toHaveBeenCalledWith( propertyId );
			expect( commit ).toHaveBeenCalledWith( 'setTargetLabel', propertyLabel );
		} );

		it( 'does nothing if there was an error', async () => {
			const commit = jest.fn();
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				commit,
				dispatch,
			} );
			const error = new Error( 'ignored' );
			const entityLabelRepository = {
				getLabel: jest.fn().mockRejectedValue( error ),
			};

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					entityLabelRepository,
				} ),
			};

			await actions.requestAndSetTargetLabel( 'Q123' );
			expect( commit ).not.toHaveBeenCalled();
			expect( dispatch ).not.toHaveBeenCalled();
		} );

	} );

	describe( 'postEntityLoad', () => {
		it( 'dispatches validateEntityState', async () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				state: newApplicationState( {
					entity: { id: defaultEntityId },
					targetProperty: defaultPropertyId,
					statements: {
						[ defaultEntityId ]: {
							[ defaultPropertyId ]: [ { mainsnak: { datavalue: {} } } ],
						},
					},
				} ),
				commit: jest.fn(),
				dispatch,
				getters: jest.fn() as any,
			} );

			const mainSnakPath = new MainSnakPath( defaultEntityId, defaultPropertyId, 0 );

			await actions.postEntityLoad();

			expect( dispatch ).toHaveBeenCalledWith( 'validateEntityState', mainSnakPath );
		} );

		it( 'commits targetValue & setApplicationStatus if there are no errors', async () => {
			const commit = jest.fn();
			const dataValue: DataValue = { type: 'string', value: 'a string value' };
			const statement = { mainsnak: { datavalue: dataValue } };
			const actions = inject( RootActions, {
				state: newApplicationState( {
					entity: { id: defaultEntityId },
					targetProperty: defaultPropertyId,
					statements: {
						[ defaultEntityId ]: {
							[ defaultPropertyId ]: [ statement ],
						},
					},
				} ),
				commit,
				dispatch: jest.fn(),
				getters: {
					applicationStatus: ApplicationStatus.INITIALIZING,
				} as RootGetters,
			} );

			await actions.postEntityLoad();

			expect( commit ).toHaveBeenCalledWith( 'setTargetValue', dataValue );
		} );

		it( 'doesn\'t commit if there are errors', async () => {
			const commit = jest.fn();
			const actions = inject( RootActions, {
				state: newApplicationState( {
					entity: { id: defaultEntityId },
					targetProperty: defaultPropertyId,
					statements: {
						[ defaultEntityId ]: {
							[ defaultPropertyId ]: [ {} ],
						},
					},
				} ),
				commit,
				dispatch: jest.fn(),
				getters: {
					applicationStatus: ApplicationStatus.ERROR,
				} as RootGetters,
			} );

			await actions.postEntityLoad();

			expect( commit ).not.toHaveBeenCalled();
		} );

		it( 'doesn\'t commit setApplicationStatus if not initializing', async () => {
			const commit = jest.fn();
			const dataValue: DataValue = { type: 'string', value: 'a string value' };
			const statement = { mainsnak: { datavalue: dataValue } };
			const actions = inject( RootActions, {
				state: newApplicationState( {
					entity: { id: defaultEntityId },
					targetProperty: defaultPropertyId,
					statements: {
						[ defaultEntityId ]: {
							[ defaultPropertyId ]: [ statement ],
						},
					},
				} ),
				commit,
				dispatch: jest.fn(),
				getters: {
					applicationStatus: ApplicationStatus.SAVED,
				} as RootGetters,
			} );

			await actions.postEntityLoad();

			expect( commit ).toHaveBeenCalledWith( 'setTargetValue', dataValue );
			expect( commit ).not.toHaveBeenCalledWith( 'setApplicationStatus', ApplicationStatus.READY );
		} );
	} );

	describe( 'initBridgeWithRemoteData', () => {
		it( 'commits to addApplicationErrors if there are permission errors', async () => {
			const information = newMockAppInformation();
			const commit = jest.fn();
			const actions = inject( RootActions, {
				commit,
				dispatch: jest.fn(),
			} );

			const permissionErrors = [ {
				type: PageNotEditable.ITEM_CASCADE_PROTECTED,
				info: {
					pages: [ 'foo' ],
				},
			} as CascadeProtectedReason ];

			await actions.initBridgeWithRemoteData( { information, results: [
				{} as WikibaseRepoConfiguration,
				permissionErrors,
				'string',
				undefined,
			] } );

			expect( commit ).toHaveBeenCalledWith( 'addApplicationErrors', permissionErrors );
		} );

		it( 'tracks the datatype', async () => {
			const information = newMockAppInformation();
			const actions = inject( RootActions, {
				state: newApplicationState( {
					entity: { id: defaultEntityId },
					targetProperty: defaultPropertyId,
					statements: {
						[ defaultEntityId ]: {
							[ defaultPropertyId ]: [ { mainsnak: { datavalue: {} } } ],
						},
					},
				} ),
				commit: jest.fn(),
				dispatch: jest.fn(),
				getters: jest.fn() as any,
			} );
			const propertyDataType = 'string';
			const tracker = newMockTracker();

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					tracker,
				} ),
			};
			await actions.initBridgeWithRemoteData( { information, results: [
				{} as WikibaseRepoConfiguration,
				[],
				propertyDataType,
				undefined,
			] } );

			expect( tracker.trackPropertyDatatype ).toHaveBeenCalledWith( propertyDataType );

		} );

		it( 'dispatches the reference rendering', async () => {
			let referenceRenderingResolve;
			const dispatch = jest.fn().mockReturnValue( new Promise( ( resolve ) => {
				referenceRenderingResolve = resolve;
			} ) );
			const actions = inject( RootActions, {
				state: newApplicationState(),
				dispatch,
			} );

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					tracker: newMockTracker(),
				} ),
			};

			actions.initBridgeWithRemoteData( {
				information: newMockAppInformation(),
				results: [
					{} as WikibaseRepoConfiguration,
					[],
					'string',
					undefined,
				],
			} );

			expect( dispatch ).toHaveBeenCalledTimes( 1 );
			expect( dispatch ).toHaveBeenCalledWith( 'renderReferences' );

			// @ts-ignore
			referenceRenderingResolve();
			await budge();
			expect( dispatch ).toHaveBeenCalledTimes( 2 );
			expect( dispatch ).toHaveBeenCalledWith( 'postEntityLoad' );

		} );

		it( 'resets the config plugin', async () => {
			const information = newMockAppInformation();
			const actions = inject( RootActions, {
				state: newApplicationState( {
					entity: { id: defaultEntityId },
					targetProperty: defaultPropertyId,
					statements: {
						[ defaultEntityId ]: {
							[ defaultPropertyId ]: [ { mainsnak: { datavalue: {} } } ],
						},
					},
				} ),
				commit: jest.fn(),
				dispatch: jest.fn(),
				getters: jest.fn() as any,
			} );

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					tracker: newMockTracker(),
				} ),
			};

			const wikibaseRepoConfiguration = getMockBridgeRepoConfig();

			await actions.initBridgeWithRemoteData( { information, results: [
				wikibaseRepoConfiguration,
				[],
				'string',
				undefined,
			] } );

			expect( mockBridgeConfig ).toHaveBeenCalledWith(
				Vue,
				{ ...wikibaseRepoConfiguration, ...information.client },
			);
		} );

		it( 'dispatches postEntityLoad', async () => {
			const information = newMockAppInformation();
			const commit = jest.fn();
			const dataValue: DataValue = { type: 'string', value: 'a string value' };
			const statement = { mainsnak: { datavalue: dataValue } };
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				state: newApplicationState( {
					entity: { id: defaultEntityId },
					targetProperty: defaultPropertyId,
					statements: {
						[ defaultEntityId ]: {
							[ defaultPropertyId ]: [ statement ],
						},
					},
				} ),
				commit,
				dispatch,
				getters: jest.fn() as any,
			} );

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					tracker: newMockTracker(),
				} ),
			};

			await actions.initBridgeWithRemoteData( { information, results: [
				{} as WikibaseRepoConfiguration,
				[],
				'string',
				undefined,
			] } );

			expect( dispatch ).toHaveBeenCalledWith( 'postEntityLoad' );
		} );
	} );

	describe( 'renderReferences', () => {
		it( 'gets the rendered references and stores them in the store', async () => {
			const commit = jest.fn();
			const mockReferences: Reference[] = [
				{} as Reference,
				{} as Reference,
			];
			const actions = inject( RootActions, {
				commit,
				getters: {
					targetReferences: mockReferences,
				} as any,
			} );
			const mockRenderedReferences = [
				'<span>ref1</span>',
				'<span>ref2</span>',
			];
			const getRenderedReferences = jest.fn().mockResolvedValue( mockRenderedReferences );
			const referencesRenderingRepository = {
				getRenderedReferences,
			};

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					referencesRenderingRepository,
				} ),
			};

			await expect( actions.renderReferences() ).resolves.toBeUndefined();

			expect( getRenderedReferences ).toHaveBeenCalledWith( mockReferences );
			expect( commit ).toHaveBeenCalledWith( 'setRenderedTargetReferences', mockRenderedReferences );
		} );
	} );

	describe( 'validateEntityState', () => {
		it( 'commits to addApplicationErrors if property is missing on entity', () => {
			const commit = jest.fn();
			const actions = inject( RootActions, {
				commit,
			} );
			const statementPropertyGetter = jest.fn().mockReturnValue( false );

			// @ts-ignore
			actions.statementModule = {
				getters: {
					propertyExists: statementPropertyGetter,
				} as any,
			};

			const entityId = 'Q42';
			const propertyId = 'P42';
			const mainSnakPath = new MainSnakPath( entityId, propertyId, 0 );
			actions.validateEntityState( mainSnakPath );
			expect( statementPropertyGetter ).toHaveBeenCalledWith( mainSnakPath );
			expect( commit ).toHaveBeenCalledWith(
				'addApplicationErrors',
				[ { type: ErrorTypes.INVALID_ENTITY_STATE_ERROR } ],
			);
		} );

		it( 'dispatches validateBridgeApplicability if there are no errors', () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				dispatch,
			} );
			const statementPropertyGetter = jest.fn().mockReturnValue( true );

			// @ts-ignore
			actions.statementModule = {
				getters: {
					propertyExists: statementPropertyGetter,
				} as any,
			};
			const mainSnakPath = new MainSnakPath( 'Q42', 'P42', 0 );
			actions.validateEntityState( mainSnakPath );
			expect( dispatch ).toHaveBeenCalledWith( 'validateBridgeApplicability', mainSnakPath );
		} );
	} );

	describe( 'validateBridgeApplicability', () => {
		const state = { applicationStatus: ApplicationStatus.INITIALIZING } as Application;
		const statementModule = {
			getters: {
				isStatementGroupAmbiguous: jest.fn().mockReturnValue( false ),
				rank: jest.fn().mockReturnValue( 'normal' ),
				snakType: jest.fn().mockReturnValue( 'value' ),
				dataType: jest.fn().mockReturnValue( 'string' ),
				dataValueType: jest.fn().mockReturnValue( 'string' ),
			},
		};

		it( 'doesn\'t dispatch error if applicable', () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				state,
				dispatch,
			} );

			// @ts-ignore
			actions.statementModule = statementModule;

			const entityId = 'Q42';
			const propertyId = 'P42';
			const mainSnakPath = new MainSnakPath( entityId, propertyId, 0 );
			actions.validateBridgeApplicability( mainSnakPath );
			expect( dispatch ).not.toHaveBeenCalled();
			expect( statementModule.getters.isStatementGroupAmbiguous ).toHaveBeenCalledWith( mainSnakPath );
			expect( statementModule.getters.snakType ).toHaveBeenCalledWith( mainSnakPath );
			expect( statementModule.getters.dataValueType ).toHaveBeenCalledWith( mainSnakPath );
		} );

		it( 'doesn\'t dispatch error if saved', () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				state: { applicationStatus: ApplicationStatus.SAVED } as Application,
				dispatch,
			} );

			// @ts-ignore
			actions.statementModule = statementModule;

			const entityId = 'Q42';
			const propertyId = 'P42';
			const mainSnakPath = new MainSnakPath( entityId, propertyId, 0 );
			actions.validateBridgeApplicability( mainSnakPath );
			expect( dispatch ).not.toHaveBeenCalled();
			expect( statementModule.getters.isStatementGroupAmbiguous ).not.toHaveBeenCalled();
			expect( statementModule.getters.snakType ).not.toHaveBeenCalled();
			expect( statementModule.getters.dataValueType ).not.toHaveBeenCalled();
		} );

		it( 'dispatches error on ambiguous statements', () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				state,
				dispatch,
			} );

			statementModule.getters.isStatementGroupAmbiguous.mockReturnValueOnce( true );

			// @ts-ignore
			actions.statementModule = statementModule;
			actions.validateBridgeApplicability( new MainSnakPath( 'Q42', 'P42', 0 ) );

			expect( dispatch ).toHaveBeenCalledWith(
				'addError',
				[ { type: ErrorTypes.UNSUPPORTED_AMBIGUOUS_STATEMENT } ],
			);

		} );

		it( 'dispatches error on deprecated statement', () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				state,
				dispatch,
			} );

			statementModule.getters.rank.mockReturnValueOnce( 'deprecated' );

			// @ts-ignore
			actions.statementModule = statementModule;
			actions.validateBridgeApplicability( new MainSnakPath( 'Q42', 'P42', 0 ) );

			expect( dispatch ).toHaveBeenCalledWith(
				'addError',
				[ { type: ErrorTypes.UNSUPPORTED_DEPRECATED_STATEMENT } ],
			);
		} );

		it( 'dispatches error for non-value snak types', () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				state,
				dispatch,
			} );

			statementModule.getters.snakType.mockReturnValueOnce( 'novalue' );

			// @ts-ignore
			actions.statementModule = statementModule;
			actions.validateBridgeApplicability( new MainSnakPath( 'Q42', 'P42', 0 ) );

			expect( dispatch ).toHaveBeenCalledWith(
				'addError',
				[ { type: ErrorTypes.UNSUPPORTED_SNAK_TYPE, info: { snakType: 'novalue' } } ],
			);

		} );

		it( 'dispatches error for non-string data types', () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				state,
				dispatch,
			} );

			statementModule.getters.dataType.mockReturnValueOnce( 'url' );

			// @ts-ignore
			actions.statementModule = statementModule;
			actions.validateBridgeApplicability( new MainSnakPath( 'Q42', 'P42', 0 ) );

			expect( dispatch ).toHaveBeenCalledWith(
				'addError',
				[ {
					type: ErrorTypes.UNSUPPORTED_DATATYPE,
					info: {
						unsupportedDatatype: 'url',
					},
				} ],
			);
		} );

		it( 'dispatches error for non-string data value types', () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				state,
				dispatch,
			} );

			statementModule.getters.dataValueType.mockReturnValueOnce( 'number' );

			// @ts-ignore
			actions.statementModule = statementModule;
			actions.validateBridgeApplicability( new MainSnakPath( 'Q42', 'P42', 0 ) );

			expect( dispatch ).toHaveBeenCalledWith(
				'addError',
				[ { type: ErrorTypes.UNSUPPORTED_DATAVALUE_TYPE } ],
			);
		} );

		it( 'dispatches single error for multiple problems', () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				state,
				dispatch,
			} );

			statementModule.getters.isStatementGroupAmbiguous.mockReturnValueOnce( true );
			statementModule.getters.snakType.mockReturnValueOnce( 'novalue' );
			statementModule.getters.dataType.mockReturnValueOnce( 'url' );
			statementModule.getters.dataValueType.mockReturnValueOnce( 'number' );

			// @ts-ignore
			actions.statementModule = statementModule;
			actions.validateBridgeApplicability( new MainSnakPath( 'Q42', 'P42', 0 ) );

			expect( dispatch ).toHaveBeenCalledTimes( 1 );
			expect( dispatch ).toHaveBeenCalledWith(
				'addError',
				[ { type: ErrorTypes.UNSUPPORTED_AMBIGUOUS_STATEMENT } ],
			);
		} );
	} );

	describe( 'setTargetValue', () => {
		it( `logs an error if the application is not in ${ApplicationStatus.READY} state and rejects`, async () => {
			const commit = jest.fn();
			const state = newApplicationState();
			const actions = inject( RootActions, {
				commit,
				state,
			} );

			await expect( actions.setTargetValue( {} as DataValue ) ).rejects.toBeNull();
			expect( commit ).toHaveBeenCalledTimes( 1 );
			expect( commit.mock.calls[ 0 ][ 0 ] ).toBe( 'addApplicationErrors' );
			const arrayOfActualErrors = commit.mock.calls[ 0 ][ 1 ];
			expect( arrayOfActualErrors.length ).toBe( 1 );
			expect( arrayOfActualErrors[ 0 ].type ).toBe( ErrorTypes.APPLICATION_LOGIC_ERROR );
			expect( arrayOfActualErrors[ 0 ].info ).toHaveProperty( 'stack' );
		} );

		it( 'commits targetValue', async () => {
			const rootModuleCommit = jest.fn();
			const state = newApplicationState( {
				applicationStatus: ApplicationStatus.READY,
			} );
			const actions = inject( RootActions, {
				commit: rootModuleCommit,
				state,
			} );

			const dataValue: DataValue = {
				type: 'string',
				value: 'the value',
			};

			await expect( actions.setTargetValue( dataValue ) ).resolves.toBe( undefined );
			expect( rootModuleCommit ).toHaveBeenCalledWith( 'setTargetValue', dataValue );
		} );
	} );

	describe( 'saveBridge', () => {
		it( `logs an error if the application is not in ${ApplicationStatus.READY} state and rejects`, async () => {
			const commit = jest.fn();
			const state = newApplicationState();
			const actions = inject( RootActions, {
				commit,
				state,
			} );

			await expect( actions.saveBridge() ).rejects.toBeNull();
			expect( commit ).toHaveBeenCalledTimes( 1 );
			expect( commit.mock.calls[ 0 ][ 0 ] ).toBe( 'addApplicationErrors' );
			const arrayOfActualErrors = commit.mock.calls[ 0 ][ 1 ];
			expect( arrayOfActualErrors.length ).toBe( 1 );
			expect( arrayOfActualErrors[ 0 ].type ).toBe( ErrorTypes.APPLICATION_LOGIC_ERROR );
			expect( arrayOfActualErrors[ 0 ].info ).toHaveProperty( 'stack' );
		} );

		// eslint-disable-next-line max-len
		it( `sets the application state to ${ApplicationStatus.SAVING} if there are no errors and sets it to ${ApplicationStatus.SAVED} if saving was successful`, async () => {
			const rootModuleDispatch = jest.fn();
			const commit = jest.fn();
			const entityId = 'Q42';
			const targetPropertyId = 'P42';
			const targetValue: DataValue = {
				type: 'string',
				value: 'a new value',
			};
			const statementsState: StatementState = {
				[ entityId ]: {
					[ targetPropertyId ]: [
						{} as Statement,
					],
				},
			};
			const state = newApplicationState( {
				applicationStatus: ApplicationStatus.READY,
				targetValue,
				targetProperty: targetPropertyId,
				entity: {
					id: entityId,
				},
				statements: statementsState,
			} );
			const actions = inject( RootActions, {
				state,
				commit,
				dispatch: rootModuleDispatch,
			} );
			const entityModuleDispatch = jest.fn( () => Promise.resolve() );

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					purgeTitles: {
						purge: jest.fn().mockReturnValue( Promise.resolve() ),
					},
				} ),
			};

			const statementMutationStrategy = jest.fn().mockReturnValue( statementsState );
			// @ts-ignore
			actions.statementMutationFactory = ( () => ( {
				apply: statementMutationStrategy,
			} ) );

			// @ts-ignore
			actions.entityModule = {
				dispatch: entityModuleDispatch,
			};
			await actions.saveBridge();

			expect( commit ).toHaveBeenCalledTimes( 2 );
			expect( commit ).toHaveBeenNthCalledWith( 1, 'setApplicationStatus', ApplicationStatus.SAVING );
			expect( commit ).toHaveBeenNthCalledWith( 2, 'setApplicationStatus', ApplicationStatus.SAVED );
		} );

		it( 'dispatches entitySave, purges the page & dispatches postEntityLoad', async () => {
			const rootModuleDispatch = jest.fn();
			const commit = jest.fn();
			const entityId = 'Q42';
			const targetPropertyId = 'P42';
			const targetValue: DataValue = {
				type: 'string',
				value: 'a new value',
			};
			const statementsState: StatementState = {
				[ entityId ]: {
					[ targetPropertyId ]: [
						{} as Statement,
					],
				},
			};
			const pageTitle = 'South_Pole_Telescope';
			const state = newApplicationState( {
				applicationStatus: ApplicationStatus.READY,
				targetValue,
				targetProperty: targetPropertyId,
				entity: {
					id: entityId,
				},
				statements: statementsState,
				pageTitle,
			} );
			const purgeTitles: MediaWikiPurge = {
				purge: jest.fn().mockReturnValue( Promise.resolve() ),
			};

			const actions = inject( RootActions, {
				state,
				commit,
				dispatch: rootModuleDispatch,
			} );
			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					purgeTitles,
				} ),
			};

			const entityModuleDispatch = jest.fn( () => Promise.resolve() );

			const statementMutationStrategy = jest.fn().mockReturnValue( statementsState );
			// @ts-ignore
			actions.statementMutationFactory = ( () => ( {
				apply: statementMutationStrategy,
			} ) );

			// @ts-ignore
			actions.entityModule = {
				dispatch: entityModuleDispatch,
			};
			await actions.saveBridge();

			expect( statementMutationStrategy )
				.toHaveBeenCalledWith(
					targetValue,
					new MainSnakPath( entityId, targetPropertyId, 0 ),
					statementsState,
				);
			expect( statementMutationStrategy.mock.calls[ 0 ][ 2 ] ).toStrictEqual( statementsState );
			expect( statementMutationStrategy.mock.calls[ 0 ][ 2 ] ).not.toBe( statementsState );
			expect( entityModuleDispatch ).toHaveBeenCalledWith( 'entitySave', statementsState[ entityId ] );
			expect( purgeTitles.purge ).toHaveBeenCalledWith( [ pageTitle ] );
			expect( rootModuleDispatch ).toHaveBeenCalledWith( 'postEntityLoad' );
		} );

		it( 'gracefully ignores but tracks problems while purging the page', async () => {
			const rootModuleDispatch = jest.fn();
			const commit = jest.fn();
			const entityId = 'Q42';
			const targetPropertyId = 'P42';
			const targetValue: DataValue = {
				type: 'string',
				value: 'a new value',
			};
			const statementsState: StatementState = {
				[ entityId ]: {
					[ targetPropertyId ]: [
						{} as Statement,
					],
				},
			};
			const pageTitle = 'South_Pole_Telescope';
			const state = newApplicationState( {
				applicationStatus: ApplicationStatus.READY,
				targetValue,
				targetProperty: targetPropertyId,
				entity: {
					id: entityId,
				},
				statements: statementsState,
				pageTitle,
			} );
			const purgeTitles: MediaWikiPurge = {
				purge: jest.fn().mockReturnValue( Promise.reject() ),
			};
			const trackTitlePurgeError = jest.fn();

			const actions = inject( RootActions, {
				state,
				commit,
				dispatch: rootModuleDispatch,
			} );
			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					purgeTitles,
					tracker: newMockTracker( {
						trackTitlePurgeError,
					} ),
				} ),
			};

			const entityModuleDispatch = jest.fn( () => Promise.resolve() );

			const statementMutationStrategy = jest.fn().mockReturnValue( statementsState );
			// @ts-ignore
			actions.statementMutationFactory = ( () => ( {
				apply: statementMutationStrategy,
			} ) );

			// @ts-ignore
			actions.entityModule = {
				dispatch: entityModuleDispatch,
			};

			await expect( actions.saveBridge() ).resolves.toBeUndefined();
			expect( trackTitlePurgeError ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'chooses the strategy based on the edit decision', async () => {
			const editDecision = EditDecision.UPDATE;
			const state = newApplicationState( {
				editDecision,
				applicationStatus: ValidApplicationStatus.READY,
				entity: {
					id: 'Q123',
				},
				statements: {},
			} );
			const actions = inject( RootActions, {
				state,
				commit: jest.fn(),
				dispatch: jest.fn(),
			} );

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					purgeTitles: {
						purge: jest.fn().mockReturnValue( Promise.resolve() ),
					},
				} ),
			};

			const statementMutationFactory = jest.fn( ( () => ( {
				apply: jest.fn().mockReturnValue( {} ),
			} ) ) );
			// @ts-ignore
			actions.statementMutationFactory = statementMutationFactory;

			// @ts-ignore
			actions.entityModule = {
				dispatch: jest.fn( () => Promise.resolve() ),
			};

			await actions.saveBridge();
			expect( statementMutationFactory ).toHaveBeenCalledWith( editDecision );
		} );

		it( 'logs an error if statement mutation strategy fails', async () => {
			const entityId = 'Q42';
			const targetPropertyId = 'P42';
			const targetValue: DataValue = {
				type: 'string',
				value: 'a new value',
			};
			const state = newApplicationState( {
				applicationStatus: ApplicationStatus.READY,
				targetValue,
				targetProperty: targetPropertyId,
				entity: {
					id: entityId,
				},
				statements: {},
			} );
			const commit = jest.fn();
			const actions = inject( RootActions, {
				state,
				commit,
			} );

			const error = new Error( 'This error should be logged and propagated.' );
			// @ts-ignore
			actions.statementMutationFactory = ( () => ( {
				apply: jest.fn( () => {
					throw error;
				} ),
			} ) );

			await expect( actions.saveBridge() ).rejects.toBe( error );
			expect( commit ).toHaveBeenCalledWith(
				'addApplicationErrors',
				[ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: error } ],
			);
		} );

		it( 'logs an error if saving failed and rejects', async () => {
			const entityId = 'Q42';
			const targetPropertyId = 'P42';
			const dataValue: DataValue = {
				type: 'string',
				value: 'the value',
			};
			const statementsState: StatementState = {
				[ entityId ]: {
					[ targetPropertyId ]: [
						{} as Statement,
					],
				},
			};
			const state = newApplicationState( {
				applicationStatus: ApplicationStatus.READY,
				targetValue: dataValue,
				targetProperty: targetPropertyId,
				entity: {
					id: entityId,
				},
				statements: statementsState,
			} );

			const commit = jest.fn();
			const actions = inject( RootActions, {
				commit,
				state,
			} );

			const error = new Error( 'This error should be logged and propagated.' );
			const entityModuleDispatch = jest.fn().mockRejectedValue( error );

			// @ts-ignore
			actions.entityModule = {
				dispatch: entityModuleDispatch,
			};

			// @ts-ignore
			actions.statementMutationFactory = ( () => ( {
				apply: jest.fn().mockReturnValue( statementsState ),
			} ) );

			await expect( actions.saveBridge() ).rejects.toBe( error );
			expect( commit ).toHaveBeenCalledWith(
				'addApplicationErrors',
				[ { type: ErrorTypes.SAVING_FAILED, info: error } ],
			);
		} );

	} );

	describe( 'addError', () => {
		it( 'it commits the provided error', () => {
			const commit = jest.fn();
			const actions = inject( RootActions, {
				commit,
			} );
			const errors: ApplicationError[] = [ { type: ErrorTypes.SAVING_FAILED } ];
			actions.addError( errors );
			expect( commit ).toHaveBeenCalledWith( 'addApplicationErrors', errors );
		} );
	} );

	describe( 'setEditDecision', () => {
		it( 'commits the edit decision to the store', () => {
			const commit = jest.fn();
			const actions = inject( RootActions, {
				commit,
			} );
			const editDecision = EditDecision.REPLACE;

			actions.setEditDecision( editDecision );

			expect( commit ).toHaveBeenCalledWith( 'setEditDecision', editDecision );
		} );
	} );
} );
