<?php

namespace Nelmio\ApiDocBundle\Tests\Command;

use Symfony\Component\PropertyAccess\PropertyAccess;

class DumpCommandTest extends CommandTestCase
{
    /**
     * @dataProvider viewProvider
     *
     * @param string $view Command view option value
     * @param array $expectedMethodsCount Expected resource methods count
     * @param array $expectedMethodValues Expected resource method values
     */
    public function testDumpWithViewOption($view, array $expectedMethodsCount, array $expectedMethodValues)
    {
        $input = array(
            'command' => 'api:doc:dump',
            '--view' => $view,
            '--format' => 'json',
        );
        $this->tester->run($input);

        $display = $this->tester->getDisplay();

        $this->assertJson($display);

        $json = json_decode($display);

        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($expectedMethodsCount as $propertyPath => $expectedCount) {
            $this->assertCount($expectedCount, $accessor->getValue($json, $propertyPath));
        }

        foreach ($expectedMethodValues as $propertyPath => $expectedValue) {
            $this->assertEquals($expectedValue, $accessor->getValue($json, $propertyPath));
        }
    }

    /**
     * @return array
     */
    public static function viewProvider()
    {
        return array(
            'test' => array(
                'test',
                array(
                    '/api/resources' => 1,
                ),
                array(
                    '/api/resources[0].method' => 'GET',
                    '/api/resources[0].uri' => '/api/resources.{_format}',
                )
            ),
            'premium' => array(
                'premium',
                array(
                    '/api/resources' => 2,
                ),
                array(
                    '/api/resources[0].method' => 'GET',
                    '/api/resources[0].uri' => '/api/resources.{_format}',
                    '/api/resources[1].method' => 'POST',
                    '/api/resources[1].uri' => '/api/resources.{_format}',
                )
            ),
            'default' => array(
                'default',
                array(
                    '/api/resources' => 4,
                ),
                array(
                    '/api/resources[0].method' => 'GET',
                    '/api/resources[0].uri' => '/api/resources.{_format}',
                    '/api/resources[1].method' => 'POST',
                    '/api/resources[1].uri' => '/api/resources.{_format}',
                    '/api/resources[2].method' => 'GET',
                    '/api/resources[2].uri' => '/api/resources/{id}.{_format}',
                    '/api/resources[3].method' => 'DELETE',
                    '/api/resources[3].uri' => '/api/resources/{id}.{_format}',
                )
            ),
        );
    }
}
