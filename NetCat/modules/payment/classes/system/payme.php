<?php

class nc_payment_system_payme extends nc_payment_system {

	/*
	const ERROR_MRCHLOGIN_IS_NOT_VALID = NETCAT_MODULE_PAYMENT_TINKOFF_ERROR_MRCHLOGIN_IS_NOT_VALID;
	const ERROR_INVID_IS_NOT_VALID = NETCAT_MODULE_PAYMENT_ERROR_INVID_IS_NOT_VALID;
	const ERROR_INVDESC_ID_IS_LONG = NETCAT_MODULE_PAYMENT_ERROR_INVDESC_ID_IS_LONG;
	const ERROR_PRIVATE_SECURITY_IS_NOT_VALID = NETCAT_MODULE_PAYMENT_TINKOFF_ERROR_TOKEN_IS_NOT_VALID;
	*/
	private $errorInfo ="";
	private $errorCod =0;
	private $request_id=0;
	private $responceType=0;
	private $result =true;
	private $inputArray;
	private $orderLocal;
	private $invoceLocal;
	private $transactionDate;
	/*
	private $lastTransaction;
	private $lastTransactionDate;
	private $statement;
	private $paymentMethod;*/
	
	protected $automatic = TRUE;

	// принимаемые валюты
	protected $accepted_currencies = array('RUB', 'USD', 'EUR', 'RUR', 'UZS');

	// параметры
	protected $settings = array(

		'PaymeMerchantId' => null,
		'PaymeSecretKey' => null,
		'PaymeSecretKeyForTest' => null,

		'PaymeTestMode' => 0,
		'PaymeCheckoutUrl' => null,
		'PaymeCheckoutUrlForTest' => 'none',

		'PaymeReturnUrl' => 'ru',
		'PaymeReturnAfter' => 'ru',
		'PaymeProductInformation' => 'ru',
	);

	// передаваемые параметры
	protected $request_parameters = array();

	// получаемые параметры
	protected $callback_response = array(
		'InvId' => null,
		'OutSum' => null,
	);
/*
	static public $tinkoff_vats = [
		'none' => 'none',
		'vat0' => 'vat0',
		'vat10' => 'vat10',
		'vat18' => 'vat18',
	];
*/

	/* A */
	public function validate_payment_request_parameters() {
		
		file_put_contents(dirname(__FILE__) . "/payme.log", " ===>>> validate_payment_request_parameters "."\n", FILE_APPEND);
		return true;
	}

	/* B */
	public function execute_payment_request(nc_payment_invoice $invoice) {
		
		$url = $_SERVER['REQUEST_SCHEME']. '://' . $_SERVER['HTTP_HOST'] . '/netcat/modules/payment/callback.php?paySystem=nc_payment_system_payme&type=result';
		
		file_put_contents(dirname(__FILE__) . "/payme.log", " ===>>> execute_payment_request ".$url."\n", FILE_APPEND);

		$t_currency="";
		
			 if( $this->get_currency_code($invoice->get_currency()) == 'UZS') $t_currency = 860;
		else if( $this->get_currency_code($invoice->get_currency()) == 'USD') $t_currency = 840;
		else if( $this->get_currency_code($invoice->get_currency()) == 'RUB') $t_currency = 643;
		else if( $this->get_currency_code($invoice->get_currency()) == 'RUB') $t_currency = 643;
		else if( $this->get_currency_code($invoice->get_currency()) == 'EUR') $t_currency = 978;
		else							  								      $t_currency = 860;
		
		$amount = round($invoice->get_amount() * 100);
		
		$t_formURL="";
		
		if ($this->get_setting('PaymeTestMode')=='0')
			$t_formURL = $this->get_setting('PaymeCheckoutUrl');
		else
			$t_formURL = $this->get_setting('PaymeCheckoutUrlForTest');

		ob_end_clean(); 
		
		$form = "
			<html>
			  <body>
					<form action=".$t_formURL." method='post'>" .
					$this->make_inputs(array( 
						'merchant'			=> $this->get_setting('PaymeMerchantId'),
						'callback'			=> $this->get_setting('PaymeReturnUrl'),
						'callback_timeout'	=> $this->get_setting('PaymeReturnAfter'),
						'account[order_id]'	=> $invoice->get_id(),
						'amount' => $amount,
						'currency' => $t_currency,
						'description' =>''
					)) . "
				</form>
				<script>
				  document.forms[0].submit();
				</script>
			  </body>
			</html>";
		echo $form;
	}

	
	

