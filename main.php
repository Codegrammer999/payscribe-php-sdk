<?php
require __DIR__."/vendor/autoload.php";

use Dotenv\Dotenv;
use GuzzleHttp\Client;

class Payscribe {
    // Properties
    const SANDBOX_URL = 'https://www.payscribe.ng/sandbox';
    const LIVE_URL = 'https://www.payscribe.ng/api/v1';
    const ACCOUNT_URL = 'https://www.payscribe.ng/api/account';
    const ENV_REQUIRED = [
        'PAYSCRIBE_KEY',
        'PAYSCRIBE_USERNAME',
        'PAYSCRIBE_TYPE'
    ];

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $type;

    /**
     * Payscribe constructor.
     *
     * @param string $key
     * @param string $username
     * @param string $type
     */
    public function __construct(string $key, string $username, string $type)
    {
        $this->key = $key;
        $this->username = $username;
        $this->type = $type;
    }

    /**
     * @return Payscribe
     * @throws PayscribeEnvMissingException
     */
    public static function createFromEnv(): Payscribe
    {
        $dotenv = Dotenv::createImmutable(__DIR__);
        $dotenv->load();

        foreach (self::ENV_REQUIRED as $var) {
            if (!isset($_ENV[$var])) {
                throw new PayscribeEnvMissingException("Missing environment variable: {$var}");
            }
        }

        return new self(
            $_ENV['PAYSCRIBE_KEY'],
            $_ENV['PAYSCRIBE_USERNAME'],
            $_ENV['PAYSCRIBE_TYPE']
        );
    }

    /**
     * Send request to Payscribe API
     *
     * @param string $path
     * @param array $data
     *
     * @return array
     *
     * @throws PayscribeApiException
     */
    private function sendRequest(string $path, array $data): array
    {
        $client = new Client();

        $options = [
            'json' => $data,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->key,
                'Cache-Control' => 'no-cache',
            ],
        ];

        try {
            $url = $this->getApiUrl($path);
            $response = $client->post($url, $options);
            $result = json_decode((string) $response->getBody(), true);
            if ($response->getStatusCode() !== 200) {
                throw new PayscribeApiException($result['message'] ?? 'An unknown error occurred');
            }

            return $result;
        } catch (GuzzleHttp\Exception\RequestException $e) {
            throw new PayscribeApiException($e->getMessage());
        }
    }

    /**
     * Get the API URL for the given path
     *
     * @param string $path
     *
     * @return string
     */
    private function getApiUrl(string $path): string
    {
        if ($path === 'account') {
            return self::ACCOUNT_URL;
        }

        if ($this->type === 'sandbox') {
            return self::SANDBOX_URL . '/' . $path;
        }

        return self::LIVE_URL . '/' . $path;
    }

    /**
     * Get account information
     *
     * @return array
     */
    public function account(): array
    {
        return $this->sendRequest('account', [
            'username' => $this->username,
        ]);
    }

    /**
     * Lookup data for a given network
     *
     * @param string $network
     *
     * @return array
     */
    public function dataLookup(string $network): array
    {
        return $this->sendRequest('data/lookup', [
            'network' => $network,
        ]);
    }

    /**
     * Vend data for a given plan, recipient and network
     *
     * @param string $plan
     * @param string $recipient
     * @param string $network
     *
     * @return array
     */
    public function vendData(string $plan, string $recipient, string $network): array
    {
        return $this->sendRequest('data/vend', [
            'plan' => $plan,
            'recipient' => $recipient,
            'network' => $network,
        ]);
    }

    /**
     * Recharge card
     *
     * @param int $qty
     * @param int $amount
     * @param string $name
     *
     * @return array
     */
    public function rechargeCard(int $qty, int $amount, string $name): array
    {
        return $this->sendRequest('rechargecard', [
            'qty' => $qty,
            'amount' => $amount,
            'display_name' => $name,
        ]);
    }

    /**
     * Get cards
     *
     * @param string $trans_id
     *
     * @return array
     */
    public function getCards(string $trans_id): array
    {
        return $this->sendRequest('cards', [
            'trans_id' => $trans_id,
        ]);
    }

    public  function validateCard(string $type,string $no): array
    {
        $data =["type"=>$type,"account"=>$no];
        $url = "multichoice/validate";
        return $this->sendRequest($url,$data);
    }
    
    public  function multichoicePay(string $plan,string $code,string $phone,string $token,string $trans_id): array
    {
        $data =["plan"=>$plan,
                "productCode"=>$code,
                "phone"=>$phone,
                "productToken"=>$token,
                "trans_id"=>$trans_id 
               ];
        $url = "multichoice/vend";
        return $this->sendRequest($url,$data);
    }
    
    public  function startimesValidate(string $no,string $amount): array
    {
        $data =["account"=>$no,"amount"=>$amount];
        $url = "startimes/validate";
        return $this->sendRequest($url,$data);
    }
    
    public  function startimesVend(string $no,string $amount,string $pcode,string $Pcode,string $phone,string $name,string $tid): array
    {
        $data =[ 
                "smart_card_no"=>$no,
                "amount"=>$amount,
                "product_code"=>$pcode,
                "productCode"=>$Pcode,
                "phone_number"=>$phone,
                "customer_name"=>$name,
                "transaction_id"=>$tid
               ];
        $url = "startimes/vend";
        return $this->sendRequest($url,$data);
    }
    
    public  function atwLookup(): array
    {
        $data = [];
        $url = "airtime_to_wallet";
        return $this->sendRequest($url,$data);
    }
    
    public  function atwProcess(string $network,string $amount,string $phone,string $from): array
    {
        $data =["network"=>$network,"amount"=>$amount,"phone_number"=>$phone,"from"=>$from];
        $url = "airtime_to_wallet/vend";
        return $this->sendRequest($url,$data);
    }
    
    public  function vendAirtime(string $network,string $amount,string $recipent): array
    {
        $data =["network"=>$network,"amount"=>$amount,"recipent"=>$recipent,"ported"=>false];
        $url = "airtime";
        return $this->sendRequest($url,$data);
    }
    
    public  function validateElectricity(string $number,string $type,string $amount,string $service): array
    {
        $data =["meter_number"=>$number,"meter_type"=>$type,"amount"=>$amount,"service"=>$service];
        $url = "electricity/validate";
        return $this->sendRequest($url,$data);
    }

    public  function electricityVend(string $productCode,string $productToken,string $phone): array
    {
        $data =["productCode"=>$productCode,"productToken"=>$productToken,"phone"=>$phone];
        $url = "electricity/vend";
        return $this->sendRequest($url,$data);
    }
}



class PayscribeEnvMissingException extends \Exception
{
}

class PayscribeApiException extends \Exception
{
}
