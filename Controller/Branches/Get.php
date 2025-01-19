<?php

namespace Dragonfly\NovaposhtaBranch\Controller\Branches;

use Dragonfly\NovaposhtaBasic\Api\Data\ApiDepotsInterface;
use Dragonfly\NovaposhtaBasic\Model\ResourceModel\ApiDepotsModel\ApiDepotsCollection;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Locale\Resolver;

class Get implements HttpGetActionInterface
{
    public const BRANCHES_TYPE = [
        '9a68df70-0267-42a8-bb5c-37f427e36ee4',
        '8d5a980d-391c-11dd-90d9-001a92567626',
        '841339c7-591a-42e2-8233-7a0a00f0ed6f'
    ];

    /**
     * @var JsonFactory
     */
    private JsonFactory $resultJsonFactory;

    /**
     * @var ApiDepotsCollection
     */
    private ApiDepotsCollection $apiDepotsCollection;

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
     * @param ApiDepotsCollection $apiDepotsCollection
     * @param RequestInterface $request
     * @param Resolver $store
     */
    public function __construct(
        JsonFactory         $resultJsonFactory,
        ApiDepotsCollection $apiDepotsCollection,
        RequestInterface    $request,
        Resolver            $store
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->apiDepotsCollection = $apiDepotsCollection;
        $this->request = $request;
        $this->store = $store;
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute()
    {
        $paramCityId = $this->request->getParam('city_id', null);
        $locale = $this->getLocale();

        if ($paramCityId == null) {
            $resultJson = $this->resultJsonFactory->create();
            return $resultJson->setData([]);
        }

        $items = $this->apiDepotsCollection
            ->addFilter('city_ref', $paramCityId)
            ->addFieldToFilter('type_of_warehouse', array('in'=>self::BRANCHES_TYPE))
            ->getItems();

        $descriptionField = ApiDepotsInterface::NAME_LANG_ASSOCIATION[$locale];

        $data = [];
        foreach ($items as $item) {
            $data[intval($item->getData(ApiDepotsInterface::NUMBER))] = [
                'id' => $item->getData('ref'),
                'name' => $item->getData($descriptionField),
                'number' => $item->getData(ApiDepotsInterface::NUMBER)

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
