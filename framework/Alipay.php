<?php

/* Alipay by wangdeguo <wangdeguo> */

class Alipay
{
    const GATEWAY = 'https://www.alipay.com/cooperate/gateway.do?';

    private $security_code = '';
    private $params = array();

    function __construct($partner, $security_code, $seller)
    {
        $this->params['partner'] = $partner;
        $this->security_code = $security_code;
        if (filter_var($seller, FILTER_VALIDATE_EMAIL) === false) {
            $this->params['seller_id'] = intval($seller);
        } else {
            $this->params['seller_email'] = $seller;
        }
    }

    public function getUrl($params)
    {
        $params = array_merge(self::getParams(), $params);
        $this->params = array_merge($params, $this->params);

        foreach ($this->params as $key => $val) {
            if (empty($val)) {
                unset($this->params[$key]);
            }
        }

        ksort($this->params);
        $sign = array();
        $url = array();
        foreach ($this->params as $key => $val) {
            $sign[] = "$key=$val";
            $url[]  = $key . '=' . urlencode($val);
        }
        $sign = $this->Sign($sign);

        $url = self::GATEWAY . implode('&', $url) . '&sign=' . $sign . '&sign_type=MD5';

        return $url;
    }

    public function NotifyVerify()
    {
        if (empty($_POST["notify_id"])) {
            return false;
        }

        $url = self::GATEWAY.'service=notify_verify&partner='.$this->params['partner'].'&notify_id='.$_POST["notify_id"];
        $result = file_get_contents($url);
        if (stripos($result, 'true') !== false) {
            return true;
        }

        return false;
    }

    private function Sign($sign)
    {
        return md5(implode('&', $sign) . $this->security_code);
    }

    public static function getParams()
    {
        return array(
            'service'           => '',
            'partner'           => '',
            'notify_url'        => '',
            'return_url'        => '',
            '_input_charset'    => 'utf-8',
            'subject'           => '',
            'body'              => '',
            'out_trade_no'      => '',
            'price'             => '',
            'total_fee'         => '',
            'discount'          => '',
            'show_url'          => '',
            'quantity'          => '',
            'payment_type'      => '',
            'logistics_type'    => '',
            'logistics_fee'     => '',
            'logistics_payment' => '',
            'receive_name'      => '',
            'receive_address'   => '',
            'receive_zip'       => '',
            'receive_phone'     => '',
            'receive_mobile'    => '',
            'seller_email'      => '',
            'seller_id'         => '',
            'buyer_email'       => '',
            'buyer_id'          => '',
            't_b_pay'           => '',
            't_s_send_1'        => '',
            't_s_send_2'        => ''
        );
    }
}
?>