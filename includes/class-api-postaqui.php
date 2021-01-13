<?php

class Postaqui
{

    protected $token;
    protected $source_zip_code;
    protected $target_zip_code;
    protected $weight;
    protected $length;
    protected $height;
    protected $width;
    protected $rates;
    protected $package_value;

    private $postaqui_url = "http://api.postaquilogistica.com.br:3100/";

    /**
     * Constructor
     * @param string $token
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Set store source zip code
     * @param mixed $source_zip_code
     * @return void
     */
    public function set_source_zip_code($source_zip_code)
    {
        $this->source_zip_code = $this->only_numbers($source_zip_code);
    }

    /**
     * Retrieve user target zip code
     * @param mixed $target_zip_code
     * @return void
     */
    public function set_target_zip_code($target_zip_code)
    {
        $this->target_zip_code = $this->only_numbers($target_zip_code);
    }

    /**
     * Set weight
     * @param mixed $weight
     * @return void
     */
    public function set_weight($weight)
    {
        $this->weight = $weight;
    }

    /**
     * Set shipping rates
     * @param mixed $rates
     * @return void
     */
    public function set_rates($rates)
    {
        $this->rates = $rates;
    }

    /**
     * Set package value
     * @param mixed $value
     * @return void
     */
    public function set_package_value($value)
    {
        $this->package_value = $value;
    }

    /**
     * Set height
     * @param mixed $height
     * @return void
     */
    public function set_height($height)
    {
        $this->height = $height;
    }

    /**
     * Set width
     * @param mixed $width
     * @return void
     */
    public function set_width($width)
    {
        $this->width = $width;
    }

    /**
     * Set length
     * @param mixed $length
     * @return void
     */
    public function set_length($length)
    {
        $this->length = $length;
    }

    /**
     * Retrieve shipping rates
     * @return mixed
     */
    public function get_rates()
    {
        return $this->rates;
    }

    /**
     * Call API with curl and decode response
     * @param string $type
     * @param string $url
     * @param array $params
     * @return array|object
     */
    private function call_api($url, $params)
    {

        $headers = [
            "Authorization: " . $this->token,
            'Content-Type: application/json',
            'Accept-Charset: utf-8',
        ];

        $params_fmt = json_encode($params);
        $curl_url = $this->postaqui_url . $url;
        $process = curl_init();

        curl_setopt($process, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($process, CURLOPT_POST, 1);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($process, CURLOPT_TIMEOUT, 0);
        curl_setopt($process, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($process, CURLOPT_POSTFIELDS, $params_fmt);
        curl_setopt($process, CURLOPT_HEADER, false);
        curl_setopt($process, CURLOPT_URL, $curl_url);

        $return = curl_exec($process);
        $status = curl_getinfo($process, CURLINFO_HTTP_CODE);

        if ($status == 0) {
            return ['error' => 1, 'message' => 'Não foi possível conectar-se com o Postaqui. Tente novamente mais tarde'];
        }

        if ($status > 400) {
            if ($status == 401) {
                return ['error' => 401, 'message' => 'Acesso não autorizado. Verifique se o seu token foi preenchido corretamente ou fale com a Postaqui'];
            }

            $message = curl_error($process);
            curl_close($process);
            return ['error' => $status, 'message' => $message];
        }

        $return_decode = json_decode($return);

        if (isset($return_decode->data->error)) {
            return ['error' => $return_decode->data->error, 'message' => $return_decode->data->message];
        }

        curl_close($process);

        return json_decode($return);
    }

    /**
     * Calculate shipping
     * @return void
     */
    public function calculate_shipping()
    {

        $data = [
            'cepOrigem' => $this->source_zip_code,
            'cepDestino' => $this->target_zip_code,
            'peso' => $this->weight,
            'valorDeclarado' => $this->package_value,
            'altura' => $this->height,
            'largura' => $this->width,
            'comprimento' => $this->length
        ];

        $return = $this->call_api('shipping-company/calc-price-deadline', $data);
        $this->rates = [];

        if ($return != []) {
            if (is_array($return) && isset($return['error'])) {
                $this->rates = (object)$return;
            } else {
                $this->rates = $return->data;
            }
        }
    }

    /**
     * Send labels to API
     * @param array $label_data
     * @return object
     */
    public function send_labels($label_data)
    {
        $return = $this->call_api('tickets', $label_data);
        return (object)$return;
    }

    /**
     * Transform array into URL params
     * @param array $array
     * @param string $prefix
     * @return string
     */
    public function array_to_params($array, $prefix = null)
    {

        if (!is_array($array)) {
            return $array;
        }

        $params = [];
        foreach ($array as $k => $v) {
            if (is_null($v)) {
                continue;
            }
            if ($prefix && $k && !is_int($k)) {
                $k = $prefix . '[' . $k . ']';
            } elseif ($prefix) {
                $k = $prefix . '[]';
            }
            if (is_array($v)) {
                $params[] = $this->array_to_params($v, $k);
            } else {
                $params[] = $k . '=' . urlencode((string)$v);
            }
        }

        return implode('&', $params);
    }

    /**
     * Format value to retrieve only numbers
     * @param string $val
     * @return string
     */
    public function only_numbers($val)
    {
        return preg_replace("/[^0-9]/", "", $val);
    }
}
