import { createLocalVue, shallowMount } from '@vue/test-utils';
import ErrorUnsupportedDatatype from '@/presentation/components/ErrorUnsupportedDatatype.vue';
import IconMessageBox from '@/presentation/components/IconMessageBox.vue';
import BailoutActions from '@/presentation/components/BailoutActions.vue';
import MessageKeys from '@/definitions/MessageKeys';
import Vuex from 'vuex';
import { calledWithHTMLElement } from '../../../util/assertions';
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
		expect( wrapper.find( IconMessageBox ).exists() ).toBe( true );
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

		calledWithHTMLElement( messageGet, 0, 1 );

		expect( messageGet ).toHaveBeenNthCalledWith(
			1,
			MessageKeys.UNSUPPORTED_DATATYPE_ERROR_HEAD,
			`<span class="wb-db-term-label" lang="zxx" dir="auto">${targetProperty}</span>`,
		);
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

		calledWithHTMLElement( messageGet, 1, 1 );

		expect( messageGet ).toHaveBeenNthCalledWith(
			2,
			MessageKeys.UNSUPPORTED_DATATYPE_ERROR_BODY,
			`<span class="wb-db-term-label" lang="zxx" dir="auto">${targetProperty}</span>`,
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

		expect( wrapper.find( BailoutActions ).exists() ).toBe( true );
		expect( wrapper.find( BailoutActions ).props() ).toStrictEqual( {
			originalHref,
			pageTitle,
		} );
	} );

} );
