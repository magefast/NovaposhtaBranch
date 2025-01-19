<?php

namespace Dragonfly\NovaposhtaBranch\Controller\Cities;

use Dragonfly\NovaposhtaBasic\Api\Data\ApiCityInterface;
use Dragonfly\NovaposhtaBasic\Model\ResourceModel\ApiCityModel\ApiCityCollection;
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
     * @var ApiCityCollection
     */
    private ApiCityCollection $apiCityCollection;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var Resolver
     */
    private Resolver $store;

    /**
     * @param JsonFactory $resultJsonFactory
     * @param ApiCityCollection $apiCityCollection
     * @param RequestInterface $request
     * @param Resolver $store
     */
    public function __construct(
        JsonFactory       $resultJsonFactory,
        ApiCityCollection $apiCityCollection,
        RequestInterface  $request,
        Resolver          $store
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->apiCityCollection = $apiCityCollection;
        $this->request = $request;
        $this->store = $store;
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute()
    {
        $paramRegionId = $this->request->getParam('region_id', null);
        $locale = $this->getLocale();

        if ($paramRegionId == null) {
            $resultJson = $this->resultJsonFactory->create();
            return $resultJson->setData([]);
        }

        $items = $this->apiCityCollection->addFilter('area', $paramRegionId)->getItems();

        $descriptionField = ApiCityInterface::NAME_LANG_ASSOCIATION[$locale];
        $descriptionFieldUa = ApiCityInterface::NAME_LANG_ASSOCIATION['uk_UA'];
        $typeField = ApiCityInterface::TYPE_LANG_ASSOCIATION[$locale];

        $data = [];
        foreach ($items as $item) {
            $name = $item->getData($descriptionField) ?: $item->getData($descriptionFieldUa);
            $name = $item->getData($typeField) . ' ' . $name;
            $data[] = [
                'id' => $item->getData('ref'),
                'name' => $name
            ];
        }

        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($data);
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
}
