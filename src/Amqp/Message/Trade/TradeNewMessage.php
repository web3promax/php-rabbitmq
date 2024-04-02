<?php
namespace Amqp\Message\Trade;

use App\Amqp\Message;
use Amqp\Message\Hydrator\MessageHydrator;
use App\Amqp\Message\Entity\Agent;
use App\Amqp\Message\Entity\Client;
use Amqp\Message\Entity\Tx;

/**
 * @api {POST} /trade 1. To insert trade
 * @apiGroup Trade
 * @apiName new
 * @apiDescription
 *
 * Outlets negotiate trade introductory info and send to App when agreement has been reached.
 * It triggers first event in trade automation sequence.
 *
 * Initial trade info is replicated and saved by each Service.
 *
 * @apiHeader (Subscribers) _All_     Save initial trade message to database.
 * @apiHeader (Subscribers) app     For selling trades find where we can accept fiat and build invoice.
 *                              For buying trades launch bill request process to collect credentials how to pay.
 * @apiHeader (Subscribers) chat    Create XMPP room and invite humans if required.
 *
 * @apiParam (Body)     {string{13}}            id              PHP uniqid() generated by salepoint.
 * @apiParam (Body)     {string}                state=NEW       Always "NEW".
 * @apiParam (Body)     {datetime}              created         MySQL format.
 * @apiParam (Body)     {bool}                  selling         TRUE if we give out crypto.
 * @apiParam (Body)     {float}                 rate            Exchange rate agreed upon.
 * @apiParam (Body)     {Tx}                    input           What we take from this trade.
 * @apiParam (Body)     {Tx}                    output          What we give in this trade.
 * @apiParam (Body)     {Agent}                 agent           Information about salepoint. See Agent below.
 * @apiParam (Body)     {Client}                client          Information about client. See Client below.
 * @apiParam (Body)     {string}                [refid]         Unique identifier generated by platform, if any.
 * @apiParam (Body)     {array}                 [extra]         Raw API output.
 *
 * @apiParam (Agent)    {string}                login           Unique username on platform.
 * @apiParam (Agent)    {string="lbtc"}         platform        Unique market name where salepoint is operating.
 * @apiParam (Client)   {string}                login           Unique username on platform.
 * @apiParam (Client)   {string="lbtc"}         platform        Unique market name where salepoint is operating.
 * @apiUse Tx
 * @apiParam (Tx)       {string="REQUEST"}      state
 *
 * @apiSuccessExample {json} Example
 *  {
    "id": "5eafdaa7e44b9",
    "state": "NEW",
    "created": "2020-05-04 08:00:05",
    "selling": true,
    "rate": 8795.57,
    "input": {
        "state": "REQUEST",
        "method": "BANK",
        "currency": "usd",
        "amount": 500,
    },
    "output": {
        "state": "REQUEST",
        "method": "blockchain",
        "currency": "btc",
        "amount": 0.0568468,
    },
    "agent": {
        "platform": "lbtc",
        "login": "mercurio21"
    },
    "client": {
        "platform": "lbtc",
        "login": "Jaswinbkk"
    },
    "refid": "59789721"
  }
 *
 *
 * @property bool $selling
 * @property float $rate
 * @property \App\Amqp\Message\Entity\Tx $input
 * @property \App\Amqp\Message\Entity\Tx $output
 * @property \App\Amqp\Message\Entity\Agent $agent
 * @property \App\Amqp\Message\Entity\Client $client
 * @property string $refid
 */
class TradeNewMessage extends Message
{
    public string $id;
    public string $state = 'NEW';
    public \DateTime $created;

    public function __construct($body = '', array $headers = [])
    {
        $this->id = uniqid();
        $this->PROPS_TO_HEADERS += ['selling'];
        parent::__construct($body, $headers);
    }

    public function isValid($value = null)
    {
        $this->errorMessages = [];

        $required = ['id', 'state', 'created', 'selling', 'rate', 'input', 'output', 'agent', 'client'];
        foreach ($required as $prop) {
            if (!isset($this->$prop)) {
                $this->errorMessages["empty_$prop"] = "$prop is not set.";
                return false; // now to avoid method calls on null
            }
        }
        if ($this->state != 'NEW') {
            $this->errorMessages['invalid_state'] = 'State must NEW.';
        }
        if ($this->created->getTimestamp() > time()) {
            $this->errorMessages['in_future'] = 'Creation time is in future.';
        }

        $childs = ['input', 'output', 'agent', 'client'];
        foreach ($childs as $prop) {
            $child = $this->$prop;
            if (!$child->isValid()) {
                $this->errorMessages += $child->getMessages();
            }
            if ($child instanceof Tx) {
                if ($child->state != Tx::STATE_REQUEST) {
                    $this->errorMessages['tx_state'] = "Transaction state must be REQUEST.";
                }
            }
        }

        return empty($this->errorMessages);
    }

    public function getHydrator() : MessageHydrator
    {
        if (!isset($this->hydrator)) {
            $hydrator = parent::getHydrator();
            $hydrator->addStrategy('agent',     new Agent());
            $hydrator->addStrategy('client',    new Client());
            $hydrator->addStrategy('input',     new Tx());
            $hydrator->addStrategy('output',    new Tx());
        }
        return $this->hydrator;
    }
}
