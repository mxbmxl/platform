<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\Controller;

use Oro\Bundle\DataGridBundle\Async\Topics;
use Oro\Bundle\DataGridBundle\Controller\GridController;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class GridControllerTest extends WebTestCase
{
    use MessageQueueAssertTrait;

    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testShouldSendExportMessage()
    {
        $this->client->request('GET', $this->getUrl('oro_datagrid_export_action', [
            'gridName' => 'accounts-grid',
            'format' => 'csv',
            'accounts-grid' => [
                '_pager' => [
                    '_page' => 1,
                    '_per_page' => 25,
                ],
                '_parameters' => ['view' => '__all__'],
                '_appearance' => ['_type' => 'grid'],
                '_sort_by' => ['name' => 'ASC'],
                '_columns' => 'name1.contactName1.contactEmail1.contactPhone1.ownerName1.createdAt1.updatedAt1.tags1',
            ],
        ]));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertCount(1, $result);
        $this->assertTrue($result['successful']);

        $this->assertMessageSent(Topics::EXPORT_CSV, [
            'format' => 'csv',
            'batchSize' => GridController::EXPORT_BATCH_SIZE,
            'parameters' => [
                'gridName' => 'accounts-grid',
                'gridParameters' => [
                    '_pager' => [
                        '_page' => '1',
                        '_per_page' => '25',
                    ],
                    '_parameters' => ['view' => '__all__'],
                    '_appearance' => ['_type' => 'grid'],
                    '_sort_by' => ['name' => 'ASC'],
                    '_columns' => 'name1.contactName1.contactEmail1.'
                        .'contactPhone1.ownerName1.createdAt1.updatedAt1.tags1',

                ],
                FormatterProvider::FORMAT_TYPE => 'excel',
            ],
            'userId' => 1,
        ]);
    }
}