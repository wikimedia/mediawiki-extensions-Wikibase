import { SelectedElement } from '@/mediawiki/SelectedElement';
import EditFlow from '@/datamodel/EditFlow';

export default class BridgeDomElementsSelector {

	private readonly hrefRegExp: RegExp;

	private readonly ENTITY_ID_REGEX_INDEX = 1;
	private readonly PROPERTY_ID_REGEX_INDEX = 2;

	public constructor( hrefRegExp: string ) {
		this.hrefRegExp = new RegExp( hrefRegExp );
	}

	private extractDataFromLink( link: HTMLAnchorElement ): { entityID: string; propertyID: string } | null {
		const match = link.href.match( this.hrefRegExp );
		if ( match && match[ this.ENTITY_ID_REGEX_INDEX ] && match[ this.PROPERTY_ID_REGEX_INDEX ] ) {
			return {
				entityID: match[ this.ENTITY_ID_REGEX_INDEX ],
				propertyID: match[ this.PROPERTY_ID_REGEX_INDEX ],
			};
		}

		return null;
	}

	private validateEditFlow( editFlow: string|undefined ): EditFlow|null {
		if ( !editFlow ) {
			return null;
		}

		if ( !Object.values( EditFlow ).includes( editFlow ) ) {
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
