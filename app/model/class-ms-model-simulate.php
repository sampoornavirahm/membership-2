<?php
/**
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
 * Membership Simulation model.
 *
 * Persisted by parent class MS_Model_Transient.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Simulate extends MS_Model_Transient {

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @staticvar MS_Model_Settings
	 */
	public static $instance;

	/**
	 * The membership ID to simulate.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected $membership_id = null;

	/**
	 * Flag, if the simulation should display a datepicker or not.
	 *
	 * @since 1.1.0
	 * @var bool
	 */
	protected $datepicker = false;

	/**
	 * The date to simulate.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $date;

	/**
	 * If current user is admin.
	 *
	 * @since 1.1.0
	 *
	 * @var bool
	 */
	protected $_is_admin;

	/**
	 * Holds a reference to the simulated subscription.
	 *
	 * @var MS_Model_Relationship.
	 */
	protected $subscription = null;

	/**
	 * Called after loading model data.
	 *
	 * @since  1.1.0
	 */
	public function after_load() {
		if ( $this->is_simulating() ) {
			if ( empty( $this->date ) ) {
				$this->date = MS_Helper_Period::current_date();
			}

			add_filter(
				'pre_site_option_site_admins',
				array( $this, 'admin_filter' )
			);

			add_filter(
				'ms_model_relationship_get_subscriptions',
				array( $this, 'add_simulation_membership' ),
				10, 2
			);
		}
	}

	/**
	 * Makes the current user a non-admin user during simulation
	 *
	 * @since  1.1
	 * @param  string $result Set to False to use default WordPress value.
	 * @return string Empty value means "no Administrator on this installation".
	 */
	public function admin_filter( $result ) {
		return '';
	}

	/**
	 * Add the simulated relationship to the current users memberships
	 *
	 * @since 1.1.0
	 */
	public function add_simulation_membership( $ms_relationships ) {
		if ( ! isset( $ms_relationships[ $this->membership_id ] ) ) {
			$this->start_simulation();

			$subscription = MS_Model_Relationship::create_ms_relationship(
				$this->membership_id,
				0,
				'simulation'
			);

			$membership = $subscription->get_membership();
			if ( MS_Model_Membership::PAYMENT_TYPE_RECURRING == $membership->payment_type
				|| MS_Model_Membership::PAYMENT_TYPE_PERMANENT == $membership->payment_type ) {
				$subscription->expire_date = '2999-12-31';
			}

			$key = 'ms_model_relationship--1';
			MS_Factory::set_singleton( $key, $subscription );

			$this->subscription = $subscription;
			$ms_relationships[ $this->membership_id ] = $subscription;
		}

		return $ms_relationships;
	}

	/**
	 * Checks if the current user is allowed to start simulation (only admin
	 * users are allowed). Reset simulation in case the user is not allowed.
	 *
	 * @since  1.1.0
	 */
	protected function check_permissions() {
		if ( null === $this->_is_admin ) {
			$this->_is_admin = MS_Model_Member::is_admin_user();
		}

		if ( ! $this->_is_admin ) {
			$this->reset_simulation();
		}
	}

	/**
	 * Check simulating status
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if currently simulating a membership.
	 */
	public function is_simulating() {
		$this->check_permissions();

		return ! empty( $this->membership_id );
	}

	/**
	 * Start simulation date.
	 *
	 * @since 1.0.0
	 */
	public function start_simulation() {
		$this->check_permissions();

		if ( $this->datepicker ) {
			$this->add_filter(
				'ms_helper_period_current_date',
				'simulate_date_filter'
			);
		}

		// Display some infos on the simulation.
		$this->add_filter(
			'shutdown',
			'simulation_infos'
		);
	}

	/**
	 * Simulate date.
	 *
	 * Used Hooks filter/actions:
	 * - ms_helper_period_current_date
	 *
	 * @since 1.0.0
	 *
	 * @param string $current_date The date to filter.
	 * @return string The filtered date.
	 */
	public function simulate_date_filter( $current_date ) {
		if ( ! empty( $this->date ) ) {
			$current_date = $this->date;
		}

		return $current_date;
	}

	/**
	 * Reset simulation.
	 *
	 * @since 1.0.0
	 */
	public function reset_simulation() {
		$this->membership_id = null;
		$this->date = null;

		$this->remove_filter(
			'ms_helper_period_current_date',
			'simulate_date_filter'
		);

		$this->save();
	}

	/**
	 * Checks if the currently simulated membership needs a datepicker or not.
	 *
	 * @since 1.0.0
	 *
	 * @return string True if a datepicker is needed.
	 */
	public function needs_datepicker() {
		$membership = MS_Factory::load(
			'MS_Model_Membership',
			$this->membership_id
		);

		$m_type = $membership->type;
		$p_type = $membership->payment_type;
		$rep_end = $membership->pay_cycle_repetitions > 0;
		$date_specific = false;

		if ( MS_Model_Membership::TYPE_DRIPPED === $m_type ) { $date_specific = true; }
		elseif ( MS_Model_Membership::PAYMENT_TYPE_FINITE === $p_type ) { $date_specific = true; }
		elseif ( MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE === $p_type ) { $date_specific = true; }
		elseif ( MS_Model_Membership::PAYMENT_TYPE_RECURRING === $p_type && $rep_end ) { $date_specific = true; }

		$this->datepicker = $date_specific;

		return apply_filters(
			'ms_model_simulate_needs_datepicker',
			$this->datepicker,
			$this
		);
	}

	/**
	 * Display some infos on the page on the current simulation
	 *
	 * @since  1.1.0
	 */
	public function simulation_infos() {
		$data = array();
		$data['membership_id'] = $this->membership_id;
		$data['subscription'] = $this->subscription;
		$data['simulate_date'] = $this->date;
		$data['datepicker'] = $this->datepicker;

		$view = MS_Factory::create( 'MS_View_Adminbar' );
		$view->data = apply_filters( 'ms_view_admin_bar_data', $data );
		$html = $view->to_html();

		echo $html;
	}

	/**
	 * Returns property associated with the render.
	 *
	 * @since 1.0.0
	 *
	 * @param string $property The name of a property.
	 * @return mixed Returns mixed value of a property or NULL if a property doesn't exist.
	 */
	public function __get( $property ) {
		$value = null;

		if ( property_exists( $this, $property ) ) {
			switch ( $property ) {
				case 'date':
					if ( empty( $this->date ) ) {
						$this->date = MS_Helper_Period::current_date();
					}
					$value = $this->date;
					break;

				default:
					$value = $this->$property;
					break;
			}
		}

		return apply_filters(
			'ms_model_simulate__get',
			$value,
			$property,
			$this
		);
	}

	/**
	 * Validate specific property before set.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch ( $property ) {
				case 'membership_id':
					$this->membership_id = null;
					$id = absint( $value );
					if ( ! empty( $id ) ) {
						if ( MS_Model_Membership::is_valid_membership( $id ) ) {
							$this->membership_id = $id;
							$this->needs_datepicker();
						}
					}
					break;

				case 'date':
					if ( $date = $this->validate_date( $value ) ) {
						$this->date = $value;
					}
					break;

				default:
					$this->$property = $value;
					break;
			}
		}

		do_action(
			'ms_model_simulate__set_after',
			$property,
			$value,
			$this
		);
	}
}