import prepareContainer from '@/mediawiki/prepareContainer';
import {
	MwWindowOO,
	PanelLayout,
	WindowManager,
} from '@/@types/mediawiki/MwWindow';

function mockOOEnv(): any {
	const windowManager: WindowManager = {
		addWindows: jest.fn(),
		openWindow: jest.fn(),
		$element: new ( jest.fn() )(),
		destroy: jest.fn(),
		clearWindows: jest.fn(),
		emit: jest.fn(),
		emitThrow: jest.fn(),
		off: jest.fn(),
		on: jest.fn(),
		once: jest.fn(),
	};

	// simulating base Dialog
	const Dialog: any = function ( this: any, _config: object ) {
		this.initialize();
	};
	Dialog.prototype.initialize = function () {
		this.$body = {
			append: jest.fn(),
		} as unknown as JQuery;
	};

	const panelLayout: PanelLayout = {
		$element: {
			attr: jest.fn(),
		} as unknown as JQuery,
	};

	const panelLayoutConstructor = jest.fn( () => panelLayout );
	const OO: MwWindowOO = {
		ui: {
			Dialog,
			PanelLayout: panelLayoutConstructor,
			WindowManager: jest.fn( () => windowManager ),
		},
		inheritClass: jest.fn( ( child: any, parent: any ) => {
			child.static = {
				name: '',
			};

			child.parent = parent;
		} ),
	};

	const documentBodyAppend = jest.fn();
	const $ = jest.fn( () => ( {
		append: documentBodyAppend,
	} ) );

	return {
		OO,
		$,
		windowManager,
		panelLayout,
		panelLayoutConstructor,
		documentBodyAppend,
	};
}

describe( 'prepareContainer', () => {
	it( 'adds Dialog window to WindowManager', () => {
		const {
			OO,
			$,
			windowManager,
			panelLayout,
			panelLayoutConstructor,
			documentBodyAppend,
		} = mockOOEnv();
		const APP_DOM_CONTAINER_ID = 'data-bridge-container';

		const myDialog = prepareContainer( OO, $, APP_DOM_CONTAINER_ID );

		expect( ( myDialog.constructor as any ).static.name ).toBe( 'data-bridge' );
		expect( ( myDialog.constructor as any ).static.escapable ).toBeFalsy();

		expect( panelLayoutConstructor )
			.toHaveBeenCalledWith( { padded: false, expanded: false } );
		expect( panelLayout.$element.attr )
			.toHaveBeenCalledWith( 'id', APP_DOM_CONTAINER_ID );

		expect( myDialog.$body.append ).toHaveBeenCalledWith( myDialog.content.$element );

		expect( documentBodyAppend ).toHaveBeenCalledWith( windowManager.$element );

		expect( windowManager.addWindows ).toHaveBeenCalledTimes( 1 );
		expect( windowManager.addWindows ).toHaveBeenCalledWith( [ myDialog ] );

		expect( windowManager.openWindow ).toHaveBeenCalledTimes( 1 );
		expect( windowManager.openWindow ).toHaveBeenCalledWith( myDialog );
	} );
} );