	/* 1 */
	public function load_invoice_on_callback() {
		
		file_put_contents(dirname(__FILE__) . "/payme.log", "\n"."\n"."\n"."===>>> POINT 1 ===== load_invoice_on_callback ==="."\n", FILE_APPEND);
		
		$this->inputArray = json_decode(file_get_contents("php://input"), true); 
		
		if ( (!isset($this->inputArray)) || empty($this->inputArray) ) {

			$this->setErrorCod(-32700,"empty inputArray");

		} else {
		
			$parsingJsonError=false;

			switch (json_last_error()){

				case JSON_ERROR_NONE: break;
				default: $parsingJson=true; break;
			}

			if ($parsingJsonError) {

				$this->setErrorCod(-32700,"parsingJsonError");

			} else {

				// Request ID
				if (!empty($this->inputArray['id']) ) {

					$this->request_id = filter_var($this->inputArray['id'], FILTER_SANITIZE_NUMBER_INT);
				}
 
					 if ($_SERVER['REQUEST_METHOD']!='POST') $this->setErrorCod(-32300);
				else if(! isset($_SERVER['PHP_AUTH_USER']))  $this->setErrorCod(-32504,"логин пустой");
				else if(! isset($_SERVER['PHP_AUTH_PW']))	 $this->setErrorCod(-32504,"пароль пустой");
			} 
		}
		
		if ($this->result) {

			if ($this->get_setting('PaymeTestMode')=='1'){

				$merchantKey=html_entity_decode($this->get_setting('PaymeSecretKeyForTest'));

			} else if ($this->get_setting('PaymeTestMode')=='0'){

				$merchantKey=html_entity_decode($this->get_setting('PaymeSecretKey'));
			}

			if( $merchantKey != html_entity_decode($_SERVER['PHP_AUTH_PW']) ) {

				$this->setErrorCod(-32504,"неправильный  пароль");

			} else { 

				//$inv=$this->load_invoice(1);
				//return $inv; 
			}
		}

		file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 1 END===== load_invoice_on_callback ==="."\n", FILE_APPEND);
		//file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 1 ===== invoceID =". $inv->get_id() ." cur=".$inv->get_currency()."\n", FILE_APPEND); 	
	}
	
	/* 2 */
	public function validate_payment_callback_response(nc_payment_invoice $invoice = null) {
		
		file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 2 ===== validate_payment_callback_response ==="."\n", FILE_APPEND);
		
		if ($this->result) {
			
			//$netshop = nc_netshop::get_instance();
			//$this->orderLocal = $netshop->load_order($invoice['order_id']);
		}
		file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 2 END===== validate_payment_callback_response ==="."\n", FILE_APPEND);
	}
	
	/* 3  */
	public function on_response(nc_payment_invoice $invoice = null) {

		file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 3 ===== on_response ==="."\n", FILE_APPEND);

		if ($this->result) {

			if ( method_exists($this,"payme_".$this->inputArray['method'])) {

				file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 3 ===== RUN ".$this->inputArray['method'].' -> '.date("M,d,Y h:i:s A")."\n", FILE_APPEND);

				$methodName="payme_".$this->inputArray['method'];
				$this->$methodName();

			} else {

				$this->setErrorCod(-32601, $this->inputArray['method'] );
			}
		}

		file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 3 ===== RUN END ".$this->inputArray['method'].' -> '.date("M,d,Y h:i:s A")."\n", FILE_APPEND);
		$resp=json_encode($this->GenerateResponse(),JSON_UNESCAPED_UNICODE );
		header('Content-type: application/json charset=utf-8');
		echo $resp;
	}
	
