<?php

namespace App\Services;

use App\Interfaces\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymobPaymentService extends BasePaymentService implements PaymentGatewayInterface
{

    protected $api_key;
    protected $integrations_id;

    public function __construct()
    {
        // i declare the base_url and header property in the parent class so it can be used in the child class here
        $this->base_url = env("BAYMOB_BASE_URL");
        $this->header = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $this->api_key = env("BAYMOB_API_KEY");
        $this->integrations_id = [4865052, 4864845];
    }


    public function sendPayment(Request $request):array
    {
        $this->header['Authorization'] = 'Bearer ' . $this->generateToken(); // append the Authorization token to the header array
        $data['api_source'] = "INVOICE";
        $data['integrations'] = $this->integrations_id;

        //validate data before sending it
        $data = $request->all();

        $response = $this->buildRequest('POST', '/api/ecommerce/orders', $data);

        //handel payment response data and return it
        if ($response->getData(true)['success']) {
            return ['success' => true, 'url' => $response->getData(true)['data']['url']];
        }

        return ['success' => false, 'url' => route('payment.failed')];
    }

    public function callBack(Request $request): bool
    {
        $response = $request->all();
        Storage::put('paymob_response.json', json_encode($request->all()));

        if (isset($response['success']) && $response['success'] === 'true') {
            return true;
        }
        return false;

    }

    protected function generateToken()
    {
        $response = $this->buildRequest('POST', '/api/auth/tokens', ['api_key' => $this->api_key]);
        return $response->getData(true)['data']['token'];
    }

}