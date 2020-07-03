<?php

/**
 * Оплата через Invoice
 */

class Shop_Payment_System_HandlerXX extends Shop_Payment_System_Handler
{
    protected $api_key = "1526fec01b5d11f4df4f2160627ce351"; //API Key(можно получить в ЛК Invoice)
    protected $login = "demo"; //Ваш логин от личного кабинета Invoice
    protected $terminal_name = "HostCMS Terminal"; //Название терминала по умолчанию(Н-р: "Магазин на диване")

    function execute()
    {
        parent::execute();
        $this->printNotification();
        return $this;
    }

    public function checkPaymentBeforeContent()
    {
        if (isset($_GET['params']))
        {
            $params = Core_Array::getGet('params');
            $order_id = intval($params['account']);

            $oShop_Order = Core_Entity::factory('Shop_Order')->find($order_id);

            if (!is_null($oShop_Order->id))
            {
                Shop_Payment_System_Handler::factory($oShop_Order->Shop_Payment_System)
                    ->shopOrder($oShop_Order)
                    ->paymentProcessing();
            } else {
                die("Not found");
            }
        }

    }

    protected function _processOrder()
    {
        parent::_processOrder();

        $this->setXSLs();
        $this->send();

        return $this;
    }

    public function getNotification()
    {
        $sum = $this->_shopOrder->getAmount();
        $id = $this->_shopOrder->id;

        $form = "";

        try {
            $url = $this->createPayment($sum, $id);
            $form = '<form name="invoice_payment" action="' . $url . '" method="get">';
            $form .= '<input class="button" type="submit" value="Оплатить">';
            $form .= '</form>';
        } catch (Exception $e) {
            $form = '<form name="invoice_payment" action="/" method="get">';
            $form .= '<p>В данный момент платежи через Инвойс недоступны<br> Обратитесь к администратору</p>';
            $form .= '<input class="button" type="submit" value="Назад">';
            $form .= '</form>';
        }

        return $form;
    }

    public function createPayment($amount, $orderId) {
        $request = [
            "order" => [
                "currency" => "RUB",
                "amount" => $amount,
                "id" => $orderId,
                "description" => "Заказ №".$orderId
            ],
            "settings" => [
                "terminal_id" => $this->checkOrCreateTerminal(),
                "success_url" => ( ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'])
            ],
            "receipt" => [],
            "custom_parameters" => [],
            "mail" => "",
            "phone" => ""
        ];

        $response = json_decode($this->sendRequest(json_encode($request), "CreatePayment"));

        if($response == null) throw new Exception("Ошибка при создании платежа");
        if(isset($response->error)) throw new Exception("Ошибка при создании платежа(".$response->description.")");

        return $response->payment_url;
    }

    public function checkOrCreateTerminal() {
        $tid = $this->getTerminal();
        if($tid == null or empty($tid)) {
            $tid = $this->createTerminal();
        }
        return $tid;
    }

    public function createTerminal() {
        $request = [
            "name" => $this->terminal_name,
            "description" => "",
            "type" => "dynamical",
            "defaultPrice" => 0
        ];

        $response = json_decode($this->sendRequest(json_encode($request), "CreateTerminal"));

        if($response == null) throw new Exception("Ошибка при создании терминала");
        if(isset($response->error)) throw new Exception("Ошибка при создании терминала(".$response->description.")");

        $this->saveTerminal($response->id);

        return $response->id;
    }

    public function saveTerminal($id) {
        file_put_contents("invoice_tid", $id);
    }

    public function getTerminal() {
        if(!file_exists("invoice_tid")) return "";
        return file_get_contents("invoice_tid");
    }

    public function sendRequest($json, $method) {
        $request = "https://api.invoice.su/api/v2/" . $method;
        $auth = base64_encode($this->login . ":" . $this->api_key);

        $ch = curl_init($request);
        curl_setopt($ch, CURLOPT_URL, $request);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Host: pay.invoice.su",
            "content-type: application/json",
            "Authorization: Basic ".$auth,
            "User-Agent: curl/7.55.1",
            "Accept: */*"
        ]);

        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public function paymentProcessing()
    {
        $postData = file_get_contents('php://input');
        $notification = json_decode($postData, true);
        $this->callback($notification);
        return TRUE;
    }

    public function callback($notification) {
        $type = $notification["notification_type"];
        $id = $notification["order"]["id"];

        $order = Core_Entity::factory('Shop_Order')->find($id);
        if(is_null($order)) return "not found";


        $signature = $notification["signature"];

        if($signature != $this->getSignature($notification["id"], $notification["status"], $this->api_key)) {
            return "Wrong signature";
        }

        if($type == "pay") {

            if($notification["status"] == "successful") {
                $this->pay($order);
                return "payment successful";
            }
            if($notification["status"] == "error") {
                $this->error($order);
                return "payment failed";
            }
        }

        return "null";
    }

    public function pay($order) {
        $order->system_information = "Оплачено(Invoice).\n";
        $order->paid();
        $this->setXSLs();
        $this->send();

        ob_start();
        $this->changedOrder('changeStatusPaid');
        ob_get_clean();
    }

    public function error($order) {
        $order->system_information = "Ошибка при оплате(Invoice).\n";
        $this->save();
    }

    public function getSignature($id, $status, $key) {
        return md5($id.$status.$key);
    }

    public function getInvoice()
    {
        return $this->getNotification();
    }
}