	public function payme_CheckPerformTransaction() {

		file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 4 ===== payme_CheckPerformTransaction "."\n", FILE_APPEND);
		
		// Поиск транзакции по order_id
		$this->getTransactionByOrderId($this->inputArray['params']['account']['order_id']);
		
		$this->invoceLocal=$this->load_invoice($this->inputArray['params']['account']['order_id']);
		$netshop = nc_netshop::get_instance();
		$this->orderLocal = $netshop->load_order($this->invoceLocal['order_id']);
		
		file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 4-d ===== payme_CheckPerformTransaction order exists "."\n", FILE_APPEND);

		// Заказ не найден
		if (! $this->orderLocal ) {

			$this->setErrorCod(-31050,'order_id');

		// Заказ найден
		} else {
		
		file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 4 ===== payme_CheckPerformTransaction order exists "."\n", FILE_APPEND);
		/*file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 3 ===== payme_CheckPerformTransaction I id=".$invoice->get_id()."\n", FILE_APPEND);		
		file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 3 ===== payme_CheckPerformTransaction I status=".$invoice['status']."\n", FILE_APPEND);
		file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 3 ===== payme_CheckPerformTransaction S1=".$this->orderLocal['Created']."\n", FILE_APPEND);
		file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 3 ===== payme_CheckPerformTransaction S2=".$this->orderLocal['OrderCurrency']."\n", FILE_APPEND);
		file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 3 ===== payme_CheckPerformTransaction S3=".$this->orderLocal['User_ID']."\n", FILE_APPEND);
		file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 3 ===== payme_CheckPerformTransaction S status=".$this->orderLocal['Status']."\n", FILE_APPEND);
		*/
			// Транзакция статусс
			if ($this->invoceLocal['status']<=2 ) { //STATUS_NEW STATUS_SENT_TO_PAYMENT_SYSTEM

				// Проверка состояния заказа
				$status = (int)$this->orderLocal['Status'];
				if ($status!=0 ) { //NEW

					$this->setErrorCod(-31052, 'order_id');

				// Сверка суммы заказа
				} else  if ( abs(($this->orderLocal->get_totals()*100) - (int)$this->inputArray['params']['amount'])>=0.01) {

					$this->setErrorCod(-31001, 'order_id ='.gettype($this->orderLocal->get_totals()).'-'. ($this->orderLocal->get_totals()*100)  .'<>'. $this->inputArray['params']['amount']) ; 

				// Allow true
				} else {

					$this->responceType=1;
				} 

			// Существует транзакция
			} else {

				$this->setErrorCod(-31051, 'order_id');
			}
		}
		file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 4 END===== payme_CheckPerformTransaction "."\n", FILE_APPEND);
	}
	
	
	public function payme_CreateTransaction() {

		file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 5 ===== payme_CreateTransaction "."\n", FILE_APPEND);
		
		$this->getTransactionByPaymeTrId($this->inputArray['params']['id']);

		// Существует транзакция
		if ($this->transactionDate) {
			
			$this->invoceLocal=$this->load_invoice($this->transactionDate['cms_order_id']);
			$netshop = nc_netshop::get_instance();
			$this->orderLocal = $netshop->load_order($this->invoceLocal['order_id']);
			
			file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 5 ===== payme_CreateTransaction  TR EXIST ct=".$this->transactionDate['create_time']."\n", FILE_APPEND);

			$paycom_time_integer=$this->datetime2timestamp($this->transactionDate['create_time']);
			$paycom_time_integer=$paycom_time_integer+43200000;

			// Проверка состояния заказа
			$status = (int)$this->orderLocal['Status'];
			if ($status>1 ) { //NEW 

				$this->setErrorCod(-31052, 'order_id');
 
			// Проверка состояния транзакции
			} else if ($this->transactionDate['state'] !=1){ //Transaction status W

				$this->setErrorCod(-31008, 'order_id');

			// Проверка времени создания транзакции
			} else if ($paycom_time_integer <= $this->timestamp2milliseconds(time())){

				// Отменит reason = 4
				$sql ="UPDATE `payme_transactions` set cancel_time=now(), reason=4, state=-1  where `transaction_id`=".$this->transactionDate['transaction_id'] ;
				$nc_core = nc_Core::get_object();
				$nc_core->db->query($sql); 
				
				$this->orderLocal->set('Status', 2);
				$this->orderLocal->save();
				
				$this->invoceLocal->set('status', nc_payment_invoice::STATUS_CALLBACK_ERROR);
                $this->invoceLocal->save();

				$this->responceType=2;
				$this->getTransactionByOrderId($this->inputArray['params']['account']['order_id']);
 
			// Всё OK
			} else {

				$this->responceType=2;		
			}

		// Транзакция нет
		} else {
			
			file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 5 ===== payme_CreateTransaction  TR not EXIST "."\n", FILE_APPEND);
			
			$this->getTransactionByOrderId($this->inputArray['params']['account']['order_id']);
 
			$this->invoceLocal=$this->load_invoice($this->inputArray['params']['account']['order_id']);
			$netshop = nc_netshop::get_instance();
			$this->orderLocal = $netshop->load_order($this->invoceLocal['order_id']);
		
			// Заказ не найден
			if (!  $this->orderLocal ) {

				$this->setErrorCod(-31050,'order_id');

			// Заказ найден
			} else {

				// Транзакция статусс
				if ($this->invoceLocal['status']<=2 ) { //STATUS_NEW STATUS_SENT_TO_PAYMENT_SYSTEM
 
				// Проверка состояния заказа
				$status = (int)$this->orderLocal['Status'];
				if ($status!=0 ) { //NEW

					$this->setErrorCod(-31052, 'order_id');

				// Сверка суммы заказа 	
				} else  if ( abs(($this->orderLocal->get_totals()*100) - (int)$this->inputArray['params']['amount'])>=0.01) {

					$this->setErrorCod(-31001, 'order_id');

				// Запись транзакцию state=1
				} else {

					file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 3 ===== payme_CreateTransaction TO SAVE"."\n", FILE_APPEND);
					$this->SaveOrder(
										$this->orderLocal->get_totals()*100,
										$this->invoceLocal->get_id(), 
										$this->inputArray['params']['time'],
										$this->timestamp2datetime($this->inputArray['params']['time'] ),
										$this->inputArray['params']['id'] 
									);
									
					$this->orderLocal->set('Status', 1);
					$this->orderLocal->save();
				
					$this->invoceLocal->set('status', nc_payment_invoice::STATUS_WAITING);
					$this->invoceLocal->save();				

					$this->responceType=2; 
					$this->getTransactionByOrderId($this->inputArray['params']['account']['order_id']);
				}
				// Существует транзакция
				} else {

				$this->setErrorCod(-31051, 'order_id');
				}
			} //
		}
	}
	
