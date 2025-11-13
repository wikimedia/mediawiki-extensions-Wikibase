jest.mock(
	'../../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);

const { mockLibWbui2025 } = require( '../libWbui2025Helpers.js' );
mockLibWbui2025();
const { createTestingPinia } = require( '@pinia/testing' );
const statusMessageComponent = require( '../../../resources/wikibase.wbui2025/components/statusMessage.vue' );
const { CdxMessage } = require( '../../../codex.js' );
const { mount } = require( '@vue/test-utils' );

describe( 'wikibase.wbui2025.statusMessage', () => {
	it( 'defines component', async () => {
		expect( typeof statusMessageComponent ).toBe( 'object' );
		expect( statusMessageComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025StatusMessage' );
	} );

	describe( 'the mounted component', () => {
		function mountStatusMessage( initialState = {} ) {
			return mount( statusMessageComponent, {
				global: {
					plugins: [ createTestingPinia( {
						initialState
					} ) ]
				}
			} );
		}

		it( 'the component and child components mount successfully', async () => {
			const wrapper = await mountStatusMessage( {
				message: {
					messages: new Map( [ [ 1, { text: 'something' } ] ] )
				}
			} );
			expect( wrapper.exists() ).toBe( true );

			const message = wrapper.findComponent( CdxMessage );
			expect( message.exists() ).toBe( true );
		} );

		it( 'shows no messages if the store is empty', async () => {
			const wrapper = await mountStatusMessage( { } );
			expect( wrapper.exists() ).toBe( true );

			const message = wrapper.findComponent( CdxMessage );
			expect( message.exists() ).toBe( false );
		} );

		it( 'sets the properties of the messages', async () => {
			const wrapper = await mountStatusMessage( {
				message: {
					messages: new Map( [
						[ 1, { text: 'something' } ],
						[ 2, { text: 'something else', type: 'error' } ]
					] )
				}
			} );
			const messages = wrapper.findAllComponents( CdxMessage );
			const firstMessage = messages.at( 0 );
			expect( firstMessage.text() ).toBe( 'something' );
			expect( firstMessage.props( 'type' ) ).toBe( 'success' );
			const secondMessage = messages.at( 1 );
			expect( secondMessage.text() ).toBe( 'something else' );
			expect( secondMessage.props( 'type' ) ).toBe( 'error' );
		} );
	} );
} );
