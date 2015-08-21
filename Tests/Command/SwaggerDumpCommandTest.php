<?php

namespace Nelmio\ApiDocBundle\Tests\Command;

class SwaggerDumpCommandTest extends CommandTestCase
{
    /**
     * @dataProvider viewProvider
     *
     * @param string $view
     * @param array $expectedJsonFiles Names of json files expected to be generated
     */
    public function testDumpFileWithViewOption($view, array $expectedJsonFiles)
    {
        $dumpDir = $this->getTmpDir().'/'.$view;

        $input = array(
            'command' => 'api:swagger:dump',
            '--view' => $view,
            'destination' => $dumpDir
        );
        $this->tester->run($input);

        $this->assertSame(0, $this->tester->getStatusCode());

        foreach ($expectedJsonFiles as $jsonFileName) {
            $expectedJsonFile = __DIR__ . '/../Fixtures/swagger/'.$view.'/'.$jsonFileName;
            $dumpFile = $dumpDir.'/'.$jsonFileName;
            $this->assertJsonFileEqualsJsonFile($expectedJsonFile, $dumpFile);
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
                    'api-docs.json',
                    'others.json',
                    'resources.json',
                )
            ),
            'premium' => array(
                'premium',
                array(
                    'api-docs.json',
                    'other-resources.json',
                    'others.json',
                    'resources.json',
                    'tests2.json'
                )
            ),
            'default' => array(
                'default',
                array(
                    'api-docs.json',
                    'other-resources.json',
                    'others.json',
                    'resources.json',
                    'TestResource.json',
                    'tests.json',
                    'tests2.json'
                )
            ),
        );
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot selectively dump a resource with the --list-only flag.
     */
    public function testListOnlyAndResourceOptionsAreBothSet()
    {
        $input = array(
            'command' => 'api:swagger:dump',
            '--list-only' => true,
            '--resource' => 'Resource',
        );
        $this->tester->run($input);
    }

    /**
     * @dataProvider listOnlyViewsProvider
     * @param string $view View name
     * @param array $expectedApis Expected api resources list
     */
    public function testListOnly($view, array $expectedApis)
    {
        $input = array(
            'command' => 'api:swagger:dump',
            '--list-only' => true,
            '--view' => $view,
            '--pretty' => true,
        );
        $this->tester->run($input);

        $this->assertEquals(0, $this->tester->getStatusCode());
        $display = $this->tester->getDisplay();

        $this->assertJson($display);
        $json = json_decode($display, true);
        $this->assertArrayHasKey('apis', $json);

        $this->assertEquals(
            $expectedApis,
            $json['apis']
        );
    }

    /**
     * @return array
     */
    public static function listOnlyViewsProvider()
    {
        return array(
            'default' => array(
                'view' => 'default',
                'expectedApis' => array (
                    array (
                        'path' => '/other-resources',
                        'description' => 'Operations on another resource.',
                    ),
                    array (
                        'path' => '/resources',
                        'description' => 'Operations on resource.',
                    ),
                    array (
                        'path' => '/tests',
                        'description' => null,
                    ),
                    array (
                        'path' => '/tests',
                        'description' => null,
                    ),
                    array (
                        'path' => '/tests2',
                        'description' => null,
                    ),
                    array (
                        'path' => '/TestResource',
                        'description' => null,
                    ),
                    array (
                        'path' => '/others',
                        'description' => 'Popo',
                    ),
                    array (
                        'path' => '/others',
                        'description' => 'Popo',
                    ),
                    array (
                        'path' => '/others',
                        'description' => 'Popo',
                    ),
                    array (
                        'path' => '/others',
                        'description' => 'Popo',
                    ),
                    array (
                        'path' => '/others',
                        'description' => 'Popo',
                    ),
                )
            ),
            'test' => array(
                'test' => 'test',
                'expectedApis' => array (
                    array (
                        'path' => '/resources',
                        'description' => 'Operations on resource.',
                    ),
                    array (
                        'path' => '/others',
                        'description' => 'Popo',
                    ),
                    array (
                        'path' => '/others',
                        'description' => 'Popo',
                    ),
                    array (
                        'path' => '/others',
                        'description' => 'Popo',
                    ),
                    array (
                        'path' => '/others',
                        'description' => 'Popo',
                    ),
                    array (
                        'path' => '/others',
                        'description' => 'Popo',
                    ),
                )
            )
        );
    }

    /**
     * @dataProvider resourceViewsProvider
     *
     * @param string $view View name
     * @param string $resource Resource name
     */
    public function testResource($view, $resource)
    {
        $input = array(
            'command' => 'api:swagger:dump',
            '--resource' => $resource,
            '--view' => $view,
            '--pretty' => true,
        );
        $this->tester->run($input);

        $this->assertEquals(0, $this->tester->getStatusCode());
        $display = $this->tester->getDisplay();

        $this->assertJson($display);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Resource "invalid" does not exist.
     */
    public function testInvalidResource()
    {
        $input = array(
            'command' => 'api:swagger:dump',
            '--resource' => 'invalid',
        );
        $this->tester->run($input);
    }

    /**
     * @return array
     */
    public static function resourceViewsProvider()
    {
        return array(
            'test, others' => array(
                'view' => 'test',
                'resource' => 'others',
            ),
            'test, invalid' => array(
                'view' => 'test',
                'resource' => 'resources'
            )
        );
    }

}
