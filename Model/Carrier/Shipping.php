<?php

namespace Dragonfly\NovaposhtaBranch\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\OfflineShipping\Model\Carrier\Flatrate\ItemPriceCalculator;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Psr\Log\LoggerInterface;

class Shipping extends AbstractCarrier implements CarrierInterface
{
    /**
     * @var string
     */
    protected $_code = 'novaposhtabranch';

    /**
     * @var bool
     */
    protected $_isFixed = true;

    /**
     * @var ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var MethodFactory
     */
    protected $_rateMethodFactory;

    /**
     * @var ItemPriceCalculator
     */
    private $itemPriceCalculator;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     * @param ItemPriceCalculator $itemPriceCalculator
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory         $rateErrorFactory,
        LoggerInterface      $logger,
        ResultFactory        $rateResultFactory,
        MethodFactory        $rateMethodFactory,
        ItemPriceCalculator  $itemPriceCalculator,
        array                $data = []
    )
    {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->itemPriceCalculator = $itemPriceCalculator;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * Collect and get rates
     *
     * @param RateRequest $request
     * @return Result|bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $freeBoxes = $this->getFreeBoxesCount($request);
        $this->setFreeBoxes($freeBoxes);

        /** @var Result $result */
        $result = $this->_rateResultFactory->create();

        $shippingPrice = $this->getShippingPrice($request, $freeBoxes);

        if ($shippingPrice !== false) {
            $method = $this->createResultMethod($shippingPrice);
            $result->append($method);
        }

        return $result;
    }

    /**
     * Get count of free boxes
     *
     * @param RateRequest $request
     * @return int
     */
    private function getFreeBoxesCount(RateRequest $request)
    {
        $freeBoxes = 0;
        if ($request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }

                $freeShippingMethod = $item->getFreeShippingMethod();

                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    $freeBoxes += $this->getFreeBoxesCountFromChildren($item);
                } elseif (
                    $item->getFreeShipping()
                    && ($freeShippingMethod === null || $freeShippingMethod === 'novaposhtabranch_novaposhtabranch')
                ) {
                    $freeBoxes += $item->getQty();
                }
            }
        }
        return $freeBoxes;
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }

    /**
     * Returns shipping price
     *
     * @param RateRequest $request
     * @param int $freeBoxes
     * @return bool|float
     */
    private function getShippingPrice(RateRequest $request, $freeBoxes)
    {
        $shippingPrice = false;

        $configPrice = $this->getConfigData('price');
        $shippingPrice = $this->itemPriceCalculator->getShippingPricePerOrder($request, $configPrice, $freeBoxes);

        $shippingPrice = $this->getFinalPriceWithHandlingFee($shippingPrice);

        if ($shippingPrice !== false && $request->getPackageQty() == $freeBoxes) {
            $shippingPrice = '0.00';
        }
        return $shippingPrice;
    }

    /**
     * Creates result method
     *
     * @param int|float $shippingPrice
     * @return Method
     */
    private function createResultMethod($shippingPrice)
    {
        /** @var Method $method */
        $method = $this->_rateMethodFactory->create();

        $method->setCarrier('novaposhtabranch');
        $method->setCarrierTitle($this->getConfigData('title'));

        $method->setMethod('novaposhtabranch');
        $method->setMethodTitle($this->getConfigData('name'));

        $method->setPrice($shippingPrice);
        $method->setCost($shippingPrice);
        return $method;
    }

    /**
     * Returns free boxes count of children
     *
     * @param mixed $item
     * @return mixed
     */
    private function getFreeBoxesCountFromChildren($item)
    {
        $freeBoxes = 0;
        foreach ($item->getChildren() as $child) {
            if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                $freeBoxes += $item->getQty() * $child->getQty();
            }
        }
        return $freeBoxes;
    }
}
