<?php

namespace App\Services;

use App\Interfaces\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TapPaymentService extends BasePaymentService implements PaymentGatewayInterface
{
    protected $api_key;

    public function __construct()
    {
        $this->base_url = env("TAP_BASE_URL");
        $this->api_key = env("TAP_API_KEY");
        $this->header = [
            'accept' => 'application/json',
            "Content-Type" => "application/json",
            "Authorization" => "Bearer " . $this->api_key,
        ];
    }

    public function sendPayment(Request $request): array
    {
        //validate data before sending it
        $data = $this->formatData($request);
        $response = $this->buildRequest('POST', '/v2/charges/', $data);
        //handel payment response data and return it
        if ($response->getData(true)['success']) {
            return ['success' => true, 'url' => $response->getData(true)['data']['transaction']['url']];
        }
        return ['success' => false, 'url' => null];
    }

    public function callBack(Request $request): bool
    {
        $chargeId = $request->input('tap_id');
        $response = $this->buildRequest('GET', "/v2/charges/$chargeId");
        $response_data = $response->getData(true);
        Storage::put('tap_callback.json', json_encode($response_data));
        if ($response_data['success'] && $response_data['data']['status'] == 'CAPTURED') {
            //save order data and return true
            return true;
        }
        return false;
    }

    protected function formatData(Request $request): array
    {
        return [
            "amount" => $request->get('amount'),
            "currency" => $request->get('currency'),
            "customer" => [
                "first_name" => $request->get('first_name', 'test'),
                "email" => $request->get('email', 'test@test.com'),
            ],
            'source' => ['id' => 'src_all'],
            'redirect' => ['url' => $request->getSchemeAndHttpHost() . '/api/payment/callback?gateway_type=' . request('gateway_type')]
        ];
    }
}
