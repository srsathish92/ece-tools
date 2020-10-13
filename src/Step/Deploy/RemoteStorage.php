<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy;

use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Config\RemoteStorage as RemoteStorageConfig;
use Psr\Log\LoggerInterface;

/**
 * Enable or disable remote storage during deployment.
 */
class RemoteStorage implements StepInterface
{
    /**
     * @var RemoteStorageConfig
     */
    private $config;

    /**
     * @var MagentoShell
     */
    private $magentoShell;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param RemoteStorageConfig $config
     * @param MagentoShell $magentoShell
     * @param MagentoVersion $magentoVersion
     * @param LoggerInterface $logger
     */
    public function __construct(
        RemoteStorageConfig $config,
        MagentoShell $magentoShell,
        MagentoVersion $magentoVersion,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->magentoShell = $magentoShell;
        $this->magentoVersion = $magentoVersion;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function execute(): void
    {
        try {
            if (!$this->magentoVersion->isGreaterOrEqual('2.4.2')) {
                return;
            }
        } catch (UndefinedPackageException $exception) {
            throw new StepException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $adapter = $this->config->getAdapter();

        if ($adapter) {
            $config = $this->config->getConfig();

            try {
                $arguments = [
                    $adapter,
                    $config['bucket'] ?? '',
                    $config['region'] ?? '',
                    $config['prefix'] ?? '',
                ];

                if (!empty($config['key']) && !empty($config['secret'])) {
                    $arguments[] = '--access-key=' . $config['key'];
                    $arguments[] = '--secret-key=' . $config['secret'];
                }

                $this->magentoShell->execute(sprintf(
                    'remote-storage:enable %s',
                    implode(' ', $arguments)
                ));
            } catch (ShellException $exception) {
                $this->logger->warning($exception->getMessage());

                return;
            }

            $this->logger->info(sprintf(
                'Remote storage with driver "%s" was enabled',
                $adapter
            ));

            return;
        }

        $this->magentoShell->execute('remote-storage:disable');
    }
}
