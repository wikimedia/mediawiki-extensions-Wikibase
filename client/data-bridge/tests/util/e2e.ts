export async function insert( element: HTMLTextAreaElement, value: string ): Promise<void> {
	const event = new Event( 'input' );
	element.value = value;
	element.dispatchEvent( event );
}

export function select( selector: string, nth: number = 0 ): HTMLElement|null {
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
