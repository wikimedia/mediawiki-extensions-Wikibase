export async function insert( element: HTMLTextAreaElement, value: string ): Promise<void> {
	const event = new Event( 'input' );
	element.value = value;
	element.dispatchEvent( event );
}

export async function selectRadioInput( element: HTMLInputElement ): Promise<void> {
	const event = new MouseEvent( 'click' );
	element.dispatchEvent( event );
}

export function select( selector: string, nth = 0 ): HTMLElement|null {
	if ( nth === 0 ) {
		return document.querySelector( selector );
	} else {
		const list = document.querySelectorAll( selector );
		if ( !list ) {
			return list;
		}

		return list.item( nth ) as HTMLElement;
	}
}

export async function keydown( element: HTMLElement, key: string ): Promise<void> {
	const event = new KeyboardEvent(
		'keydown', {
			bubbles: true,
			key,
		},
	);
	element.dispatchEvent( event );
}

export async function keypressed( element: HTMLElement, key: string ): Promise<void> {
	const event = new KeyboardEvent(
		'keypress', {
			bubbles: true,
			key,
		},
	);
	element.dispatchEvent( event );
}

export async function keyup( element: HTMLElement, key: string ): Promise<void> {
	const event = new KeyboardEvent(
		'keyup', {
			bubbles: true,
			key,
		},
	);
	element.dispatchEvent( event );
}

// @see: https://developer.mozilla.org/en-US/docs/Web/API/KeyboardEvent#Auto-repeat_handling
export async function enter( element: HTMLElement ): Promise<void> {
	await keydown( element, 'Enter' );
	await keypressed( element, 'Enter' );
	if ( element instanceof HTMLAnchorElement && element.href ) {
		await element.click(); // links emit 'click' on Enter
	}
	await keyup( element, 'enter' );
}

export async function space( element: HTMLElement ): Promise<void> {
	await keydown( element, ' ' );
	await keypressed( element, ' ' );
	await keyup( element, ' ' );
}
