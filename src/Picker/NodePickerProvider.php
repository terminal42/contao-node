<?php

declare(strict_types=1);

namespace Terminal42\NodeBundle\Picker;

use Contao\CoreBundle\DependencyInjection\Attribute\AsPickerProvider;
use Contao\CoreBundle\Picker\AbstractPickerProvider;
use Contao\CoreBundle\Picker\DcaPickerProviderInterface;
use Contao\CoreBundle\Picker\PickerConfig;

#[AsPickerProvider(priority: 132)]
class NodePickerProvider extends AbstractPickerProvider implements DcaPickerProviderInterface
{
    public function getDcaTable(PickerConfig|null $config = null): string
    {
        return 'tl_node';
    }

    public function getDcaAttributes(PickerConfig $config): array
    {
        $attributes = ['fieldType' => 'checkbox'];

        if ($fieldType = $config->getExtra('fieldType')) {
            $attributes['fieldType'] = $fieldType;
        }

        if ($this->supportsValue($config)) {
            $attributes['value'] = array_map('intval', explode(',', $config->getValue()));
        }

        if (\is_array($rootNodes = $config->getExtra('rootNodes'))) {
            $attributes['rootNodes'] = $rootNodes;
        }

        return $attributes;
    }

    public function convertDcaValue(PickerConfig $config, $value): int|string
    {
        return (int) $value;
    }

    public function getName(): string
    {
        return 'nodePicker';
    }

    public function supportsContext(string $context): bool
    {
        return 'node' === $context;
    }

    public function supportsValue(PickerConfig $config): bool
    {
        foreach (explode(',', $config->getValue()) as $id) {
            if (!is_numeric($id)) {
                return false;
            }
        }

        return true;
    }

    protected function getRouteParameters(PickerConfig|null $config = null): array
    {
        return ['do' => 'nodes'];
    }
}
