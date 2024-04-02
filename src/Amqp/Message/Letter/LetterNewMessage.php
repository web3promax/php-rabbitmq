<?php
namespace Amqp\Message\Letter;

use Amqp\Message;

/**
 * @api {POST} chat,lbtc  1. Send a message
 * @apiGroup Letter
 * @apiName new
 * @apiDescription
 * Published by chat to make salepoints send outgoing message.
 *
 * Published by salepoints when incoming message is received.
 *
 * @apiHeader (Actions) [chat]     Echo incoming message to trade chat. Optionally pass message to Dialogflow.
 * @apiHeader (Actions) [lbtc]     Submit outgoing message to API.
 *
 * @apiParam (Body)     {string{13}}    id              Generated uuid() of message.
 * @apiParam (Body)     {string}        state=NEW       Always "NEW".
 * @apiParam (Body)     {bool}          incoming        True if already received, false if to be sent.
 * @apiParam (Body)     {string{13}}    trade_id
 * @apiParam (Body)     {string}        body            Message body. May be empty if e.g. file is being sent.
 * @apiParam (Body)     {array}         [file]          Base64 encoded file and metadata.
 * @apiParam (Body)     {string}        [refid]         Unique identifier generated by platform, for incoming messages.
 *
 * @apiParam (File)     {string}        data          Base64 encoded string.
 * @apiParam (File)     {string}        type          MIME encoded type.
 * @apiParam (File)     {string}        [name]        User defined file name.
 *
 * @apiSuccessExample {json} Example
 *  {
     "id": "5eafdaa7e0000",
     "state": "NEW",
     "incoming": true,
     "trade_id": "5eafdaa7e44b9",
     "body": "Hi! Are you there?"
    }
 *
 * @property array $file
 * @property string $refid
 */
class LetterNewMessage extends Message
{
    public string   $id;
    public string   $state      = 'NEW';
    public bool     $incoming;
    public string   $trade_id;
    public string   $body       = '';

    public function isValid($value = null)
    {
        $value ??= $this;

        $this->errorMessages = [];

        $required = ['id', 'state', 'incoming', 'trade_id', 'body'];
        foreach ($required as $prop) {
            if (!isset($value->$prop)) {
                $this->errorMessages["empty_$prop"] = "$prop is not set.";
                return false; // now to avoid method calls on null
            }
        }
        if ($value->state != 'NEW') {
            $this->errorMessages['invalid_state'] = 'State must NEW.';
        }

        return empty($this->errorMessages);
    }
}
