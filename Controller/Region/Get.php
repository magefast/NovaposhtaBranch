<?php

namespace Dragonfly\NovaposhtaBranch\Controller\Region;

use Dragonfly\NovaposhtaBasic\Api\Data\ApiRegionInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Locale\Resolver;

class Get implements HttpGetActionInterface
{
    /**
     * @var JsonFactory
     */
    private JsonFactory $resultJsonFactory;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var Resolver
     */
    private Resolver $store;

    /**
     * @var ApiRegionInterface
     */
    private ApiRegionInterface $apiRegion;

    /**
     * @param JsonFactory $resultJsonFactory
     * @param RequestInterface $request
     * @param Resolver $store
     * @param ApiRegionInterface $apiRegion
     */
    public function __construct(
        JsonFactory        $resultJsonFactory,
        RequestInterface   $request,
        Resolver           $store,
        ApiRegionInterface $apiRegion
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request = $request;
        $this->store = $store;
        $this->apiRegion = $apiRegion;
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute()
    {
        $locale = $this->getLocale();

        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($this->getRegionList($locale));
    }

    /**
     * @return array|string|string[]
     */
    private function getLocale()
    {
        $defaultLocale = $this->store->getLocale();
        $locale = $this->request->getParam('locale', $defaultLocale);
        $locale = str_replace('-', '_', $locale);

        return $locale;
    }

    /**
     * @param $locale
     * @return array
     */
    public function getRegionList($locale): array
    {
        return $this->apiRegion->getRegionList($locale);
    }
}
