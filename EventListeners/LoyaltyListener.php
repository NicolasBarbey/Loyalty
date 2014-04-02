<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : info@thelia.net                                                      */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      This program is free software; you can redistribute it and/or modify         */
/*      it under the terms of the GNU General Public License as published by         */
/*      the Free Software Foundation; either version 3 of the License                */
/*                                                                                   */
/*      This program is distributed in the hope that it will be useful,              */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of               */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                */
/*      GNU General Public License for more details.                                 */
/*                                                                                   */
/*      You should have received a copy of the GNU General Public License            */
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/

namespace Loyalty\EventListeners;

use CreditAccount\CreditAccount;
use CreditAccount\Event\CreditAccountEvent;
use Loyalty\Model\LoyaltyQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;


/**
 * Class LoyaltyListener
 * @package Loyalty\EventListeners
 * @author Manuel Raynaud <mraynaud@openstudio.fr>
 */
class LoyaltyListener implements EventSubscriberInterface
{

    public function updateCreditAccount(OrderEvent $event)
    {
        $order = $event->getOrder();

        if ($order->isPaid()) {
            $total = $order->getTotalAmount();

            $loyaltySlice = LoyaltyQuery::create()
                ->filterByMin($total, Criteria::LESS_EQUAL)
                ->filterByMax($total, Criteria::GREATER_EQUAL)
                ->findOne();

            if ($loyaltySlice) {
                $amount = $loyaltySlice->getAmount();

                $creditEvent = new CreditAccountEvent($order->getCustomer(), $amount);

                $event->getDispatcher()->dispatch(CreditAccount::CREDIT_ACCOUNT_ADD_AMOUNT, $creditEvent);
            }
        }
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return [
            TheliaEvents::ORDER_UPDATE_STATUS => ['updateCreditAccount', 120]
        ];
    }
}