<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created 2012-04-20 by Patrick Spence
 *
 */
/** 
 * assumes latest Stripe api library is installed in APPPATH.'third_party' 
 */
require_once(APPPATH.'third_party/stripe.php');

class Stripe_cust extends CI_Model {
	
	protected $private_key; // private key for accessing stripe account
	
	public $fields;
	
	/**
	 * constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->config->load('charge');
		$this->private_key = $this->config->item('stripe_secret_key');
		$this->set_api_key();
	} // __construct
	
	/**
	 * sets the private API key to enable transactions 
	 */
	function set_api_key() {
		Stripe::setApiKey($this->private_key);
	}
	
	public function init($config = array()) {
		if (isset($config['private_key'])) {
			$this->private_key = $config['private_key'];
		}
		$this->set_api_key();
		
	} // init
	
	/**
	 * retrieve a single customer record from Stripe
	 *
	 * @param string $customer_id Stripe customer id token
	 * @return object or array
	 */
	public function get($customer_id) {
		try {
			$ch = Stripe_Customer::retrieve($customer_id);
			$data['error'] = FALSE;
			$data['created'] = $ch->created;
			$data['email'] = $ch->email;
			$data['delinquent'] = $ch->delinquent;
			$data['discount'] = $ch->discount;
			$data['object'] = $ch->object; // type of this object
			$data['schedule'] = $ch->schedule;
			$data['livemode'] = $ch->livemode;
			$data['description'] = $ch->description;
			$data['name'] = $data['description']; // using description field to hold customer name
			$data['account_balance'] = $ch->account_balance;
			$data['subscription'] = $ch->subscription; // subscription info (object?)
			$data['id'] = $ch->id; // customer object ID code
			$data['active_card'] = $ch->active_card; // customer card object
			$data['data_object'] = $ch;
			return $data;
		} catch (Exception $e) {
			$data['error'] = TRUE;
			$data['message'] = $e->getMessage();
			return $data;
		}
	}

	/**
	 * updates given data fields on a customer
	 * @param type $customer_id Stripe customer token
	 * @param type $data  array of optional items: email, description (name), card.  Card can be card token from Stripe or array of these fields:
	 *					number, exp_month, exp_year, cvc, name, address_line1, address_line2, address_zip, address_state, address_country
	 *					number, exp_month, and exp_year are the only required fields in this array
	 */
	function update($customer_id, $data) {
		$customer = $this->get($customer_id);
		if (! $customer['error']) {
			if (isset($data['email'])) {
				$customer['data_object']->email = $data['email'];
			}
			if (isset($data['description'])) {
				$customer['data_object']->description = $data['description'];
			}
			if (isset($data['name'])) {
				$customer['data_object']->description = $data['description'];
			}
			if (isset($data['card'])) {
				$customer['data_object']->card = $data['card'];
			}
			try {
				$customer['data_object']->save();
			} catch (Exception $e) {
				$data['error'] = TRUE;
				$data['message'] = $e->getMessage();
				return $data;
			}
		} else {
			return $customer;
		}

		
	}// update
	
	/**
	 * deletes a customer
	 * @param type $customer_id stripe id token of customer
	 */
	function delete($customer_id) {
		if (trim($customer_id) <> '') {
			$customer = $this->get($customer_id);
			if ($customer['error']) {
				return $customer;
			} else {
				try {
					$ch = $customer['data_object']->delete();
					$data['error'] = FALSE;
					return $data;
				} catch (Exception $e) {
					$data['error'] = TRUE;
					$data['message'] = $e->getMessage();
					return $data;
				}
			}
		}
	} // delete
	
	/**
	 * Get last (n) charges up to 100 (default).  Cannot return more than 100 at a time.   Data
	 * returned is sorted with most recent first.
	 *
	 * @return array - function returns associative array from stripe, we cant get objects
	 */
	public function get_all($num_customers = 100, $offset = 0) {
		try {
			$ch = Stripe_Customer::all(array(
				'count' => $num_customers,
				'offset' => $offset
				));
			$data['error'] = FALSE;
			$data['data'] = $ch->data;
		} catch (Exception $e) {
			$data['error'] = TRUE;
			$data['data'] = array();
		}
		return $data;
	}

	/**
	 * Return a count of every transaction, up to the last 100!
	 *
	 * @return integer
	 */
	public function count_all() {
		$charges = $this->get_all();
		return count($charges);
	}


	/**
	 * creates a new customer
	 * @param string $name used as description of customer
	 * @param string $email email address of customer
	 * @param string $card can be card token from Stripe OR array of these fields:
	 *					number, exp_month, exp_year, cvc, name, address_line1, address_line2, address_zip, address_state, address_country
	 *					number, exp_month, and exp_year are the only required fields in this array
	 * @return array array with error flag and id of customer object if successful
	 */
	public function insert($name, $email, $card = NULL) {
		try {
			$record['description'] = $name;
			if ($card <> NULL) {
				$record['card'] = $card;
			}
			$record['email'] = $email;
			$customer = Stripe_Customer::create($record);
			$data['id'] = $customer->id;
			$data['error'] = FALSE;
		} catch(Exception $e) {
			$data['error'] = TRUE;
			$data['message'] = $e->getMessage();
			$data['code'] = $e->getCode();
		}
		return $data;	
	}
	
	/**
	 * wrapper for function insert
	 * @param type $token
	 * @param type $description
	 * @param type $amount
	 * @return type 
	 */
	function create($name, $email, $card) {
		return $this->insert($name, $email, $card);
	}

	/**
	 * Similar to insert(), just passing an array to insert
	 * multiple rows at once. Returns an array of insert IDs.
	 *
	 * @param array $data Array of arrays to insert
	 * @return array
	 */
	public function insert_many($data) {
		$ids = array();

		foreach ($data as $row) {
			$ids[] = $this->insert($row, $skip_validation);
		}

		return $ids;
	}

	/**
	 * returns chunk of all members dictated by $limit and $offset
	 * @param int $limit
	 * @param int $offset
	 * @return type array/object
	 */
	public function get_limit($limit, $offset = 0) {
		return $this->get_all($limit, $offset);
	}// get_limit


	
}// class stripe