	public function payme_CheckTransaction() {
 
		file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 6 ===== payme_CheckTransaction "."\n", FILE_APPEND);
		
		// Поиск транзакции по id
		$this->getTransactionByPaymeTrId($this->inputArray['params']['id']);

		// Существует транзакция
		if ($this->transactionDate) {
			
			file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 6 ===== payme_CheckTransaction TR EXIST"."\n", FILE_APPEND);

			$this->responceType=2;

		// Транзакция нет
		} else {

			$this->setErrorCod(-31003);
		}
		
		file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 6 end ===== payme_CheckTransaction "."\n", FILE_APPEND);
	}
	
	public function payme_PerformTransaction() {
		
		file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 7 ===== payme_PerformTransaction "."\n", FILE_APPEND);

		// Поиск транзакции по id
		$this->getTransactionByPaymeTrId($this->inputArray['params']['id']);

		// Существует транзакция
		if ($this->transactionDate) {

			// Поиск заказа по order_id
			$this->invoceLocal=$this->load_invoice($this->transactionDate['cms_order_id']);
			$netshop = nc_netshop::get_instance();
			$this->orderLocal = $netshop->load_order($this->invoceLocal['order_id']);
  
			// Проверка состояние транзакцие
			if ($this->transactionDate['state']==1){ //Transaction status W

				$paycom_time_integer=$this->datetime2timestamp($this->transactionDate['create_time']); 
				$paycom_time_integer=$paycom_time_integer+43200000;

				// Проверка времени создания транзакции
				if ($paycom_time_integer <= $this->timestamp2milliseconds(time())){

					// Отменит reason = 4
					$sql ="UPDATE `payme_transactions` set cancel_time=now(), reason=4, state=-1  where `transaction_id`=".$this->transactionDate['transaction_id'] ;
					$nc_core = nc_Core::get_object();
					$nc_core->db->query($sql); 
					
					$this->orderLocal->set('Status', 2);
					$this->orderLocal->save();
					
					$this->invoceLocal->set('status', nc_payment_invoice::STATUS_CALLBACK_ERROR);
					$this->invoceLocal->save(); 

				// Всё Ok
				} else {

					// Оплата
					$sql ="UPDATE `payme_transactions` set perform_time=now(), state=2  where `transaction_id`=".$this->transactionDate['transaction_id'] ;
					$nc_core = nc_Core::get_object();
					$nc_core->db->query($sql); 
					
					$this->orderLocal->set('Status', 3);
					$this->orderLocal->save();
					
					$this->invoceLocal->set('status', nc_payment_invoice::STATUS_SUCCESS);
					$this->invoceLocal->save();

					$this->on_payment_success($this->invoceLocal);
				}

				$this->responceType=2;
				$this->getTransactionByPaymeTrId($this->inputArray['params']['id']);

			// Cостояние не 1
			} else {

				// Проверка состояние транзакцие
				if ($this->transactionDate['state']==2){ //Transaction status

					$this->responceType=2;

				// Cостояние не 2
				} else {

					$this->setErrorCod(-31008);
				}
			}
		// Транзакция нет
		} else {

			$this->setErrorCod(-31003);
		}
	}
	
