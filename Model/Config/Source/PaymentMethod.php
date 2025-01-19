<?php

declare(strict_types=1);

namespace Dragonfly\NovaposhtaBranch\Model\Config\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Payment\Api\Data\PaymentMethodInterface;
use Magento\Store\Model\ScopeInterface;

class PaymentMethod implements OptionSourceInterface
{
    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var array */
    protected $options;

    /**
     * PaymentMethod constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function toOptionArray()
    {
        if (!$this->options) {
            $paymentMethods = $this->scopeConfig->getValue('payment', ScopeInterface::SCOPE_STORE);

            $options = [];

            /**
             * @var string $code
             * @var PaymentMethodInterface $method
             */
            foreach ($paymentMethods as $code => $method) {
                if (isset($method['title'])) {
                    $title = $method['title'];
                } else {
                    $title = $code; // vault or substitution do not have title
                }

                $options[$code] = [
                    'value' => $code,
                    'label' => $title
                ];
            }

            usort($options, function ($optionA, $optionB) {
                $labelA = (string)$optionA['label'];
                $labelB = (string)$optionB['label'];
                return strcasecmp($labelA, $labelB);
            });

            $this->options = $options;
        }

        return $this->options;
    }
}
