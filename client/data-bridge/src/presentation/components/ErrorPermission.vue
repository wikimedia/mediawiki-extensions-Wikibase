<template>
	<section>
		<h2>{{ $messages.get( $messages.KEYS.PERMISSIONS_HEADING ) }}</h2>
		<ErrorPermissionInfo
			v-for="( permissionError, index ) in permissionErrors"
			:key="index"
			:message-header="getMessageHeader( permissionError )"
			:message-body="getMessageBody( permissionError )"
		/>
	</section>
</template>

<script lang="ts">
import {
	Prop,
	Vue,
} from 'vue-property-decorator';
import Component from 'vue-class-component';
import { State } from 'vuex-class';
import ErrorPermissionInfo from '@/presentation/components/ErrorPermissionInfo.vue';
import PageList from '@/presentation/components/PageList.vue';
import { MissingPermissionsError } from '@/definitions/data-access/BridgePermissionsRepository';
import { PageNotEditable } from '@/definitions/data-access/BridgePermissionsRepository';
import MessageKeys from '@/definitions/MessageKeys';
import MediaWikiRouter from '@/definitions/MediaWikiRouter';

interface PermissionTypeRenderer {
	header: keyof typeof MessageKeys;
	body: keyof typeof MessageKeys;
}

type PermissionTypeMessageRenderers = {
	[ key in PageNotEditable ]: PermissionTypeRenderer
};

const permissionTypeRenderers: PermissionTypeMessageRenderers = {
	[ PageNotEditable.ITEM_FULLY_PROTECTED ]: {
		header: 'PERMISSIONS_PROTECTED_HEADING',
		body: 'PERMISSIONS_PROTECTED_BODY',
	},
	[ PageNotEditable.ITEM_SEMI_PROTECTED ]: {
		header: 'PERMISSIONS_SEMI_PROTECTED_HEADING',
		body: 'PERMISSIONS_SEMI_PROTECTED_BODY',
	},
	[ PageNotEditable.ITEM_CASCADE_PROTECTED ]: {
		header: 'PERMISSIONS_CASCADE_PROTECTED_HEADING',
		body: 'PERMISSIONS_CASCADE_PROTECTED_BODY',
	},
	[ PageNotEditable.BLOCKED_ON_CLIENT_PAGE ]: {
		header: 'PERMISSIONS_BLOCKED_ON_CLIENT_HEADING',
		body: 'PERMISSIONS_BLOCKED_ON_CLIENT_BODY',
	},
	[ PageNotEditable.BLOCKED_ON_REPO_ITEM ]: {
		header: 'PERMISSIONS_BLOCKED_ON_REPO_HEADING',
		body: 'PERMISSIONS_BLOCKED_ON_REPO_BODY',
	},
	[ PageNotEditable.PAGE_CASCADE_PROTECTED ]: {
		header: 'PERMISSIONS_PAGE_CASCADE_PROTECTED_HEADING',
		body: 'PERMISSIONS_PAGE_CASCADE_PROTECTED_BODY',
	},
	[ PageNotEditable.UNKNOWN ]: {
		header: 'PERMISSIONS_ERROR_UNKNOWN_HEADING',
		body: 'PERMISSIONS_ERROR_UNKNOWN_BODY',
	},
};

@Component( {
	components: {
		ErrorPermissionInfo,
	},
} )

export default class ErrorPermission extends Vue {
	@Prop( { required: true } )
	private readonly permissionErrors!: MissingPermissionsError[];
	@State( 'entityTitle' )
	public entityTitle!: string;

	public getMessageHeader( permissionError: MissingPermissionsError ): string {
		return this.$messages.get(
			this.$messages.KEYS[ this.messageHeaderKey( permissionError ) ],
			...this.messageHeaderParameters( permissionError ),
		);
	}

	public getMessageBody( permissionError: MissingPermissionsError ): string {
		return this.$messages.get(
			this.$messages.KEYS[ this.messageBodyKey( permissionError ) ],
			...this.messageBodyParameters( permissionError ),
		);
	}

	/** A poor (wo)man's implementation of constructing a correct
		talk page title due to lack of a redirect functionality.
		This can be removed once T242346 is resolved.
	*/
	private buildTalkPageNamespace(): string {
		if ( this.entityTitle.includes( ':' ) ) {
			const entityTitleParts: string[] = this.entityTitle.split( ':', 2 );
			return `${entityTitleParts[ 0 ]}_talk:${entityTitleParts[ 1 ]}`;
		}
		return `Talk:${this.entityTitle}`;
	}

	private messageHeaderKey( permissionError: MissingPermissionsError ): ( keyof typeof MessageKeys ) {
		return permissionTypeRenderers[ permissionError.type ].header;
	}

