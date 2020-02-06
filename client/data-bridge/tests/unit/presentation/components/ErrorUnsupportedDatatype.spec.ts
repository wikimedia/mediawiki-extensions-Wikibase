import { createLocalVue, shallowMount } from '@vue/test-utils';
import ErrorUnsupportedDatatype from '@/presentation/components/ErrorUnsupportedDatatype.vue';
import IconMessageBox from '@/presentation/components/IconMessageBox.vue';
import BailoutActions from '@/presentation/components/BailoutActions.vue';
import MessageKeys from '@/definitions/MessageKeys';
import Vuex from 'vuex';
import { createTestStore } from '../../../util/store';

const localVue = createLocalVue();
localVue.use( Vuex );

describe( 'ErrorUnsupportedDatatype', () => {
	const targetProperty = 'P569',
		pageTitle = 'Marie_Curie',
		originalHref = 'https://www.wikidata.org/wiki/Q7186',
		dataType = 'time',
		messageGet = jest.fn( ( key ) => key ),
		store = createTestStore( {
			state: {
				targetProperty,
				pageTitle,
				originalHref,
			},
		} );

	it( 'uses IconMessageBox to display the error message', () => {
		const wrapper = shallowMount( ErrorUnsupportedDatatype, {
			localVue,
			propsData: {
				dataType,
			},
			mocks: {
				$messages: {
					KEYS: MessageKeys,
					get: messageGet,
				},
			},
			store,
		} );
		expect( wrapper.find( IconMessageBox ).exists() ).toBeTruthy();
	} );

	it( 'passes a slot to IconMessageBox which contains the header message', () => {
		shallowMount( ErrorUnsupportedDatatype, {
			localVue,
			propsData: {
				dataType,
			},
			mocks: {
				$messages: {
					KEYS: MessageKeys,
					get: messageGet,
				},
			},
			store,
		} );

		expect( messageGet ).toHaveBeenNthCalledWith( 1, MessageKeys.UNSUPPORTED_DATATYPE_ERROR_HEAD, targetProperty );
	} );

	it( 'shows the message body', () => {
		shallowMount( ErrorUnsupportedDatatype, {
			localVue,
			propsData: {
				dataType,
			},
			mocks: {
				$messages: {
					KEYS: MessageKeys,
					get: messageGet,
				},
			},
			store,
		} );

		expect( messageGet ).toHaveBeenNthCalledWith(
			2,
			MessageKeys.UNSUPPORTED_DATATYPE_ERROR_BODY,
			targetProperty,
			dataType,
		);
	} );

	it( 'uses BailoutActions to provide a bail out path for unsupported data type', () => {
		const wrapper = shallowMount( ErrorUnsupportedDatatype, {
			localVue,
			propsData: {
				dataType,
			},
			mocks: {
				$messages: {
					KEYS: MessageKeys,
					get: messageGet,
				},
			},
			store,
		} );

		expect( wrapper.find( BailoutActions ).exists() ).toBeTruthy();
		expect( wrapper.find( BailoutActions ).props() ).toStrictEqual( {
			originalHref,
			pageTitle,
		} );
	} );

} );
