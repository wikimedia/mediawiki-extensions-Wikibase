import ApplicationError, { ErrorTypes } from '@/definitions/ApplicationError';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import EditDecision from '@/definitions/EditDecision';
import EditFlow from '@/definitions/EditFlow';
import Application from '@/store/Application';
import { RootMutations } from '@/store/mutations';
import newApplicationState from './newApplicationState';
import { inject } from 'vuex-smart-module';

describe( 'root/mutations', () => {
	it( 'changes the targetProperty of the state', () => {
		const state: Application = newApplicationState();

		const mutations = inject( RootMutations, { state } );

		mutations.setPropertyPointer( 'P42' );
		expect( state.targetProperty ).toBe( 'P42' );
	} );

	it( 'changes the editFlow of the state', () => {
		const editFlow: EditFlow = EditFlow.OVERWRITE;
		const state: Application = newApplicationState();

		const mutations = inject( RootMutations, { state } );
		mutations.setEditFlow( editFlow );
		expect( state.editFlow ).toBe( editFlow );
	} );

	it( 'changes the originalHref of the state', () => {
		const state: Application = newApplicationState();
		const originalHref = 'https://example.com/index.php?title=Item:Q42&uselang=en#P31';

		const mutations = inject( RootMutations, { state } );

		mutations.setOriginalHref( originalHref );
		expect( state.originalHref ).toBe( originalHref );
	} );

	it( 'changes the applicationStatus of the state', () => {
		const state: Application = newApplicationState();
		const mutations = inject( RootMutations, { state } );
		mutations.setApplicationStatus( ApplicationStatus.READY );
		expect( state.applicationStatus ).toBe( ApplicationStatus.READY );
	} );

	it( 'changes the targetLabel of the state', () => {
		const targetLabel = { language: 'el', value: 'πατατα' };
		const state: Application = newApplicationState();
		const mutations = inject( RootMutations, { state } );

		mutations.setTargetLabel( targetLabel );
		expect( state.targetLabel ).toBe( targetLabel );
	} );

	it( 'changes the originalStatement of the state', () => {
		const targetProperty = {
			type: 'statement' as any,
			id: 'opaque statement ID',
			rank: 'normal' as any,
			mainsnak: {
				snaktype: 'novalue' as any,
				property: 'P60',
				datatype: 'string',
			},
		};
		const state: Application = newApplicationState();

		const mutations = inject( RootMutations, { state } );

		mutations.setOriginalStatement( targetProperty );
		expect( state.originalStatement ).not.toBe( targetProperty );
		expect( state.originalStatement ).toStrictEqual( targetProperty );
	} );

	it( 'adds errors to the state', () => {
		const state: Application = newApplicationState();
		const errors: ApplicationError[] = [ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: {} } ];

		const mutations = inject( RootMutations, { state } );

		mutations.addApplicationErrors( errors );
		expect( state.applicationErrors ).toStrictEqual( errors );
	} );

	it( 'does not drop existing errors when adding new ones to the state', () => {
		const oldErrors: ApplicationError[] = [ { type: ErrorTypes.INVALID_ENTITY_STATE_ERROR } ];
		const state: Application = newApplicationState( { applicationErrors: oldErrors.slice() } );
		const newErrors: ApplicationError[] = [ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: {} } ];

		const mutations = inject( RootMutations, { state } );

		mutations.addApplicationErrors( newErrors );
		expect( state.applicationErrors ).toStrictEqual( [ ...oldErrors, ...newErrors ] );
	} );

	it( 'sets the edit decision of the state', () => {
		const state: Application = newApplicationState();
		const editDecision = EditDecision.REPLACE;

		const mutations = inject( RootMutations, { state } );

		mutations.setEditDecision( editDecision );
		expect( state.editDecision ).toBe( editDecision );
	} );

	it( 'sets the entity title of the state', () => {
		const state: Application = newApplicationState();
		const entityTitle = 'Entity title';

		const mutations = inject( RootMutations, { state } );

		mutations.setEntityTitle( entityTitle );
		expect( state.entityTitle ).toBe( entityTitle );
	} );

	it( 'sets the page title of the store', () => {
		const state: Application = newApplicationState();
		const pageTitle = 'Page_title';

		const mutations = inject( RootMutations, { state } );

		mutations.setPageTitle( pageTitle );
		expect( state.pageTitle ).toBe( pageTitle );
	} );
} );
