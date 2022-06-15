<template>
	<div class="wb-db-load">
		<!-- @slot The content which is being initialized and needs to be hidden until that is complete,
		or the content which is being saved and needs to be unchangeable until the saving is complete -->
		<slot v-if="ready" />

		<IndeterminateProgressBar class="wb-db-load__bar" v-if="loadingIsSlow" />
	</div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import IndeterminateProgressBar from '@/presentation/components/IndeterminateProgressBar.vue';

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
export default defineComponent( {
	// eslint-disable-next-line vue/multi-word-component-names
	name: 'Loading',
	components: {
		IndeterminateProgressBar,
	},
	props: {
		/**
		 * The state of initializing.
		 * The using component should influence this based on the progress of loading.
		 */
		isInitializing: {
			type: Boolean,
			required: true,
		},

		/**
		 * The state of saving, activated after the "Save changes" button has been clicked.
		 * The using component should influence this based on the progress of saving.
		 */
		isSaving: {
			type: Boolean,
			required: true,
		},

		/* eslint-disable vue/prop-name-casing */
		/**
		 * Number of *milliseconds* before the initializing or saving is considered "slow"
		 * and is illustrated accordingly.
		 */
		TIME_UNTIL_CONSIDERED_SLOW: {
			type: Number,
			default: 1000,
		},
		/**
		 * Number of *milliseconds* to show the loading animation at least for,
		 * to avoid the impression of "flickering".
		 */
		MINIMUM_TIME_OF_PROGRESS_ANIMATION: {
			type: Number,
			default: 500,
		},
		/* eslint-enable vue/prop-name-casing */
	},
	watch: {
		isInitializing: {
			immediate: true,
			handler( isInitializing: boolean, _oldStatus: boolean ): void {
				if ( isInitializing ) {
					this.showLoading();
				} else {
					this.tendTowardsReady();
				}
			},
		},
		isSaving: {
			immediate: true,
			handler( isSaving: boolean, _oldStatus: boolean ): void {
				if ( isSaving ) {
					this.showLoading();
				} else {
					this.tendTowardsReady();
				}
			},
		},
	},
	data() {
		return {
			/**
			 * This flag, which determines the rendering of the default slot,
			 * plays a role only in the case of Initializing, where we do not
			 * have the content which needs to be rendered.
			 * In the case of Saving we want the content to be visible, just not clickable.
			 */
			ready: !this.isInitializing,
			loadingIsSlow: false,
			trackSlowness: null as ReturnType<typeof setTimeout> | null,
			trackAnimation: null as ReturnType<typeof setTimeout> | null,
			animatedEnough: false,
		};
	},
	methods: {
		showLoading(): void {
			this.ready = !this.isInitializing;

			this.trackSlowness = setTimeout( () => {
				this.loadingIsSlow = true;
				this.trackAnimation = setTimeout( () => {
					this.animatedEnough = true;
					this.tendTowardsReady();
				}, this.MINIMUM_TIME_OF_PROGRESS_ANIMATION );
			}, this.TIME_UNTIL_CONSIDERED_SLOW );
		},
		tendTowardsReady(): void {
			if ( ( this.isInitializing || this.isSaving ) || this.loadingIsSlow && !this.animatedEnough ) {
				return;
			}

			this.ready = true;
			this.resetSlownessTracking();
		},
		resetSlownessTracking(): void {
			this.loadingIsSlow = false;
			this.animatedEnough = false;
			if ( this.trackSlowness ) {
				clearTimeout( this.trackSlowness );
				this.trackSlowness = null;
			}
			if ( this.trackAnimation ) {
				clearTimeout( this.trackAnimation );
				this.trackAnimation = null;
			}
		},
	},
	compatConfig: { MODE: 3 },
} );
</script>
<style lang="scss">
.wb-db-load {
	&__bar {
		z-index: $stacking-height-loading-bar;
	}
}
</style>