	public function payme_CancelTransaction() {

		// Поиск транзакции по id
		$this->getTransactionByPaymeTrId($this->inputArray['params']['id']);

		// Существует транзакция
		if ($this->transactionDate) {

			// Поиск заказа по order_id
			$this->invoceLocal=$this->load_invoice($this->transactionDate['cms_order_id']);
			$netshop = nc_netshop::get_instance();
			$this->orderLocal = $netshop->load_order($this->invoceLocal['order_id']);

			$reasonCencel=filter_var($this->inputArray['params']['reason'], FILTER_SANITIZE_NUMBER_INT);

			// Проверка состояние транзакцие
			if ($this->transactionDate['state']==1){ //Transaction status W

				// Отменит state = -1
				$sql ="UPDATE `payme_transactions` set cancel_time=now(), reason=".$reasonCencel.", state=-1  where `transaction_id`=".$this->transactionDate['transaction_id'] ;
				$nc_core = nc_Core::get_object();
				$nc_core->db->query($sql); 
				
				$this->orderLocal->set('Status', 2);
				$this->orderLocal->save();
				
				$this->invoceLocal->set('status', nc_payment_invoice::STATUS_CALLBACK_ERROR);
                $this->invoceLocal->save(); 

			// Cостояние 2
			} else if ($this->transactionDate['state']==2){ //Transaction status

				// Отменит state = -2
				$sql ="UPDATE `payme_transactions` set cancel_time=now(), reason=".$reasonCencel.", state=-2  where `transaction_id`=".$this->transactionDate['transaction_id'] ;
				$nc_core = nc_Core::get_object();
				$nc_core->db->query($sql); 
				
				$this->orderLocal->set('Status', 2);
				$this->orderLocal->save();
				
				$this->invoceLocal->set('status', nc_payment_invoice::STATUS_CALLBACK_ERROR);
                $this->invoceLocal->save();

			// Cостояние
			} else {

				// Ничего не надо делать
			}

			$this->responceType=2;
			$this->getTransactionByPaymeTrId($this->inputArray['params']['id']);

		// Транзакция нет
		} else {

			$this->setErrorCod(-31003);
		}
	}
	