	private messageBodyKey( permissionError: MissingPermissionsError ): ( keyof typeof MessageKeys ) {
		return permissionTypeRenderers[ permissionError.type ].body;
	}

	private messageHeaderParameters( permissionError: MissingPermissionsError ): string[] {
		const params: string[] = [];
		switch ( permissionError.type ) {
			case PageNotEditable.ITEM_FULLY_PROTECTED:
				params.push(
					this.$repoRouter.getPageUrl( 'Project:Page_protection_policy' ),
					this.$repoRouter.getPageUrl( 'Project:Administrators' ),
				);
				break;
			case PageNotEditable.ITEM_SEMI_PROTECTED:
				params.push(
					this.$repoRouter.getPageUrl( 'Project:Page_protection_policy' ),
					this.$repoRouter.getPageUrl( 'Project:Autoconfirmed_users' ),
				);
				break;
		}
		return params;
	}

	private messageBodyParameters( permissionError: MissingPermissionsError ): ( string|HTMLElement )[] {
		const params: ( string|HTMLElement )[] = [];
		switch ( permissionError.type ) {
			case PageNotEditable.BLOCKED_ON_CLIENT_PAGE: {
				const {
					blockedBy,
					blockedById,
					blockReason,
					blockId,
					blockExpiry,
					blockedTimestamp,
				} = permissionError.info;
				const blockedByText = document.createElement( 'bdi' );
				blockedByText.textContent = blockedBy;
				let blockedByLink;
				if ( blockedById > 0 ) {
					blockedByLink = document.createElement( 'a' );
					blockedByLink.appendChild( blockedByText.cloneNode( true ) );
					blockedByLink.href = this.$clientRouter.getPageUrl( `Special:Redirect/user/${blockedById}` );
				} else {
					// not a local user, no link
					blockedByLink = blockedByText.cloneNode( true ) as HTMLElement;
				}
				params.push(
					blockedByLink,
					blockReason,
					'', // reserved for currentIP
					blockedByText,
					blockId.toString(),
					blockExpiry,
					'', // reserved for intended blockee
					blockedTimestamp,
				);
				break;
			}
			case PageNotEditable.BLOCKED_ON_REPO_ITEM: {
				const {
					blockedBy,
					blockedById,
					blockReason,
					blockId,
					blockExpiry,
					blockedTimestamp,
				} = permissionError.info;
				const blockedByText = document.createElement( 'bdi' );
				blockedByText.textContent = blockedBy;
				let blockedByLink;
				if ( blockedById > 0 ) {
					blockedByLink = document.createElement( 'a' );
					blockedByLink.appendChild( blockedByText.cloneNode( true ) );
					blockedByLink.href = this.$repoRouter.getPageUrl( `Special:Redirect/user/${blockedById}` );
				} else {
					// not a local user, no link
					blockedByLink = blockedByText.cloneNode( true ) as HTMLElement;
				}
				params.push(
					blockedByLink,
					blockReason,
					'', // reserved for currentIP
					blockedByText,
					blockId.toString(),
					blockExpiry,
					'', // reserved for intended blockee
					blockedTimestamp,
					this.$repoRouter.getPageUrl( 'Project:Administrators' ),
				);
				break;
			}
			case PageNotEditable.ITEM_FULLY_PROTECTED:
				params.push(
					this.$repoRouter.getPageUrl( 'Project:Page_protection_policy' ),
					this.$repoRouter.getPageUrl( 'Project:Project:Edit_warring' ),
					this.$repoRouter.getPageUrl( 'Special:Log/protect', { page: this.entityTitle } ),
					this.$repoRouter.getPageUrl( this.buildTalkPageNamespace() ),
				);
				break;
			case PageNotEditable.ITEM_SEMI_PROTECTED:
				params.push(
					this.$repoRouter.getPageUrl( 'Special:Log/protect', { page: this.entityTitle } ),
					this.$repoRouter.getPageUrl( this.buildTalkPageNamespace() ),
				);
				break;
			case PageNotEditable.ITEM_CASCADE_PROTECTED:
				params.push(
					permissionError.info.pages.length.toString(),
					this.convertToHtmlList( permissionError.info.pages, this.$repoRouter ),
				);
				break;
			case PageNotEditable.PAGE_CASCADE_PROTECTED:
				params.push(
					permissionError.info.pages.length.toString(),
					this.convertToHtmlList( permissionError.info.pages, this.$clientRouter ),
				);
				break;
		}
		return params;
	}

	private convertToHtmlList( arr: string[], mwRouter: MediaWikiRouter ): HTMLElement {
		const pageListInstance = new PageList( {
			propsData: {
				pages: arr,
				router: mwRouter,
			},
		} );
		pageListInstance.$mount();
		return pageListInstance.$el as HTMLElement;
	}
}
</script>
