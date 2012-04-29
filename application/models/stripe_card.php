<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Created 2012-04-20 by Patrick Spence
 *
 */

/** 
 * assumes latest Stripe api library is installed in APPPATH.'third_party' 
 */
require_once(APPPATH.'third_party/stripe.php');

class Stripe_card extends CI_Model {
	
	protected $private_key; // private key for accessing stripe account
	
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
	 * converts card object to array
	 * @param type $card 
	 */
	function card_to_array($card) {
		$data = array(
				'address_country' => $card->address_country,
				'type' => $card->type,
				'address_zip_check' => $card->address_zip_check,
				'fingerprint' => $card->fingerprint,
				'address_state' => $card->address_state,
				'exp_month' => $card->exp_month,
				'address_line1_check' => $card->address_line1_check,
				'country' => $card->country,
				'last4' => $card->last4,
				'exp_year' => $card->exp_year,
				'address_zip' => $card->address_zip,
				'object' => $card->object,
				'address_line1' => $card->address_line1,
				'name' => $card->name,
				'address_line2' => $card->address_line2,
				'id' => $card->id,
				'cvc_check' => $card->cvc_check,
			);
		return $data;
	}
	
	/**
	 * Get a single record by creating a WHERE clause with
	 * a value for your primary key
	 *
	 * @param string $primary_value The value of your primary key
	 * @return object or array
	 */
	public function get($card_id) {
		try {
			$ch = Stripe_Token::retrieve($card_id);
			$data['created'] = date('Y-m-d H:i:s', $ch->created);
			$data['used'] = $ch->used;
			$data['amount'] = $ch->amount;
			$data['currency'] = $ch->currency;
			$data['object'] = $ch->object; // object type
			$data['livemode'] = $ch->livemode;
			$data['id'] = $ch->id;
			$data['card'] = $this->card_to_array($ch->card);

			$data['error'] = FALSE;
			$data['data_object'] = $ch;
		} catch (Exception $e) {
			$data['error'] = TRUE;
			$data['message'] = $e->getMessage();
		}
		return $data;
	}

	/**
	 * creates a card token
	 * @param type $card_info array of number, exp_month, exp_year, cvc, name, address_line1,
	 *							address_line2, address_zip, address_state, address_country
	 *							only number, exp_month, exp_year are required
	 * @return type 
	 */
	public function insert($card_info) {
		try {
			$card = Stripe_Token::create($card_info);
			
			$data = $this->card_to_array($ch->card);
			
			$data['data_object'] = $card;
			$data['error'] = FALSE;
		} catch(Exception $e) {
			$data['error'] = TRUE;
			$data['message'] = $e->getMessage();
			$data['code'] = $e->getCode();
		}
		return $data;	
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
	
}// class stripe