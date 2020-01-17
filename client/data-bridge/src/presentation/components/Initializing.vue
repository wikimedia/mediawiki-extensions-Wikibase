<template>
	<div class="wb-db-init">
		<!-- @slot The content which is being initialized and needs to be hidden until that is complete. -->
		<slot v-if="ready" />
		<template v-else>
			<IndeterminateProgressBar v-if="loadingIsSlow" />
		</template>
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
 * This
 *
 * * shows the default slot if `isInitializing` is false (= ready)
 * * hides the default slot while `isInitializing` is true; during that time it
 *   * shows blank until `TIME_UNTIL_CONSIDERED_SLOW`
 *   * shows the `IndeterminateProgressBar` from there on until `isInitializing` is false
 *   * shows the `IndeterminateProgressBar` for at least `MINIMUM_TIME_OF_PROGRESS_ANIMATION`[1]
 *
 * [1] This condition is only applied while `isInitializing` is true
 *
 * Effectively there are three scenarios:
 *
 * ```
 * Timeline     0s                        1s            1.5s            2s
 * Scenario 1
 *   Loading    |------------------|
 *   Animation      (no animation)  <- ready
 * Scenario 2
 *   Loading    |----------------------------|
 *   Animation                            |--------------|<- ready
 * Scenario 3
 *   Loading    |---------------------------------------------|
 *   Animation                            |-------------------|<- ready
 * ```
 */
@Component( {
	components: {
		IndeterminateProgressBar,
	},
} )
export default class Initializing extends Vue {
	/**
	 * The state (still initializing or "done"). The using component should
	 * influence this based on the progress of loading what is needed to
	 * show the contents of the default slot.
	 */
	@Prop( { required: true } )
	private readonly isInitializing!: boolean;

	/**
	 * Number of *milliseconds* before the initializing is considered "slow"
	 * and is illustrated accordingly.
	 */
	@Prop( { default: 1000 } )
	private readonly TIME_UNTIL_CONSIDERED_SLOW!: number;

	/**
	 * Number of *milliseconds* to show the loading animation at least for,
	 * to avoid the impression "flickering".
	 */
	@Prop( { default: 500 } )
	private readonly MINIMUM_TIME_OF_PROGRESS_ANIMATION!: number;

	@Watch( 'isInitializing', { immediate: true } )
	private onStatusChange( isInitializing: boolean, _oldStatus: boolean ): void {
		if ( isInitializing ) {
			this.showLoading();
		} else {
			this.tendTowardsReady();
		}
	}

	public ready = false;
	public loadingIsSlow = false;

	private trackSlowness?: ReturnType<typeof setTimeout>;
	private trackAnimation?: ReturnType<typeof setTimeout>;
	private animatedEnough = false;

	private showLoading(): void {
		this.ready = false;

		this.trackSlowness = setTimeout( () => {
			this.loadingIsSlow = true;
			this.trackAnimation = setTimeout( () => {
				this.animatedEnough = true;
				this.tendTowardsReady();
			}, this.MINIMUM_TIME_OF_PROGRESS_ANIMATION );
		}, this.TIME_UNTIL_CONSIDERED_SLOW );
	}

	private tendTowardsReady(): void {
		if ( this.isInitializing || this.loadingIsSlow && !this.animatedEnough ) {
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
