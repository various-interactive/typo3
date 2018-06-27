<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Install\Tests\Unit\Service;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Tests\Unit\Utility\AccessibleProxies\ExtensionManagementUtilityAccessibleProxy;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Install\Service\Exception\ConfigurationChangedException;
use TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class SilentConfigurationUpgradeServiceTest extends UnitTestCase
{
    /**
     * @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configurationManager;

    /**
     * @var \TYPO3\CMS\Core\Package\UnitTestPackageManager A backup of unit test package manager
     */
    protected $backupPackageManager;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->backupPackageManager = ExtensionManagementUtilityAccessibleProxy::getPackageManager();
    }

    /**
     * Tear down
     */
    protected function tearDown()
    {
        ExtensionManagementUtilityAccessibleProxy::setPackageManager($this->backupPackageManager);
        parent::tearDown();
    }

    /**
     * @param array $methods
     */
    protected function createConfigurationManagerWithMockedMethods(array $methods)
    {
        $this->configurationManager = $this->getMockBuilder(ConfigurationManager::class)
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * Dataprovider for configureBackendLoginSecurity
     *
     * @return array
     */
    public function configureBackendLoginSecurityLocalconfiguration(): array
    {
        return [
            ['', 'rsa', true, false],
            ['normal', 'rsa', true, true],
            ['rsa', 'normal', false, true],
        ];
    }

    /**
     * @test
     * @dataProvider configureBackendLoginSecurityLocalconfiguration
     * @param string $current
     * @param string $setting
     * @param bool $isPackageActive
     * @param bool $hasLocalConfig
     */
    public function configureBackendLoginSecurity($current, $setting, $isPackageActive, $hasLocalConfig)
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        /** @var $packageManager PackageManager|\PHPUnit_Framework_MockObject_MockObject */
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->expects($this->any())
            ->method('isPackageActive')
            ->will($this->returnValue($isPackageActive));
        ExtensionManagementUtility::setPackageManager($packageManager);

        $currentLocalConfiguration = [
            ['BE/loginSecurityLevel', $current]
        ];
        $closure = function () {
            throw new MissingArrayPathException('Path does not exist in array', 1476109311);
        };

        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            ]
        );
        if ($hasLocalConfig) {
            $this->configurationManager->expects($this->once())
                ->method('getLocalConfigurationValueByPath')
                ->will($this->returnValueMap($currentLocalConfiguration));
        } else {
            $this->configurationManager->expects($this->once())
                ->method('getLocalConfigurationValueByPath')
                ->will($this->returnCallback($closure));
        }
        $this->configurationManager->expects($this->once())
            ->method('setLocalConfigurationValueByPath')
            ->with($this->equalTo('BE/loginSecurityLevel'), $this->equalTo($setting));

        $this->expectException(ConfigurationChangedException::class);

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('configureBackendLoginSecurity');
    }

    /**
     * @test
     */
    public function removeObsoleteLocalConfigurationSettingsIfThereAreOldSettings()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $obsoleteLocalConfigurationSettings = [
            'SYS/form_enctype',
        ];

        $currentLocalConfiguration = [
            [$obsoleteLocalConfigurationSettings, true]
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'removeLocalConfigurationKeysByPath',
            ]
        );
        $this->configurationManager->expects($this->exactly(1))
            ->method('removeLocalConfigurationKeysByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));

        $this->expectException(ConfigurationChangedException::class);

        $silentConfigurationUpgradeServiceInstance->_set('obsoleteLocalConfigurationSettings', $obsoleteLocalConfigurationSettings);
        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('removeObsoleteLocalConfigurationSettings');
    }

    /**
     * @test
     */
    public function doNotRemoveObsoleteLocalConfigurationSettingsIfThereAreNoOldSettings()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $obsoleteLocalConfigurationSettings = [
            'SYS/form_enctype',
        ];

        $currentLocalConfiguration = [
            [$obsoleteLocalConfigurationSettings, false]
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'removeLocalConfigurationKeysByPath',
            ]
        );
        $this->configurationManager->expects($this->exactly(1))
            ->method('removeLocalConfigurationKeysByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));

        $silentConfigurationUpgradeServiceInstance->_set('obsoleteLocalConfigurationSettings', $obsoleteLocalConfigurationSettings);
        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('removeObsoleteLocalConfigurationSettings');
    }

    /**
     * @test
     */
    public function doNotGenerateEncryptionKeyIfExists()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $currentLocalConfiguration = [
            ['SYS/encryptionKey', 'EnCrYpTiOnKeY']
        ];

        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            ]
        );
        $this->configurationManager->expects($this->exactly(1))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->never())
            ->method('setLocalConfigurationValueByPath');

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('generateEncryptionKeyIfNeeded');
    }

    /**
     * @test
     */
    public function generateEncryptionKeyIfNotExists()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $closure = function () {
            throw new MissingArrayPathException('Path does not exist in array', 1476109266);
        };

        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            ]
        );
        $this->configurationManager->expects($this->exactly(1))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnCallback($closure));
        $this->configurationManager->expects($this->once())
            ->method('setLocalConfigurationValueByPath')
            ->with($this->equalTo('SYS/encryptionKey'), $this->isType('string'));

        $this->expectException(ConfigurationChangedException::class);

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('generateEncryptionKeyIfNeeded');
    }

    /**
     * Data provider for transferHttpSettings
     *
     * @return array
     */
    public function httpSettingsMappingDataProvider(): array
    {
        return [
            'No changes overridden in Local Configuration' => [
                ['timeout' => 100],
                ['HTTP/timeout' => 100],
                false
            ],
            'Old and unused settings removed' => [
                ['adapter' => 'curl'],
                [],
                true
            ],
            'Old and used settings changed' => [
                ['protocol_version' => '1.1'],
                ['HTTP/version' => '1.1'],
                true
            ],

            /** redirect options */
            'Redirects moved to default' => [
                ['follow_redirects' => true],
                [],
                true
            ],
            'Redirects moved #1' => [
                ['follow_redirects' => true, 'max_redirects' => 200, 'strict_redirects' => false],
                ['HTTP/allow_redirects' => ['max' => 200]],
                true
            ],
            'Redirects moved #2' => [
                ['follow_redirects' => false, 'max_redirects' => 200, 'strict_redirects' => false],
                ['HTTP/allow_redirects' => false],
                true
            ],
            'Redirects moved #3' => [
                ['follow_redirects' => true, 'max_redirects' => 400, 'strict_redirects' => 1],
                ['HTTP/allow_redirects' => ['max' => 400, 'strict' => true]],
                true
            ],

            /** Proxy settings */
            'Proxy host set' => [
                ['proxy_host' => 'vpn.myproxy.com'],
                ['HTTP/proxy' => 'http://vpn.myproxy.com'],
                true
            ],
            'Proxy host set + port' => [
                ['proxy_host' => 'vpn.myproxy.com', 'proxy_port' => 8080],
                ['HTTP/proxy' => 'http://vpn.myproxy.com:8080'],
                true
            ],
            'Proxy host set + port + verification' => [
                ['proxy_host' => 'vpn.myproxy.com', 'proxy_port' => 8080, 'proxy_auth_scheme' => 'basic', 'proxy_user' => 'myuser', 'proxy_password' => 'mysecret'],
                ['HTTP/proxy' => 'http://myuser:mysecret@vpn.myproxy.com:8080'],
                true
            ],

            /** SSL verification */
            'Only ssl_capath set, invalid migration' => [
                ['ssl_capath' => '/foo/bar/'],
                [],
                true
            ],
            'Verification activated, but only ssl_capath set, using default' => [
                ['ssl_verify_peer' => 1, 'ssl_capath' => '/foo/bar/'],
                [],
                true
            ],
            'Verification activated, with ssl_capath and ssl_cafile set' => [
                ['ssl_verify_peer' => 1, 'ssl_capath' => '/foo/bar/', 'ssl_cafile' => 'supersecret.crt'],
                ['HTTP/verify' => '/foo/bar/supersecret.crt'],
                true
            ],

            /** SSL key + passphrase */
            'SSL key certification' => [
                ['ssl_local_cert' => '/foo/bar/supersecret.key'],
                ['HTTP/ssl_key' => '/foo/bar/supersecret.key'],
                true
            ],
            'SSL key certification + passphrase' => [
                ['ssl_local_cert' => '/foo/bar/supersecret.key', 'ssl_passphrase' => 'donotcopypasteme'],
                ['HTTP/ssl_key' => ['/foo/bar/supersecret.key', 'donotcopypasteme']],
                true
            ],
            'SSL key passphrase only - no migration' => [
                ['ssl_passphrase' => 'donotcopypasteme'],
                [],
                true
            ],
        ];
    }

    /**
     * @test
     * @dataProvider httpSettingsMappingDataProvider
     * @param array $currentLocalConfiguration
     * @param array $newSettings
     * @param bool $localConfigurationNeedsUpdate
     */
    public function transferHttpSettingsIfSet($currentLocalConfiguration, $newSettings, $localConfigurationNeedsUpdate)
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $this->createConfigurationManagerWithMockedMethods(
            [
                'setLocalConfigurationValuesByPathValuePairs',
                'removeLocalConfigurationKeysByPath',
                'getLocalConfiguration'
            ]
        );

        $this->configurationManager->expects($this->any())
            ->method('getLocalConfiguration')
            ->willReturn(['HTTP' => $currentLocalConfiguration]);
        if ($localConfigurationNeedsUpdate) {
            if (!empty($newSettings)) {
                $this->configurationManager->expects($this->once())
                    ->method('setLocalConfigurationValuesByPathValuePairs')
                    ->with($newSettings);
            }
            $this->configurationManager->expects($this->atMost(1))->method('removeLocalConfigurationKeysByPath');
        }

        if ($localConfigurationNeedsUpdate) {
            $this->expectException(ConfigurationChangedException::class);
        }

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('transferHttpSettings');
    }

    /**
     * @test
     */
    public function disableImageMagickDetailSettingsIfImageMagickIsDisabled()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $currentLocalConfiguration = [
            ['GFX/im', 0],
            ['GFX/im_path', ''],
            ['GFX/im_path_lzw', ''],
            ['GFX/imagefile_ext', 'gif,jpg,png'],
            ['GFX/thumbnails', 0]
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'getDefaultConfigurationValueByPath',
                'setLocalConfigurationValuesByPathValuePairs',
            ]
        );
        $this->configurationManager->expects($this->exactly(5))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->never())
            ->method('getDefaultConfigurationValueByPath');
        $this->configurationManager->expects($this->once())
            ->method('setLocalConfigurationValuesByPathValuePairs')
            ->withConsecutive(
                [['GFX/imagefile_ext' => 'gif,jpg,jpeg,png']]
            );

        $this->expectException(ConfigurationChangedException::class);

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('disableImageMagickDetailSettingsIfImageMagickIsDisabled');
    }

    /**
     * @test
     */
    public function doNotDisableImageMagickDetailSettingsIfImageMagickIsEnabled()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $currentLocalConfiguration = [
            ['GFX/im', 1],
            ['GFX/im_path', ''],
            ['GFX/im_path_lzw', ''],
            ['GFX/imagefile_ext', 'gif,jpg,jpeg,png'],
            ['GFX/thumbnails', 0]
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'getDefaultConfigurationValueByPath',
                'setLocalConfigurationValuesByPathValuePairs',
            ]
        );
        $this->configurationManager->expects($this->exactly(5))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->never())
            ->method('getDefaultConfigurationValueByPath');
        $this->configurationManager->expects($this->never())
            ->method('setLocalConfigurationValuesByPathValuePairs');

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('disableImageMagickDetailSettingsIfImageMagickIsDisabled');
    }

    /**
     * @test
     */
    public function setImageMagickDetailSettings()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $currentLocalConfiguration = [
            ['GFX/processor', 'GraphicsMagick'],
            ['GFX/processor_allowTemporaryMasksAsPng', 1],
            ['GFX/processor_effects', false],
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'getDefaultConfigurationValueByPath',
                'setLocalConfigurationValuesByPathValuePairs',
            ]
        );
        $this->configurationManager->expects($this->exactly(3))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->never())
            ->method('getDefaultConfigurationValueByPath');
        $this->configurationManager->expects($this->once())
            ->method('setLocalConfigurationValuesByPathValuePairs')
            ->withConsecutive([
                [
                    'GFX/processor_allowTemporaryMasksAsPng' => 0,
                ]
            ]);

        $this->expectException(ConfigurationChangedException::class);

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('setImageMagickDetailSettings');
    }

    /**
     * @test
     */
    public function doNotSetImageMagickDetailSettings()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $currentLocalConfiguration = [
            ['GFX/processor', ''],
            ['GFX/processor_allowTemporaryMasksAsPng', 0],
            ['GFX/processor_effects', 0],
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'getDefaultConfigurationValueByPath',
                'setLocalConfigurationValuesByPathValuePairs',
            ]
        );
        $this->configurationManager->expects($this->exactly(3))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->never())
            ->method('getDefaultConfigurationValueByPath');
        $this->configurationManager->expects($this->never())
            ->method('setLocalConfigurationValuesByPathValuePairs');

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('setImageMagickDetailSettings');
    }

    /**
     * @test
     * @dataProvider graphicsProcessorEffects
     *
     * @param mixed $currentValue
     * @param bool $expectedMigratedValue
     */
    public function migratesGraphicsProcessorEffects($currentValue, $expectedMigratedValue)
    {
        /** @var ConfigurationManager|\Prophecy\Prophecy\ObjectProphecy */
        $configurationManager = $this->prophesize(ConfigurationManager::class);
        $configurationManager->getLocalConfigurationValueByPath('GFX/processor')->willReturn('GraphicsMagick');
        $configurationManager->getLocalConfigurationValueByPath('GFX/processor_allowTemporaryMasksAsPng')->willReturn(false);
        $configurationManager->getLocalConfigurationValueByPath('GFX/processor_effects')->willReturn($currentValue);
        $configurationManager->setLocalConfigurationValuesByPathValuePairs([
            'GFX/processor_effects' => $expectedMigratedValue,
        ])->shouldBeCalled();

        $this->expectException(ConfigurationChangedException::class);

        $silentConfigurationUpgradeService = new SilentConfigurationUpgradeService($configurationManager->reveal());

        $this->callInaccessibleMethod($silentConfigurationUpgradeService, 'setImageMagickDetailSettings');
    }

    /**
     * @return array
     */
    public function graphicsProcessorEffects(): array
    {
        return [
            'integer 1' => [
                1,
                true,
            ],
            'integer 0' => [
                0,
                false,
            ],
            'integer -1' => [
                -1,
                false,
            ],
            'string "1"' => [
                '1',
                true,
            ],
            'string "0"' => [
                '0',
                false,
            ],
            'string "-1"' => [
                '-1',
                false,
            ],
        ];
    }

    /**
     * @test
     */
    public function migrateNonExistingLangDebug()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $currentLocalConfiguration = [
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            ]
        );

        $this->configurationManager->expects($this->exactly(1))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->never())
            ->method('setLocalConfigurationValueByPath');

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('migrateLangDebug');
    }

    /**
     * @test
     */
    public function migrateExistingLangDebug()
    {
        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $currentLocalConfiguration = [
            ['BE/lang/debug', false]
        ];
        $this->createConfigurationManagerWithMockedMethods(
            [
                'getLocalConfigurationValueByPath',
                'setLocalConfigurationValueByPath',
            ]
        );

        $this->configurationManager->expects($this->exactly(1))
            ->method('getLocalConfigurationValueByPath')
            ->will($this->returnValueMap($currentLocalConfiguration));
        $this->configurationManager->expects($this->once())
            ->method('setLocalConfigurationValueByPath')
            ->with($this->equalTo('BE/languageDebug'), false);

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $this->configurationManager);

        $silentConfigurationUpgradeServiceInstance->_call('migrateLangDebug');
    }

    /**
     * @test
     */
    public function migrateCacheHashOptions()
    {
        $oldConfig = [
            'FE/cHashOnlyForParameters' => 'foo,bar',
            'FE/cHashExcludedParameters' => 'bar,foo',
            'FE/cHashRequiredParameters' => 'bar,baz',
            'FE/cHashExcludedParametersIfEmpty' => '*'
        ];

        /** @var ConfigurationManager|ObjectProphecy $configurationManager */
        $configurationManager = $this->prophesize(ConfigurationManager::class);

        foreach ($oldConfig as $key => $value) {
            $configurationManager->getLocalConfigurationValueByPath($key)
                ->shouldBeCalled()
                ->willReturn($value);
        }

        $configurationManager->setLocalConfigurationValuesByPathValuePairs(\Prophecy\Argument::cetera())
            ->shouldBeCalled();
        $configurationManager->removeLocalConfigurationKeysByPath(\Prophecy\Argument::cetera())
            ->shouldBeCalled();

        $this->expectException(ConfigurationChangedException::class);

        /** @var $silentConfigurationUpgradeServiceInstance SilentConfigurationUpgradeService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $silentConfigurationUpgradeServiceInstance = $this->getAccessibleMock(
            SilentConfigurationUpgradeService::class,
            ['dummy'],
            [],
            '',
            false
        );

        $silentConfigurationUpgradeServiceInstance->_set('configurationManager', $configurationManager->reveal());

        $silentConfigurationUpgradeServiceInstance->_call('migrateCacheHashOptions');
    }
}
