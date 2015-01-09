<?php
/**
 * An Addon controller.
 *
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
 * MA 02110-1301 USA
 *
 */

/**
 * Add-On controller for: Taxamo
 *
 * @since 1.1.0
 *
 * @package Membership
 * @subpackage Controller
 */
class MS_Addon_Taxamo extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since 1.1.0
	 */
	const ID = 'addon_taxamo';

	/**
	 * Registers the Add-On
	 *
	 * @since  1.1.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $addons ) {
		$addons[ self::ID ] = (object) array(
			'name' => __( 'Taxamo', MS_TEXT_DOMAIN ),
			'description' => __( 'Addresses EU VAT regulations.', MS_TEXT_DOMAIN ),
			'icon' => 'wpmui-fa wpmui-fa-euro',
		);

		return $addons;
	}

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.1.0
	 */
	public function init() {

	}

	/**
	 * Activates the Add-on logic, only executed when add-on is active.
	 *
	 * @since  1.1.0
	 */
	public function activate() {
		$this->add_filter(
			'ms_controller_settings_get_tabs',
			'settings_tabs',
			10, 2
		);
		$this->add_filter(
			'ms_view_settings_edit_render_callback',
			'manage_render_callback',
			10, 3
		);
	}

	/**
	 * Add taxamo settings tab in settings page.
	 *
	 * @since 1.0.0
	 *
	 * @param array $tabs The current tabs.
	 * @param int $membership_id The membership id to edit
	 * @return array The filtered tabs.
	 */
	public function settings_tabs( $tabs ) {
		$tabs[ self::ID  ] = array(
			'title' => __( 'Taxamo', MS_TEXT_DOMAIN ),
			'url' => 'admin.php?page=' . MS_Controller_Plugin::MENU_SLUG . '-settings&tab=' . self::ID,
		);

		return $tabs;
	}

	/**
	 * Add taxamo settings-view callback.
	 *
	 * @since 1.0.0
	 *
	 * @param array $callback The current function callback.
	 * @param string $tab The current membership rule tab.
	 * @param array $data The data shared to the view.
	 * @return array The filtered callback.
	 */
	public function manage_render_callback( $callback, $tab, $data ) {
		if ( self::ID == $tab ) {
			$view = new MS_Addon_Taxamo_View_Settings();
			$view->data = $data;
			$callback = array( $view, 'render_tab' );
		}

		return $callback;
	}

}