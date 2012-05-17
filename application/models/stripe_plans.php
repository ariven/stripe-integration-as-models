<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

	/**
	 * assumes latest Stripe api library is installed in APPPATH.'third_party'
	 */
	require_once(APPPATH . 'third_party/Stripe.php');

	class Stripe_plans extends CI_Model
	{

		protected $private_key; // private key for accessing stripe account

		public $message = ''; // used to hand messages back in case of error
		public $code; // error code, when saved on errors
		public $error = FALSE; // used to hand error condition back if you dont use array returns

		/**
		 * constructor
		 */
		public function __construct()
		{
			parent::__construct();
			$this->config->load('charge');
			$this->private_key = $this->config->item('stripe_secret_key');
			$this->set_api_key();
		}

		/**
		 * sets the private API key to enable transactions
		 */
		function set_api_key()
		{
			Stripe::setApiKey($this->private_key);
		}

		/**
		 * @param $plan_id           unique ID for this plan
		 * @param $plan_name         Name to display in web interface and on invoices
		 * @param $amount            amount in PENNIES.  0 for free plan
		 * @param $interval          'month' or 'year'
		 * @param bool $trial_period if integer, number of days for trial period
		 */
		function insert($plan_id, $plan_name, $amount, $interval, $trial_period = FALSE)
		{
			$plan_data = array(
				'amount'   => $amount,
				'interval' => $interval,
				'name'     => $plan_name,
				'currency' => 'usd',
				'id'       => $plan_id,
			);
			if ($trial_period)
			{
				$plan_data['trial_period_days'] = $trial_period;
			}
			try
			{
				$plan = Stripe_Plan::create($plan_data);
			} catch (Exception $e)
			{
				$this->error   = TRUE;
				$this->message = $e->getMessage();
				$this->code    = $e->getCode();
				return FALSE;
			}
			return $plan;
		}

		/**
		 * Gets a plan object.
		 *
		 * @param $plan_id the unique plan id
		 * @return mixed plan, or FALSE
		 */
		function get($plan_id)
		{
			try
			{
				$plan = Stripe_Plan::retrieve($plan_id);
			} catch (Exception $e)
			{
				$this->error   = TRUE;
				$this->message = $e->getMessage();
				$this->code    = $e->getCode();
				return FALSE;
			}
			return $plan;
		}

		/**
		 * Updates the name of the plan.  No other details are editable, per Stripe
		 *
		 * @param $plan_id the id for the plan to change
		 * @param $name new name for this plan, no other fields are editable
		 */
		function update ($plan_id, $name)
		{
			$plan_object = $this->get($plan_id);
			if (! $plan_object)
			{
				$this->error = TRUE;
				$this->message = 'Plan does not exist';
				return FALSE;
			}
			try
			{
				$plan_object->name = $name;
			} catch (Exception $e)
			{
				$this->error   = TRUE;
				$this->message = $e->getMessage();
				$this->code    = $e->getCode();
				return FALSE;
			}
			return $plan;
		}

		/**
		 * Deletes a plan.  this will NOT remove people from the plan, just prevent new signups.
		 *
		 * @param $plan_id the unique id of this plan
		 * @return mixed plan object or FALSE
		 */
		function delete($plan_id)
		{
			$plan_object = $this->get($plan_id);
			if (! $plan_object)
			{
				$this->error = TRUE;
				$this->message = 'Plan does not exist';
				return FALSE;
			}
			try
			{
				$plan_object->delete();
			} catch (Exception $e)
			{
				$this->error   = TRUE;
				$this->message = $e->getMessage();
				$this->code    = $e->getCode();
				return FALSE;
			}
			return $plan;
		}

		/**
		 * @param int $count number to return, max 100
		 * @param int $offset where to start
		 * @return array|bool array with data property that contains array of all plans up to count.
		 */
		function get_all($count = 10, $offset = 0)
		{
			try {
				$plans = Stripe_Plan::all();
			} catch (Exception $e)
			{
				$this->error   = TRUE;
				$this->message = $e->getMessage();
				$this->code    = $e->getCode();
				return FALSE;
			}
			return $plans;
		}
	}