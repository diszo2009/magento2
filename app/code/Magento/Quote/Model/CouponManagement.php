<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Quote\Model;

use \Magento\Quote\Api\CouponManagementInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Coupon management object.
 */
class CouponManagement implements CouponManagementInterface
{
    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $quoteRepository;

    /**
     * Constructs a coupon read service object.
     *
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository Quote repository.
     */
    public function __construct(
        \Magento\Quote\Model\QuoteRepository $quoteRepository
    ) {
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function get($cartId)
    {
        /** @var  \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        return $quote->getCouponCode();
    }

    /**
     * {@inheritdoc}
     */
    public function set($cartId, $couponCode)
    {
        /** @var  \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException("Cart $cartId doesn't contain products");
        }
        $quote->getShippingAddress()->setCollectShippingRates(true);

        try {
            $quote->setCouponCode($couponCode);
            $this->quoteRepository->save($quote->collectTotals());
        } catch (\Exception $e) {
            throw new CouldNotSaveException('Could not apply coupon code');
        }
        if ($quote->getCouponCode() != $couponCode) {
            throw new NoSuchEntityException('Coupon code is not valid');
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($cartId)
    {
        /** @var  \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException("Cart $cartId doesn't contain products");
        }
        $quote->getShippingAddress()->setCollectShippingRates(true);
        try {
            $quote->setCouponCode('');
            $this->quoteRepository->save($quote->collectTotals());
        } catch (\Exception $e) {
            throw new CouldNotDeleteException('Could not delete coupon code');
        }
        if ($quote->getCouponCode() != '') {
            throw new CouldNotDeleteException('Could not delete coupon code');
        }
        return true;
    }
}