	public function payme_ChangePassword() {
		
		$sql ="UPDATE `Payment_SystemSetting` set Param_Value='".$this->inputArray['params']['password']."' where `Param_Name`='PaymeSecretKey'" ;
		$nc_core = nc_Core::get_object();
		$nc_core->db->query($sql);

		$this->responceType=3;
	}
	
		public function payme_GetStatement() {
		
		$sql = "SELECT * FROM payme_transactions d WHERE CAST(d.paycom_time AS UNSIGNED)>=".$this->inputArray['params']['from']." and  CAST(d.paycom_time AS UNSIGNED)<=".$this->inputArray['params']['to'];
		$nc_core = nc_Core::get_object();
		$arr = $nc_core->db->get_results($sql, ARRAY_A);
		
		$responseArray = array();
		$transactions  = array();
		
		if (!empty($arr)) {
			
			foreach ($arr as $row) {
				
				array_push($transactions,array(

				"id"		   => $row["paycom_transaction_id"],
				"time"		   => $row['paycom_time']  ,
				"amount"	   => $row["amount"],
				"account"	   => array("cms_order_id" => $row["cms_order_id"]),
				"create_time"  => (is_null($row['create_time']) ? null: $this->datetime2timestamp( $row['create_time']) ) ,
				"perform_time" => (is_null($row['perform_time'])? null: $this->datetime2timestamp( $row['perform_time'])) ,
				"cancel_time"  => (is_null($row['cancel_time']) ? null: $this->datetime2timestamp( $row['cancel_time']) ) ,
				"transaction"  => $row["cms_order_id"],
				"state"		   => (int) $row['state'],
				"reason"	   => (is_null($row['reason'])?null:(int) $row['reason']) ,
				"receivers"	=> null
			)) ;
			
			}
		}  

		$responseArray['result'] = array( "transactions"=> $transactions );

		$this->responceType=4;
		$this->statement=$responseArray;
	}
	
	
	// Поиск транзакции по order_id
	public function getTransactionByOrderId($order_id) {
	
		file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> getTransactionByOrderId  "."\n", FILE_APPEND);
		
		$sql ="SELECT * FROM `payme_transactions` where `cms_order_id`='".$order_id."' order by transaction_id";
		$nc_core = nc_Core::get_object();
		$arr = $nc_core->db->get_results($sql, ARRAY_A);
		
		if (!empty($arr)) {
			
			foreach ($arr as $row) {
				
				$this->transactionDate = $row;
			}
		} 
		
		file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> end getTransactionByOrderId  "."\n", FILE_APPEND);
	}
	
	public function getTransactionByPaymeTrId($t_id) {
		
		file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> getTransactionByPaymeTrId  "."\n", FILE_APPEND);
		
		$sql ="SELECT * FROM `payme_transactions` where `paycom_transaction_id`='".$t_id."' order by transaction_id";	
		$nc_core = nc_Core::get_object();
		$arr = $nc_core->db->get_results($sql, ARRAY_A);
		
		if (!empty($arr)) {
			
			foreach ($arr as $row) {
				
				$this->transactionDate = $row;
			}
		}

		file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> end getTransactionByPaymeTrId  "."\n", FILE_APPEND);		
	}
	
