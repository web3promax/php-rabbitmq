<?php
namespace Amqp\Message\Ad;

/**
 * @api {GET} /ad/list 1. To list ads
 * @apiGroup Ad
 * @apiName api-list
 * @apiDescription Retreive list of active ads.
 * @apiSampleRequest off
 *
 * @apiParam (Body)     {int}       id          Generated by app.
 * @apiParam (Body)     {string}    way         "bid" is when we are buying and user is selling, "ask" otherwise.
 * @apiParam (Body)     {string}    symbol      Crypto-fiat pair in ad. Ex: "btcusd"
 * @apiParam (Body)     {string}    method      Single supported payment method for this ad.
 * @apiParam (Body)     {float}     rate        Exchange rate in offer without salepoint fees.
 * @apiParam (Body)     {float}     visible     Current visibility status.
 * @apiParam (Body)     {string}    [country]   ISO-2 code. Advertise only in this country, if set.
 * @apiParam (Body)     {int}       [minFiat]   Minimum trade amount.
 * @apiParam (Body)     {int}       [maxFiat]   Maximum trade amount.
 * @apiParam (Body)     {string}    [title]     Ad title visible to clients.
 * @apiParam (Body)     {string}    [terms]     Ad terms visible to clients.
 *
 * @apiSuccessExample {json} Example
[
  {
    "id": 12,
    "way": "ask",
    "symbol": "btcusd",
    "method": "wing",
    "rate": 8795.57,
    "country": "KH"
  },
  {
    "id": 13,
    "way": "ask",
    "symbol": "btcusd",
    "method": "cash",
    "rate": 8800,
    "country": "KH"
  },
  {
    "id": 14,
    "way": "bid",
    "symbol": "btcusd",
    "method": "wing",
    "rate": 8999.20,
    "country": "KH"
  }
]
 *
 */

/**
 * @api {EVENT} ad.new 2. Ad is published
 * @apiGroup Ad
 * @apiName event-ad-new
 * @apiSampleRequest off
 * @apiDescription
 * Published by app once a minute to inform salepoints of current ads and rates.
 *
 * @apiHeader (Subscribers) Outlets    Create/update ad as published on this endpoint. Disable if no longer published.
 *
 * @property string $country
 * @property int $minRate
 * @property int $maxRate
 * @property string $title
 * @property string $terms
 */
class AdNewMessage extends \Amqp\Message
{
    public $id;
    public string $way;
    const WAY_ASK = 'ask';
    const WAY_BID = 'bid';

    public string $symbol;
    public string $method;
    public float    $rate;

    public bool $visible = true;

    public function isValid($value = null)
    {
        $value ??= $this;

        $this->errorMessages = [];

        $required = ['id', 'way', 'symbol', 'method', 'rate'];
        foreach ($required as $prop) {
            if (!isset($value->$prop)) {
                $this->errorMessages["empty_$prop"] = "$prop is not set.";
                return false; // now to avoid method calls on null
            }
        }
        if (!in_array($value->way, [self::WAY_ASK, self::WAY_BID])) {
            $this->errorMessages['invalid_way'] = "Unknown way: {$value->way}";
        }

        return empty($this->errorMessages);
    }
}
