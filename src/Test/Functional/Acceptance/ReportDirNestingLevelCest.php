<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Test\Functional\Codeception\Docker;

/**
 * This test runs on the latest version of PHP
 */
class ReportDirNestingLevelCest extends AbstractCest
{
    /**
     * @var string
     */
    private $expectedPathLocalXml = '/app/pub/errors/local.xml';

    /**
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function _before(\CliTester $I)
    {
        parent::_before($I);
        $I->cloneTemplate();
        $I->addEceComposerRepo();
    }

    /**
     * The case when the property ERROR_REPORT_DIR_NESTING_LEVEL not set in .magento.env.yaml file
     * and the file <magento_root>/errors/local.xml not exist on build phase
     * and the environment variable MAGE_ERROR_REPORT_DIR_NESTING_LEVEL not exist on deploy phase
     *
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testDefault(\CliTester $I)
    {
        $I->assertTrue($I->runEceToolsCommand('build', Docker::BUILD_CONTAINER));
        $I->startEnvironment();
        $I->assertTrue($I->runEceToolsCommand('deploy', Docker::DEPLOY_CONTAINER));
        $I->assertContains(
            $this->getTemplateLocalXm(1),
            $I->grabFileContent('/pub/errors/local.xml')
        );
        $log = $I->grabFileContent('/var/log/cloud.log');
        $I->assertContains(
            sprintf(
                'NOTICE: The file %s with the `config.report.dir_nesting_level` property: `1` was created.',
                $this->expectedPathLocalXml
            ),
            $log
        );
    }

    /**
     * The case when the property ERROR_REPORT_DIR_NESTING_LEVEL set in .magento.env.yaml file
     * and the file <magento_root>/errors/local.xml not exist on build phase
     * and the environment variable MAGE_ERROR_REPORT_DIR_NESTING_LEVEL not exist on deploy phase
     *
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testWithPropertyInMagentoEnvFile(\CliTester $I)
    {
        $I->uploadToContainer(
            'files/report_dir_nesting_level/.magento.env.yaml',
            '/.magento.env.yaml',
            Docker::BUILD_CONTAINER
        );
        $I->assertTrue($I->runEceToolsCommand('build', Docker::BUILD_CONTAINER));
        $I->startEnvironment();
        $I->assertTrue($I->runEceToolsCommand('deploy', Docker::DEPLOY_CONTAINER));
        $I->assertContains(
            $this->getTemplateLocalXm(3),
            $I->grabFileContent('/pub/errors/local.xml')
        );
        $log = $I->grabFileContent('/var/log/cloud.log');
        $I->assertContains(
            sprintf(
                'NOTICE: The file %s with the `config.report.dir_nesting_level` property: `3` was created.',
                $this->expectedPathLocalXml
            ),
            $log
        );
    }

    /**
     * The case when the property ERROR_REPORT_DIR_NESTING_LEVEL set in .magento.env.yaml file
     * and the file <magento_root>/errors/local.xml exists with property `config.report.dir_nesting_level`
     * and the environment variable MAGE_ERROR_REPORT_DIR_NESTING_LEVEL not exist on deploy phase
     *
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testWithPropertyInLocalXmlFile(\CliTester $I)
    {
        $I->uploadToContainer(
            'files/report_dir_nesting_level/.magento.env.yaml',
            '/.magento.env.yaml',
            Docker::BUILD_CONTAINER
        );
        $I->uploadToContainer(
            'files/report_dir_nesting_level/local_with_property.xml',
            '/pub/errors/local.xml',
            Docker::BUILD_CONTAINER
        );
        $I->assertTrue($I->runEceToolsCommand('build', Docker::BUILD_CONTAINER));
        $I->startEnvironment();
        $I->assertTrue($I->runEceToolsCommand('deploy', Docker::DEPLOY_CONTAINER));
        $log = $I->grabFileContent('/var/log/cloud.log');
        $I->assertContains(
            sprintf(
                'NOTICE: The error reports configuration file `%s` exists.'
                . ' Value of the property `%s` of .magento.env.yaml will be ignored',
                $this->expectedPathLocalXml,
                BuildInterface::VAR_ERROR_REPORT_DIR_NESTING_LEVEL
            ),
            $log
        );
    }

    /**
     * The case when the property ERROR_REPORT_DIR_NESTING_LEVEL set in .magento.env.yaml file
     * and the file <magento_root>/errors/local.xml exists with property `config.report.dir_nesting_level`
     * and the environment variable MAGE_ERROR_REPORT_DIR_NESTING_LEVEL exists on deploy phase
     *
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testWhenSetEnvVar(\CliTester $I)
    {
        $I->uploadToContainer(
            'files/report_dir_nesting_level/.magento.env.yaml',
            '/.magento.env.yaml',
            Docker::BUILD_CONTAINER
        );
        $I->uploadToContainer(
            'files/report_dir_nesting_level/local_with_property.xml',
            '/pub/errors/local.xml',
            Docker::BUILD_CONTAINER
        );
        $I->assertTrue($I->runEceToolsCommand('build', Docker::BUILD_CONTAINER));
        $I->startEnvironment();
        $I->assertTrue($I->runEceToolsCommand(
            'deploy',
            Docker::DEPLOY_CONTAINER,
            [],
            ['MAGE_ERROR_REPORT_DIR_NESTING_LEVEL' => 7]
        ));
        $log = $I->grabFileContent('/var/log/cloud.log');
        $I->assertContains(
            sprintf(
                'NOTICE: The error reports configuration file `%s` exists.'
                . ' Value of the property `%s` of .magento.env.yaml will be ignored',
                $this->expectedPathLocalXml,
                BuildInterface::VAR_ERROR_REPORT_DIR_NESTING_LEVEL
            ),
            $log
        );
    }

    /**
     * The case when the property ERROR_REPORT_DIR_NESTING_LEVEL set in .magento.env.yaml file
     * and the file <magento_root>/errors/local.xml exists without property `config.report.dir_nesting_level`
     * and the environment variable MAGE_ERROR_REPORT_DIR_NESTING_LEVEL not exist on deploy phase
     *
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testErrorReportDirNestingLevelNotSet(\CliTester $I)
    {
        $I->uploadToContainer(
            'files/report_dir_nesting_level/.magento.env.yaml',
            '/.magento.env.yaml',
            Docker::BUILD_CONTAINER
        );
        $I->uploadToContainer(
            'files/report_dir_nesting_level/local_without_property.xml',
            '/pub/errors/local.xml',
            Docker::BUILD_CONTAINER
        );
        $I->assertTrue($I->runEceToolsCommand('build', Docker::BUILD_CONTAINER));
        $I->startEnvironment();
        $I->assertTrue($I->runEceToolsCommand('deploy', Docker::DEPLOY_CONTAINER));
        $log = $I->grabFileContent('/var/log/cloud.log');
        $I->assertContains(
            sprintf(
                'NOTICE: The error reports configuration file `%s` exists.'
                . ' Value of the property `%s` of .magento.env.yaml will be ignored',
                $this->expectedPathLocalXml,
                BuildInterface::VAR_ERROR_REPORT_DIR_NESTING_LEVEL
            ),
            $log
        );
        $I->assertContains(
            '- The directory nesting level value for error reporting has not been configured.',
            $log
        );
        $I->assertContains(
            'You can configure the setting using the `config.report.dir_nesting_level`'
            . ' in the file ' . $this->expectedPathLocalXml,
            $log
        );
    }

    /**
     * The case when the property ERROR_REPORT_DIR_NESTING_LEVEL set in .magento.env.yaml file
     * and the file <magento_root>/errors/local.xml exists with invalid content
     * and the environment variable MAGE_ERROR_REPORT_DIR_NESTING_LEVEL not exist on deploy phase
     *
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testWithInvalidLocalXmlFile(\CliTester $I)
    {
        $I->uploadToContainer(
            'files/report_dir_nesting_level/.magento.env.yaml',
            '/.magento.env.yaml',
            Docker::BUILD_CONTAINER
        );
        $I->uploadToContainer(
            'files/report_dir_nesting_level/invalid_local.xml',
            '/pub/errors/local.xml',
            Docker::BUILD_CONTAINER
        );
        $I->assertTrue($I->runEceToolsCommand('build', Docker::BUILD_CONTAINER));
        $I->startEnvironment();
        $I->assertTrue($I->runEceToolsCommand('deploy', Docker::DEPLOY_CONTAINER));
        $log = $I->grabFileContent('/var/log/cloud.log');
        $I->assertContains(
            sprintf(
                'NOTICE: The error reports configuration file `%s` exists.'
                . ' Value of the property `%s` of .magento.env.yaml will be ignored',
                $this->expectedPathLocalXml,
                BuildInterface::VAR_ERROR_REPORT_DIR_NESTING_LEVEL
            ),
            $log
        );
        $I->assertContains(
            "- Config of the file {$this->expectedPathLocalXml} is invalid.",
            $log
        );
        $I->assertContains(
            'Fix the directory nesting level configuration for error reporting in the file '
            . $this->expectedPathLocalXml,
            $log
        );
    }

    /**
     * Returns xml configuration
     *
     * @param $value
     * @return string
     */
    private function getTemplateLocalXm($value): string
    {
        return <<<XML
<?xml version="1.0"?>
<config>
    <report>
        <dir_nesting_level>{$value}</dir_nesting_level>
    </report>
</config> 
XML;
    }
}
