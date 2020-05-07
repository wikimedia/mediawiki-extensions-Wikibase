<template>
	<div class="wb-db-load">
		<!-- @slot The content which is being initialized and needs to be hidden until that is complete,
		or the content which is being saved and needs to be unchangeable until the saving is complete -->
		<slot v-if="ready" />

		<IndeterminateProgressBar class="wb-db-load__bar" v-if="loadingIsSlow" />
	</div>
</template>

<script lang="ts">
import {
	Prop,
	Vue,
	Watch,
} from 'vue-property-decorator';
import Component from 'vue-class-component';
import { IndeterminateProgressBar } from '@wmde/wikibase-vuejs-components';

/**
 * A component which gets shown to illustrate that an operation is
 * ongoing which temporarily does not allow user interaction.
 *
 * Depending on the run time of the operation an animation is shown.
 *
 * `isInitializing` and `isSaving` are provided to the component to inform it about the
 * state of the application. They should not both be true simultaneously.
 *
 * This
 *
 * * shows the default slot if `isInitializing` and `isSaving` are false (= ready)
 * * while `isInitializing` is true it
 *   * hides the default slot, i.e. shows blank until `TIME_UNTIL_CONSIDERED_SLOW`
 *   * shows the `IndeterminateProgressBar` from there on until `isInitializing` is false
 *   * shows the `IndeterminateProgressBar` for at least `MINIMUM_TIME_OF_PROGRESS_ANIMATION`
 * * while `isSaving` is true it
 *   * shows the default slot
 *   * overlays it with the `IndeterminateProgressBar` from `TIME_UNTIL_CONSIDERED_SLOW` until `isSaving` is false
 *   * shows the `IndeterminateProgressBar` for at least `MINIMUM_TIME_OF_PROGRESS_ANIMATION`
 *
 * Effectively there are three scenarios transitioning to a "ready" state:
 *
 * ```
 * Timeline                 0s                        1s            1.5s            2s
 * Scenario 1
 *   Initializing/Saving    |------------------|
 *   Animation                  (no animation)  <- ready
 * Scenario 2
 *   Initializing/Saving    |----------------------------|
 *   Animation                                        |--------------|<- ready
 * Scenario 3
 *   Initializing/Saving    |---------------------------------------------|
 *   Animation                                        |-------------------|<- ready
 * ```
 */
@Component( {
	components: {
		IndeterminateProgressBar,
	},
} )
export default class Loading extends Vue {
	/**
	 * The state of initializing.
	 * The using component should influence this based on the progress of loading.
	 */
	@Prop( { required: true } )
	private readonly isInitializing!: boolean;

	/**
	 * The state of saving, activated after the "Save changes" button has been clicked.
	 * The using component should influence this based on the progress of saving.
	 */
	@Prop( { required: true } )
	private readonly isSaving!: boolean;

	/**
	 * Number of *milliseconds* before the initializing or saving is considered "slow"
	 * and is illustrated accordingly.
	 */
	@Prop( { default: 1000 } )
	private readonly TIME_UNTIL_CONSIDERED_SLOW!: number;

	/**
	 * Number of *milliseconds* to show the loading animation at least for,
	 * to avoid the impression of "flickering".
	 */
	@Prop( { default: 500 } )
	private readonly MINIMUM_TIME_OF_PROGRESS_ANIMATION!: number;

	@Watch( 'isInitializing', { immediate: true } )
	private onInitStatusChange( isInitializing: boolean, _oldStatus: boolean ): void {
		if ( isInitializing ) {
			this.showLoading();
		} else {
			this.tendTowardsReady();
		}
	}

	@Watch( 'isSaving', { immediate: true } )
	private onSavingStatusChange( isSaving: boolean, _oldStatus: boolean ): void {
		if ( isSaving ) {
			this.showLoading();
		} else {
			this.tendTowardsReady();
		}
	}

	/**
	 * This flag, which determines the rendering of the default slot,
	 * plays a role only in the case of Initializing, where we do not
	 * have the content which needs to be rendered.
	 * In the case of Saving we want the content to be visible, just not clickable.
	 */
	public ready = !this.isInitializing;
	public loadingIsSlow = false;

	private trackSlowness?: ReturnType<typeof setTimeout>;
	private trackAnimation?: ReturnType<typeof setTimeout>;
	private animatedEnough = false;

	private showLoading(): void {
		this.ready = !this.isInitializing;

		this.trackSlowness = setTimeout( () => {
			this.loadingIsSlow = true;
			this.trackAnimation = setTimeout( () => {
				this.animatedEnough = true;
				this.tendTowardsReady();
			}, this.MINIMUM_TIME_OF_PROGRESS_ANIMATION );
		}, this.TIME_UNTIL_CONSIDERED_SLOW );
	}

	private tendTowardsReady(): void {
		if ( ( this.isInitializing || this.isSaving ) || this.loadingIsSlow && !this.animatedEnough ) {
			return;
		}

		this.ready = true;
		this.resetSlownessTracking();
	}

	private resetSlownessTracking(): void {
		this.loadingIsSlow = false;
		this.animatedEnough = false;
		if ( this.trackSlowness ) {
			clearTimeout( this.trackSlowness );
			this.trackSlowness = undefined;
		}
		if ( this.trackAnimation ) {
			clearTimeout( this.trackAnimation );
			this.trackAnimation = undefined;
		}
	}
}
</script>
<style lang="scss">
.wb-db-load {
	&__bar {
		z-index: $stacking-height-loading-bar;
	}
}
</style>
