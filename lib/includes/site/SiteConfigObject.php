<?php

/**
 * Object for holing configuration for a single Site.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.20
 *
 * @file
 * @ingroup Site
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteConfigObject implements SiteConfig {

	protected $forward;
	protected $extraConfig;

	/**
	 * Constructor.
	 *
	 * @since 1.20
	 *
	 * @param boolean $forward
	 * @param array $extraConfig
	 */
	public function __construct( $forward, array $extraConfig = array() ) {
		$this->forward = $forward;
		$this->extraConfig = $extraConfig;
	}

	/**
	 * @see SiteConfig::getForward()
	 *
	 * @since 1.20
	 *
	 * @return boolean
	 */
	public function getForward() {
		return $this->forward;
	}

	/**
	 * @see SiteConfig::getExtraInfo()
	 *
	 * @since 1.20
	 *
	 * @return array
	 */
	public function getExtraInfo() {
		return $this->extraConfig;
	}

}
