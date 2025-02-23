<?php

namespace App\Http\Livewire\Subscription;

use Livewire\Component;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class PricingPlans extends Component
{
    public function subscribeStripe($type)
    {
        Stripe::setApiKey(config('subscription.stripe_api_key'));
        switch ($type) {
            case 'basic-monthly':
                $priceId = config('subscription.stripe_price_id_basic_monthly');
                break;
            case 'basic-yearly':
                $priceId = config('subscription.stripe_price_id_basic_yearly');
                break;
            case 'ultimate-monthly':
                $priceId = config('subscription.stripe_price_id_ultimate_monthly');
                break;
            case 'pro-monthly':
                $priceId = config('subscription.stripe_price_id_pro_monthly');
                break;
            case 'pro-yearly':
                $priceId = config('subscription.stripe_price_id_pro_yearly');
                break;
            case 'ultimate-yearly':
                $priceId = config('subscription.stripe_price_id_ultimate_yearly');
                break;
            default:
                $priceId = config('subscription.stripe_price_id_basic_monthly');
                break;
        }
        if (!$priceId) {
            $this->emit('error', 'Price ID not found! Please contact the administrator.');
            return;
        }
        $payload = [
            'client_reference_id' => auth()->user()->id . ':' . currentTeam()->id,
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'tax_id_collection' => [
                'enabled' => true,
            ],
            'mode' => 'subscription',
            'success_url' => route('dashboard', ['success' => true]),
            'cancel_url' => route('subscription.index', ['cancelled' => true]),
        ];
        $customer = currentTeam()->subscription?->stripe_customer_id ?? null;
        if ($customer) {
            $payload['customer'] = $customer;
            $payload['customer_update'] = [
                'name' => 'auto'
            ];
        } else {
            $payload['customer_email'] = auth()->user()->email;
        }
        $session = Session::create($payload);
        return redirect($session->url, 303);
    }
}
