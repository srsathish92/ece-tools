<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Build;

use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Util\ArrayManager;

/**
 * Validates that configuration file contains enough data for running static content deploy in build phase.
 */
class ConfigFileStructure implements ValidatorInterface
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var File
     */
    private $file;

    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @param ArrayManager $arrayManager
     * @param File $file
     * @param DirectoryList $directoryList
     * @param Validator\ResultFactory $resultFactory
     */
    public function __construct(
        ArrayManager $arrayManager,
        File $file,
        DirectoryList $directoryList,
        Validator\ResultFactory $resultFactory
    ) {
        $this->directoryList = $directoryList;
        $this->arrayManager = $arrayManager;
        $this->file = $file;
        $this->resultFactory = $resultFactory;
    }

    /**
     * @inheritdoc
     */
    public function validate(): Validator\ResultInterface
    {
        $configFile = $this->directoryList->getMagentoRoot() . '/app/etc/config.php';
        $config = $this->file->requireFile($configFile);

        $flattenedConfig = $this->arrayManager->flatten($config);
        $websites = $this->arrayManager->filter($flattenedConfig, 'scopes/websites', false);
        $stores = $this->arrayManager->filter($flattenedConfig, 'scopes/stores', false);

        if (count($stores) === 0 && count($websites) === 0) {
            $error = 'No stores/website/locales found in config.php';
            $suggestion = implode(
                PHP_EOL,
                [
                    'To speed up the deploy process, please run the following commands:',
                    '1. php ./vendor/bin/ece-tools config:dump',
                    '2. git add -f app/etc/config.php',
                    '3. git commit -m \'Updating config.php\'',
                    '4. git push'
                ]
            );

            return $this->resultFactory->create(
                Validator\ResultInterface::ERROR,
                [
                    'error' => $error,
                    'suggestion' => $suggestion
                ]
            );
        }

        return $this->resultFactory->create(Validator\ResultInterface::SUCCESS);
    }
}