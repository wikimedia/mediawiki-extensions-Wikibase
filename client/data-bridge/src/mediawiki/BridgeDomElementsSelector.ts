import EditFlow from '@/definitions/EditFlow';
import { SelectedElement } from '@/mediawiki/SelectedElement';

interface LinkData {
	entityTitle: string;
	entityId: string;
	propertyId: string;
}

export default class BridgeDomElementsSelector {

	private readonly hrefRegExp: RegExp;

	private readonly ENTITY_ID_REGEX_INDEX = 2;
	private readonly PROPERTY_ID_REGEX_INDEX = 3;
	private readonly ENTITY_TITLE_REGEX_INDEX = 1;

	public constructor( hrefRegExp: string ) {
		this.hrefRegExp = new RegExp( hrefRegExp );
	}

	private extractDataFromLink( link: HTMLAnchorElement ): LinkData | null {
		const match = link.href.match( this.hrefRegExp );
		if ( match &&
			match[ this.ENTITY_ID_REGEX_INDEX ] &&
			match[ this.PROPERTY_ID_REGEX_INDEX ] &&
			match[ this.ENTITY_TITLE_REGEX_INDEX ] ) {
			return {
				entityId: match[ this.ENTITY_ID_REGEX_INDEX ],
				propertyId: match[ this.PROPERTY_ID_REGEX_INDEX ],
				entityTitle: match[ this.ENTITY_TITLE_REGEX_INDEX ],
			};
		}

		return null;
	}

	private validateEditFlow( editFlow: string|undefined ): EditFlow|null {
		if ( !editFlow ) {
			return null;
		}

		if ( !( Object.values( EditFlow ) as string[] ).includes( editFlow ) ) {
			return null;
		}

		return ( editFlow as EditFlow );
	}

	private getEditFlowFromParent( parentElement: HTMLElement ): EditFlow|null {
		if ( parentElement.querySelectorAll( 'a' ).length !== 1 ) {
			return null;
		}
		return this.validateEditFlow( parentElement.dataset.bridgeEditFlow );
	}

	private extractDataFromElement( link: HTMLAnchorElement ): SelectedElement | null {
		const editFlow = this.getEditFlowFromParent( link.parentElement as HTMLElement );
		if ( !editFlow ) {
			return null;
		}

		const editLinkData = this.extractDataFromLink( link );
		if ( editLinkData === null ) {
			return null;
		}

		return {
			...editLinkData,
			link,
			editFlow,
		};
	}

	public selectElementsToOverload(): SelectedElement[] {
		const selectedLinks = Array.from( document.querySelectorAll( '[data-bridge-edit-flow] > a' ) );

		const hydratedElements = selectedLinks.map(
			( element: Element ) => this.extractDataFromElement( ( element as HTMLAnchorElement ) ),
		);

		return hydratedElements.filter( ( e: SelectedElement | null ): e is SelectedElement => e !== null );
	}

}
