<?php

namespace Crm\PaymentsModule;

use Crm\PaymentsModule\Repository\PaymentsRepository;
use Crm\PaymentsModule\Repository\RecurrentPaymentsRepository;
use Crm\SubscriptionsModule\Repository\SubscriptionTypesRepository;
use Nette\Database\Table\ActiveRow;

class RecurrentPaymentsResolver
{
    private $recurrentPaymentsRepository;

    private $subscriptionTypesRepository;

    private $paymentsRepository;

    public function __construct(
        RecurrentPaymentsRepository $recurrentPaymentsRepository,
        SubscriptionTypesRepository $subscriptionTypesRepository,
        PaymentsRepository $paymentsRepository
    ) {
        $this->recurrentPaymentsRepository = $recurrentPaymentsRepository;
        $this->subscriptionTypesRepository = $subscriptionTypesRepository;
        $this->paymentsRepository = $paymentsRepository;
    }

    /**
     * resolveSubscriptionType determines which subscriptionType will be used within the next charge.
     *
     * @param ActiveRow $recurrentPayment
     *
     * @return ActiveRow
     */
    public function resolveSubscriptionType(ActiveRow $recurrentPayment)
    {
        $subscriptionType = $this->subscriptionTypesRepository->find($recurrentPayment->subscription_type_id);
        if ($recurrentPayment->next_subscription_type_id) {
            $subscriptionType = $this->subscriptionTypesRepository->find($recurrentPayment->next_subscription_type_id);
        }
        if ($subscriptionType->next_subscription_type_id) {
            $subscriptionType = $this->subscriptionTypesRepository->find($subscriptionType->next_subscription_type_id);
        }
        return $subscriptionType;
    }

    /**
     * resolveCustomChargeAmount calculates only non-standard charge amount which can be used
     * as "amount" parameter in PaymentsRepository::add().
     *
     * @param ActiveRow $recurrentPayment
     *
     * @return float|null
     */
    public function resolveCustomChargeAmount(ActiveRow $recurrentPayment) : ?float
    {
        $amount = null;
        if ($recurrentPayment->custom_amount != null) {
            $amount = $recurrentPayment->custom_amount;
        }
        return $amount;
    }

    /**
     * resolveChargeAmount calculates final amount of money to be charged next time, including
     * the standard subscription price.
     *
     * @param ActiveRow $recurrentPayment
     *
     * @return float
     */
    public function resolveChargeAmount(ActiveRow $recurrentPayment) : float
    {
        $subscriptionType = $this->resolveSubscriptionType($recurrentPayment);
        $amount = $subscriptionType->price;
        if ($recurrentPayment->custom_amount != null) {
            $amount = $recurrentPayment->custom_amount;
        }
        return $amount;
    }
}