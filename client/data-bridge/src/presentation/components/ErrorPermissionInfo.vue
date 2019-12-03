<template>
	<div class="wb-ui-permission-info-box">
		<div class="wb-ui-permission-info-box__info__icon">
			<div class="wb-ui-permission-info-box__header" v-html="messageHeader" />
		</div>
		<div class="wb-ui-permission-info-box__body">
			<div
				:class="[ infoIsExpanded ?
					'wb-ui-permission-info-box__icon--collapsed' :
					'wb-ui-permission-info-box__icon--expanded' ]"
				@click="toggleInfo"
			>
				<a class="wb-ui-permission-info-box__body__title">
					{{ this.$messages.get( this.$messages.KEYS.PERMISSIONS_MORE_INFO ) }}
				</a>
			</div>
			<div v-if="infoIsExpanded" v-html="messageBody" />
		</div>
	</div>
</template>

<script lang="ts">
import {
	Prop,
	Vue,
} from 'vue-property-decorator';
import Component from 'vue-class-component';

@Component
export default class ErrorPermissionInfo extends Vue {
	public infoIsExpanded = false;

	@Prop( { required: true } )
	private readonly messageHeader!: string;
	@Prop( { required: true } )
	private readonly messageBody!: string;

	private toggleInfo(): void {
		this.infoIsExpanded = !this.infoIsExpanded;
	}
}
</script>

<style lang="scss">
.wb-ui-permission-info-box {
	&__header {
		margin-left: 2em;
	}

	&__info__icon,
	&__icon--collapsed,
	&__icon--expanded {
		line-height: $size-icon;
		box-sizing: border-box;
		text-align: left;
		position: relative;
	}

	&__body {
		padding-left: 2em;

		&__title {
			margin-left: 2em;
		}
	}

	&__info__icon:before {
		background-image: $svg-info;
		background-repeat: no-repeat;
		background-size: contain;
		min-width: $min-size-icon;
		min-height: $min-size-icon;
		width: $size-icon;
		height: 100%;
		top: 0;
		position: absolute;
		content: '';
	}

	&__icon--collapsed:before {
		background-image: $svg-collapse;
		background-repeat: no-repeat;
		background-size: contain;
		min-width: $min-size-icon;
		min-height: $min-size-icon;
		width: $size-icon;
		height: 100%;
		top: 0;
		position: absolute;
		content: '';
	}

	&__icon--expanded:before {
		background-image: $svg-expand;
		background-repeat: no-repeat;
		background-size: contain;
		min-width: $min-size-icon;
		min-height: $min-size-icon;
		width: $size-icon;
		height: 100%;
		top: 0;
		position: absolute;
		content: '';
	}
}
</style>
