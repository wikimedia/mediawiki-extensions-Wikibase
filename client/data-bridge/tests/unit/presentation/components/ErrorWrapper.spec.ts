import ErrorWrapper from '@/presentation/components/ErrorWrapper.vue';
import { createLocalVue, shallowMount } from '@vue/test-utils';
import Vuex, { Store } from 'vuex';
import {
	MissingPermissionsError,
	PageNotEditable,
	ProtectedReason,
} from '@/definitions/data-access/BridgePermissionsRepository';
import ErrorPermission from '@/presentation/components/ErrorPermission.vue';
import Application from '@/store/Application';
import ApplicationError, { ErrorTypes } from '@/definitions/ApplicationError';

const localVue = createLocalVue();
localVue.use( Vuex );

describe( 'ErrorWrapper', () => {
	it( 'shows an (unoffical, hard-coded) generic error text on empty applicationErrors', () => {
		const store = new Store<Partial<Application>>( {
			state: {
				applicationErrors: [],
			},
		} );
		const wrapper = shallowMount( ErrorWrapper, { localVue, store } );
		expect( wrapper.html() ).toContain( 'An error occurred' );
		expect( wrapper.find( ErrorPermission ).exists() ).toBeFalsy();
	} );

	it( 'shows an (unoffical, hard-coded) generic error text for non-permission-related errors', () => {
		const store = new Store<Partial<Application>>( {
			state: {
				applicationErrors: [
					{
						type: ErrorTypes.INVALID_ENTITY_STATE_ERROR,
					},
				],
			},
		} );
		const wrapper = shallowMount( ErrorWrapper, { localVue, store } );
		expect( wrapper.html() ).toContain( 'An error occurred' );
		expect( wrapper.find( ErrorPermission ).exists() ).toBeFalsy();
	} );

	it( 'shows ErrorPermission if a permission error is contained in the application errors', () => {
		const applicationErrors: ApplicationError[] = [
			{
				type: PageNotEditable.ITEM_SEMI_PROTECTED,
			} as ProtectedReason,
		];
		const store = new Store<Partial<Application>>( {
			state: {
				applicationErrors,
			},
		} );
		const wrapper = shallowMount( ErrorWrapper, {
			localVue,
			store,
		} );

		const permissionErrorComponent = wrapper.find( ErrorPermission );
		expect( permissionErrorComponent.exists() ).toBeTruthy();
		expect( permissionErrorComponent.props( 'permissionErrors' ) ).toEqual( applicationErrors );
		expect( wrapper.html() ).not.toContain( 'An error occurred' );
	} );

	// TODO find an answer to what is actually the desired behavior
	// dev first needs to come clear which combinations of errors are even possible not to confuse UX
	it( 'shows only ErrorPermission even if permission errors are mixed with other application errors', () => {
		const permissionErrors: MissingPermissionsError[] = [
			{
				type: PageNotEditable.ITEM_SEMI_PROTECTED,
			} as ProtectedReason,
		];
		const applicationErrors: ApplicationError[] = [
			...permissionErrors,
			{
				type: ErrorTypes.INVALID_ENTITY_STATE_ERROR,
			},
		];
		const store = new Store<Partial<Application>>( {
			state: {
				applicationErrors,
			},
		} );
		const wrapper = shallowMount( ErrorWrapper, {
			localVue,
			store,
		} );

		const permissionErrorComponent = wrapper.find( ErrorPermission );
		expect( permissionErrorComponent.exists() ).toBeTruthy();
		expect( permissionErrorComponent.props( 'permissionErrors' ) ).toEqual( permissionErrors );
		expect( wrapper.html() ).not.toContain( 'An error occurred' );
	} );
} );