	    // FIX state=1 and
	public function SaveOrder($amount,$cmsOrderId,$paycomTime,$paycomTimeDatetime,$paycomTransactionId ) {

	file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 3 ===== payme_CreateTransaction SAVE begin"."\n", FILE_APPEND);
	
		$oId=(is_null( $cmsOrderId )  ?  0:$cmsOrderId);
		
		$nc_core = nc_Core::get_object();
		$sql ="SELECT count(transaction_id) FROM  payme_transactions WHERE state=1 and cms_order_id = '".$oId."' and amount = ".$amount; 
		
		$transactionCnt=$nc_core->db->get_var($sql);
			
		if (! $transactionCnt) {
			
			file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 3 ===== payme_CreateTransaction insert begin"."\n", FILE_APPEND);

			$sql ="INSERT INTO `payme_transactions` (`create_time`, `amount`, `state` ,  `cms_order_id` , `paycom_time` , `paycom_time_datetime` , `paycom_transaction_id`)
			VALUES ( NOW(),".$amount.",1,'".$oId."','".$paycomTime."','".$paycomTimeDatetime."','".$paycomTransactionId."')";
			
			file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 3 ===== payme_CreateTransaction sql= ".$sql."\n", FILE_APPEND);
			
			$inserted = $nc_core->db->query($sql ); 
		}
		
		file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT 3 ===== payme_CreateTransaction save end "."\n", FILE_APPEND);
	}
	
	public function GenerateResponse() {

		file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT LAST STEP begin ===== GenerateResponse error=".$this->errorCod." rType=".$this->responceType."\n", FILE_APPEND);
		
		if ($this->errorCod==0) {
 
			if ($this->responceType==1) {

				$responseArray = array('result'=>array( 'allow' => true )); 

			} else if ($this->responceType==2) {

				$responseArray = array(); 
				$responseArray['id']	 = $this->request_id;
				$responseArray['result'] = array(

					"create_time"	=> $this->datetime2timestamp($this->transactionDate['create_time']) ,
					"perform_time"  => $this->datetime2timestamp($this->transactionDate['perform_time']),
					"cancel_time"   => $this->datetime2timestamp($this->transactionDate['cancel_time']) ,
					"transaction"	=> $this->transactionDate['cms_order_id'], //FIX $this->order_id,
					"state"			=>     (int)$this->transactionDate['state'],
					"reason"		=> ( $this->transactionDate['reason'] ? (int)$this->transactionDate['reason'] : null)
				);

			} else if ($this->responceType==3) {

				$responseArray = array('result'=>array( 'success' => true ));

			} else if ($this->responceType==4) {

				$responseArray=$this->statement;
			}

		} else {

			$responseArray['id']	= $this->request_id;
			$responseArray['error'] = array (

				'code'  =>(int)$this->errorCod,
				"data" 	=>$this->errorInfo,
				'message'=> array(

					"ru"=>$this->getGenerateErrorText($this->errorCod,"ru"),
					"uz"=>$this->getGenerateErrorText($this->errorCod,"uz"),
					"en"=>$this->getGenerateErrorText($this->errorCod,"en"),

				)
			);
		}
		
		file_put_contents(dirname(__FILE__) . "/payme.log", "===>>> POINT LAST STEP end ===== GenerateResponse "."\n", FILE_APPEND);

		return $responseArray;
	}

