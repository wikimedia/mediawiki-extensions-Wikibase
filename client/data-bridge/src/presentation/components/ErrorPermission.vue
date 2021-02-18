<template>
	<section class="wb-db-error-permission">
		<p
			class="wb-db-error-permission__heading"
		>
			{{ $messages.getText( $messages.KEYS.PERMISSIONS_HEADING ) }}
		</p>
		<ErrorPermissionInfo
			class="wb-db-error-permission__info"
			v-for="( permissionError, index ) in permissionErrors"
			:key="index"
			:message-header="getMessageHeader( permissionError )"
			:message-body="getMessageBody( permissionError )"
			:expanded-by-default="permissionErrors.length === 1"
		/>
	</section>
</template>

<script lang="ts">
import { CreateElement } from 'vue';
import {
	Prop,
	Vue,
} from 'vue-property-decorator';
import Component, { mixins } from 'vue-class-component';
import StateMixin from '@/presentation/StateMixin';
import ErrorPermissionInfo from '@/presentation/components/ErrorPermissionInfo.vue';
import PageList from '@/presentation/components/PageList.vue';
import UserLink from '@/presentation/components/UserLink.vue';
import { MissingPermissionsError } from '@/definitions/data-access/BridgePermissionsRepository';
import { PageNotEditable } from '@/definitions/data-access/BridgePermissionsRepository';
import MessageKeys from '@/definitions/MessageKeys';
import MediaWikiRouter from '@/definitions/MediaWikiRouter';
import { VNode } from 'vue/types';

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

export default class ErrorPermission extends mixins( StateMixin ) {
	@Prop( { required: true } )
	private readonly permissionErrors!: MissingPermissionsError[];

	public get entityTitle(): string {
		return this.rootModule.state.entityTitle;
	}

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
			case PageNotEditable.ITEM_CASCADE_PROTECTED:
				params.push(
					'', // unused (not reserved for anything in particular)
					this.$repoRouter.getPageUrl( 'Project:Administrators' ),
				);
				break;
			case PageNotEditable.PAGE_CASCADE_PROTECTED: // temporary; TODO remove once translations no longer use $2
				params.push(
					'', // unused (not reserved for anything in particular)
					this.$clientRouter.getPageUrl( 'Project:Administrators' ),
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
				const blockedByText = this.bdi( blockedBy );
				const blockedByLink = new UserLink( {
					propsData: {
						userId: blockedById,
						userName: blockedBy,
						router: this.$clientRouter,
					},
				} ).$mount().$el as HTMLElement;
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
				const blockedByText = this.bdi( blockedBy );
				const blockedByLink = new UserLink( {
					propsData: {
						userId: blockedById,
						userName: blockedBy,
						router: this.$repoRouter,
					},
				} ).$mount().$el as HTMLElement;
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

	private bdi( text: string ): HTMLElement {
		return new Vue( {
			render( createElement: CreateElement ): VNode {
				return createElement( 'bdi', text );
			},
		} ).$mount().$el as HTMLElement;
	}

	private convertToHtmlList( arr: readonly string[], mwRouter: MediaWikiRouter ): HTMLElement {
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
<style lang="scss">
.wb-db-error-permission {
	@include marginForCenterColumn();

	&__heading {
		@include body-responsive();
	}

	&__info {
		margin-top: 3*$base-spacing-unit;
	}
}
</style>
