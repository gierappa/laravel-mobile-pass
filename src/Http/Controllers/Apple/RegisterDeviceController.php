<?php

namespace Spatie\LaravelMobilePass\Http\Controllers\Apple;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelMobilePass\Actions\Apple\RegisterDeviceAction;
use Spatie\LaravelMobilePass\Support\Config;

/**
 * Registering a Device to Receive Push Notifications for a Pass
 * https://developer.apple.com/documentation/walletpasses/register-a-pass-for-update-notifications
 */
class RegisterDeviceController extends Controller
{
    public function __invoke(Request $request)
    {
        /** @var class-string<RegisterDeviceAction> $actionClass */
        $actionClass = Config::getActionClass('register_device', RegisterDeviceAction::class);

        Log::debug('Passkit register device', [
            'deviceId' => $request->route('deviceId'),
            'passTypeId' => $request->route('passTypeId'),
            'passSerial' => $request->route('passSerial'),
            'pushToken' => $request->get('pushToken'),
        ]);

        try {
            $registration = (new $actionClass)->execute(
                $request->route('deviceId'),
                $request->get('pushToken'),
                $request->route('passTypeId'),
                $request->route('passSerial'),
            );
        } catch (\Throwable $e) {
            Log::error('Passkit register device failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw $e;
        }

        Log::debug('Passkit register device success', ['wasRecentlyCreated' => $registration->wasRecentlyCreated]);

        return response()
            ->noContent()
            ->setStatusCode($registration->wasRecentlyCreated ? 201 : 200);
    }
}
