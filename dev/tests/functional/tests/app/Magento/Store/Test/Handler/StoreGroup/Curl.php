<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Store\Test\Handler\StoreGroup;

use Magento\Mtf\Fixture\FixtureInterface;
use Magento\Mtf\Handler\Curl as AbstractCurl;
use Magento\Mtf\System\Config;
use Magento\Mtf\Util\Protocol\CurlInterface;
use Magento\Mtf\Util\Protocol\CurlTransport;
use Magento\Mtf\Util\Protocol\CurlTransport\BackendDecorator;

/**
 * Class Curl
 * Curl handler for creating Store Group
 */
class Curl extends AbstractCurl implements StoreGroupInterface
{
    /**
     * POST request for creating store group
     *
     * @param FixtureInterface $fixture
     * @return array
     * @throws \Exception
     */
    public function persist(FixtureInterface $fixture = null)
    {
        $data = $this->prepareData($fixture);
        $url = $_ENV['app_backend_url'] . 'admin/system_store/save';
        $curl = new BackendDecorator(new CurlTransport(), new Config());
        $curl->write(CurlInterface::POST, $url, '1.1', [], $data);
        $response = $curl->read();
        $curl->close();
        if (!strpos($response, 'data-ui-id="messages-message-success"')) {
            throw new \Exception("Store group entity creating by curl handler was not successful! Response: $response");
        }

        return ['group_id' => $this->getStoreGroupIdByGroupName($fixture->getName())];
    }

    /**
     * Get store id by store name
     *
     * @param string $storeName
     * @return int
     * @throws \Exception
     */
    protected function getStoreGroupIdByGroupName($storeName)
    {
        //Set pager limit to 2000 in order to find created store group by name
        $url = $_ENV['app_backend_url'] . 'admin/system_store/index/sort/group_title/dir/asc/limit/2000';
        $curl = new BackendDecorator(new CurlTransport(), new Config());
        $curl->addOption(CURLOPT_HEADER, 1);
        $curl->write(CurlInterface::POST, $url, '1.0');
        $response = $curl->read();

        $expectedUrl = '/admin/system_store/editGroup/group_id/';
        $expectedUrl = preg_quote($expectedUrl);
        $expectedUrl = str_replace('/', '\/', $expectedUrl);
        preg_match('/' . $expectedUrl . '([0-9]*)\/(.)*>' . $storeName . '<\/a>/', $response, $matches);

        if (empty($matches)) {
            throw new \Exception('Cannot find store group id');
        }

        return intval($matches[1]);
    }

    /**
     * Prepare data from text to values
     *
     * @param FixtureInterface $fixture
     * @return array
     */
    protected function prepareData(FixtureInterface $fixture)
    {
        $categoryId = $fixture->getDataFieldConfig('root_category_id')['source']->getCategory()->getId();
        $websiteId = $fixture->getDataFieldConfig('website_id')['source']->getWebsite()->getWebsiteId();
        $data = [
            'group' => [
                'name' => $fixture->getName(),
                'root_category_id' => $categoryId,
                'website_id' => $websiteId,
                'group_id' => $fixture->hasData('group_id') ? $fixture->getGroupId() : '',
            ],
            'store_action' => 'add',
            'store_type' => 'group',
        ];

        return $data;
    }
}
