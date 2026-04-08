<?php

namespace Spatie\LaravelMobilePass\Builders\Google;

use Chiiya\Passes\Google\Components\Common\Barcode as GoogleBarcode;
use Chiiya\Passes\Google\Components\Common\DateTime as GoogleDateTime;
use Chiiya\Passes\Google\Components\Common\LocalizedString;
use Chiiya\Passes\Google\Components\Common\TimeInterval;
use Chiiya\Passes\Google\Enumerators\BarcodeType as GoogleBarcodeType;
use Chiiya\Passes\Google\Enumerators\Offer\RedemptionChannel;
use Chiiya\Passes\Google\Enumerators\ReviewStatus;
use Chiiya\Passes\Google\Enumerators\State;
use Chiiya\Passes\Google\Http\GoogleClient;
use Chiiya\Passes\Google\JWT;
use Chiiya\Passes\Google\Passes\OfferClass;
use Chiiya\Passes\Google\Passes\OfferObject;
use Chiiya\Passes\Google\Repositories\OfferClassRepository;
use Illuminate\Support\Str;

class CouponPassBuilder extends GooglePassBuilder
{
    public function generate(): string
    {
        $credentials = $this->getServiceCredentials();
        $issuerId = $this->getIssuerId();

        $classId = "{$issuerId}.coupon-" . Str::slug($this->organisationName ?? 'default');
        $objectId = "{$issuerId}." . Str::uuid()->toString();

        $client = GoogleClient::createAuthenticatedClient($credentials);
        $repository = new OfferClassRepository($client);

        $offerClass = new OfferClass(
            title: $this->description ?? 'Coupon',
            redemptionChannel: RedemptionChannel::INSTORE,
            provider: $this->organisationName ?? 'Default Provider',
            reviewStatus: ReviewStatus::UNDER_REVIEW,
            id: $classId,
            issuerName: $this->organisationName ?? 'Default Organisation',
            localizedDetails: LocalizedString::make('pl', $this->description ?? 'Coupon'),
            hexBackgroundColor: $this->hexBackgroundColor ?? '#ff0000',
        );

        try {
            $repository->get($classId);
            $repository->update($offerClass);
        } catch (\Exception $e) {
            $repository->create($offerClass);
        }

        $object = new OfferObject(
            classId: $classId,
            id: $objectId,
            state: State::ACTIVE,
            barcode: new GoogleBarcode(
                type: GoogleBarcodeType::QR_CODE,
                value: 'https://retiva.io/pass/' . ($this->serialNumber ?? '000000'),
                alternateText: $this->serialNumber ?? '000000',
            ),
            validTimeInterval: new TimeInterval(
                start: new GoogleDateTime(date: $this->validTimeInterval['start'] ?? now()),
                end: new GoogleDateTime(date: $this->validTimeInterval['end'] ?? now()->addMonth())
            ),
            textModulesData: $this->mapFieldsToTextModules(),
        );

        return (new JWT(
            iss: $credentials->client_email,
            key: $credentials->private_key,
            origins: [request()->getSchemeAndHttpHost()],
        ))->addOfferObject($object)->sign();
    }
}
