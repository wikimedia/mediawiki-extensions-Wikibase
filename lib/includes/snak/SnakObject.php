<?php

namespace Wikibase;

/**
 * Base class for snaks.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Snaks
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class SnakObject implements Snak {

	/**
	 * @since 0.1
	 *
	 * @var SubscribtionHandler|false
	 */
	private $subscribtionHandler = false;

	/**
	 * @since 0.1
	 *
	 * @return SubscribtionHandler
	 */
	protected function getSubscriptionHandler() {
		if ( $this->subscribtionHandler === false ) {
			$this->subscribtionHandler = new SubscribtionHandler();
		}

		return $this->subscribtionHandler;
	}

	/**
	 * @since 0.1
	 *
	 * @see Subscribable::subscribe
	 *
	 * @param callable $function
	 */
	public function subscribe( $function ) {
		$this->getSubscriptionHandler()->subscribe( $function );
	}

	/**
	 * @since 0.1
	 *
	 * @see Subscribable::unsubscribe
	 *
	 * @param callable $function
	 */
	public function unsubscribe( $function ) {
		$this->getSubscriptionHandler()->unsubscribe( $function );
	}

	/**
	 * @see Snak::getHash
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHash() {
		return md5( serialize( $this ) );
	}

}