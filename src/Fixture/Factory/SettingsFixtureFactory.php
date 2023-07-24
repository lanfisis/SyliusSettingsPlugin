<?php

/*
 * This file is part of SyliusSettingsPlugin corporate website.
 *
 * (c) SyliusSettingsPlugin <sylius+syliussettingsplugin@monsieurbiz.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MonsieurBiz\SyliusSettingsPlugin\Fixture\Factory;

use DateTime;
use MonsieurBiz\SyliusSettingsPlugin\Entity\Setting\SettingInterface;
use MonsieurBiz\SyliusSettingsPlugin\Settings\RegistryInterface;
use MonsieurBiz\SyliusSettingsPlugin\Settings\SettingsInterface;
use Sylius\Bundle\CoreBundle\Fixture\Factory\AbstractExampleFactory;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class SettingsFixtureFactory extends AbstractExampleFactory
{
    private RegistryInterface $settingsRegistry;
    private OptionsResolver $optionsResolver;
    private ChannelRepositoryInterface $channelRepository;
    private FactoryInterface $settingFactory;

    public function __construct(
        RegistryInterface $settingsRegistry,
        ChannelRepositoryInterface $channelRepository,
        FactoryInterface $settingFactory
    )
    {
        $this->settingsRegistry = $settingsRegistry;
        $this->channelRepository = $channelRepository;
        $this->settingFactory = $settingFactory;
        $this->optionsResolver = new OptionsResolver();

        $this->configureOptions($this->optionsResolver);
    }

    public function create(array $options = []): SettingInterface
    {
        $options = $this->optionsResolver->resolve($options);

        /** @var SettingsInterface $settings */
        $settings = $this->settingsRegistry->getByAlias($options['alias']);
        ['vendor' => $vendor, 'plugin' => $plugin] = $settings->getAliasAsArray();

        /** @var $setting SettingInterface */
        $setting = $this->settingFactory->createNew();
        $setting->setVendor($vendor);
        $setting->setPlugin($plugin);
        $setting->setPath($options['path']);
        $setting->setLocaleCode($options['locale']);
        $setting->setStorageType($options['type']);

        switch ($options['type']) {
            case SettingInterface::STORAGE_TYPE_BOOLEAN:
                $options['value'] = (bool) $options['value'];
                break;
            case SettingInterface::STORAGE_TYPE_INTEGER:
                $options['value'] = (int) $options['value'];
                break;
            case SettingInterface::STORAGE_TYPE_FLOAT:
                $options['value'] = (float) $options['value'];
                break;
            case SettingInterface::STORAGE_TYPE_JSON:
                if (!is_array($options['value'])) {
                    $options['value'] = json_decode($options['value']);
                }
                break;
            case SettingInterface::STORAGE_TYPE_DATE:
            case SettingInterface::STORAGE_TYPE_DATETIME:
                if (is_int($options['value'])) {
                    $options['value'] = (new DateTime())->setTimestamp($options['value']);
                } else {
                    $options['value'] = new DateTime($options['value']);
                }
                break;
        }

        $setting->setValue($options['value']);

        if (null !== $options['channel']) {
            /** @var ?ChannelInterface $channel */
            $channel = $this->channelRepository->findOneBy(['code' => $options['channel']]);
            $setting->setChannel($channel);
        }

        return $setting;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('alias', '')
            ->setAllowedTypes('alias', 'string')
            ->setDefault('path', '')
            ->setAllowedTypes('path', 'string')
            ->setDefault('channel', null)
            ->setAllowedTypes('channel', ['null', 'string'])
            ->setDefault('locale', null)
            ->setAllowedTypes('locale', ['null', 'string'])
            ->setDefault('type', 'text')
            ->setAllowedTypes('type', 'string')
            ->setAllowedValues('type', ['text', 'boolean', 'integer', 'float', 'json', 'date', 'datetime'])
            ->setDefault('value', null)
            ->setAllowedTypes('value', ['null', 'string', 'integer', 'bool', 'float', 'Datetime', 'array'])
        ;
    }
}