	public function getGenerateErrorText($codeOfError,$codOfLang){

		$listOfError=array ('-31001' => array(
										  "ru"=>'Неверная сумма.',
										  "uz"=>'Неверная сумма.',
										  "en"=>'Неверная сумма.'
										),
							'-31003' => array(
										  "ru"=>'Транзакция не найдена.',
										  "uz"=>'Транзакция не найдена.',
										  "en"=>'Транзакция не найдена.'
										),
							'-31008' => array(
										  "ru"=>'Невозможно выполнить операцию.',
										  "uz"=>'Невозможно выполнить операцию.',
										  "en"=>'Невозможно выполнить операцию.'
										),
							'-31050' => array(
										  "ru"=>'Заказ не найден.',
										  "uz"=>'Заказ не найден.',
										  "en"=>'Заказ не найден.'
										),
							'-31051' => array(
										  "ru"=>'Существует транзакция.',
										  "uz"=>'Существует транзакция.',
										  "en"=>'Существует транзакция.'
										),
							'-31052' => array(
											"ru"=>'Заказ уже оплачен.',
											"uz"=>'Заказ уже оплачен.',
											"en"=>'Заказ уже оплачен.'
										),
										
							'-32300' => array(
										  "ru"=>'Ошибка возникает если метод запроса не POST.',
										  "uz"=>'Ошибка возникает если метод запроса не POST.',
										  "en"=>'Ошибка возникает если метод запроса не POST.'
										),
							'-32600' => array(
										  "ru"=>'Отсутствуют обязательные поля в RPC-запросе или тип полей не соответствует спецификации',
										  "uz"=>'Отсутствуют обязательные поля в RPC-запросе или тип полей не соответствует спецификации',
										  "en"=>'Отсутствуют обязательные поля в RPC-запросе или тип полей не соответствует спецификации'
										),
							'-32700' => array(
										  "ru"=>'Ошибка парсинга JSON.',
										  "uz"=>'Ошибка парсинга JSON.',
										  "en"=>'Ошибка парсинга JSON.'
										),
							'-32600' => array(
										  "ru"=>'Отсутствуют обязательные поля в RPC-запросе или тип полей не соответствует спецификации.',
										  "uz"=>'Отсутствуют обязательные поля в RPC-запросе или тип полей не соответствует спецификации.',
										  "en"=>'Отсутствуют обязательные поля в RPC-запросе или тип полей не соответствует спецификации.'
										),
							'-32601' => array(
										  "ru"=>'Запрашиваемый метод не найден. В RPC-запросе имя запрашиваемого метода содержится в поле data.',
										  "uz"=>'Запрашиваемый метод не найден. В RPC-запросе имя запрашиваемого метода содержится в поле data.',
										  "en"=>'Запрашиваемый метод не найден. В RPC-запросе имя запрашиваемого метода содержится в поле data.'
										),
							'-32504' => array(
										  "ru"=>'Недостаточно привилегий для выполнения метода.',
										  "uz"=>'Недостаточно привилегий для выполнения метода.',
										  "en"=>'Недостаточно привилегий для выполнения метода.'
										),
							'-32400' => array(
										  "ru"=>'Системная (внутренняя ошибка). Ошибку следует использовать в случае системных сбоев: отказа базы данных, отказа файловой системы, неопределенного поведения и т.д.',
										  "uz"=>'Системная (внутренняя ошибка). Ошибку следует использовать в случае системных сбоев: отказа базы данных, отказа файловой системы, неопределенного поведения и т.д.',
										  "en"=>'Системная (внутренняя ошибка). Ошибку следует использовать в случае системных сбоев: отказа базы данных, отказа файловой системы, неопределенного поведения и т.д.'
										)
							);

		return $listOfError[$codeOfError][$codOfLang];
	}
	
	public function setErrorCod($cod_,$info=null) {

		$this->errorCod=$cod_;

		if ($info!=null) $this->errorInfo=$info;

		if ($cod_!=0) {

			$this->result=false;
		}
	}
	
	public function timestamp2datetime($timestamp){

		if (strlen((string)$timestamp) == 13) {
			$timestamp = $this->timestamp2seconds($timestamp);
		}

		return date('Y-m-d H:i:s', $timestamp);
	}

	public function timestamp2seconds($timestamp) {

		if (strlen((string)$timestamp) == 10) {
			return $timestamp;
		}

		return floor(1 * $timestamp / 1000);
	}

	public function timestamp2milliseconds($timestamp) {

		if (strlen((string)$timestamp) == 13) {
			return $timestamp;
		}

		return $timestamp * 1000;
	}

	public function datetime2timestamp($datetime) {

		if ($datetime) {

			return strtotime($datetime)*1000;
		} else return 0;

		return $datetime;
	}
}