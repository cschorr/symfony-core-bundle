<?php

namespace App\Service;

/**
 * Fluent builder for field configurations
 */
class FieldConfigBuilder
{
    private array $config;

    public function __construct(
        private string $name,
        private ?string $type,
        private EasyAdminFieldService $service
    ) {
        $this->config = [
            'name' => $name,
            'type' => $type ?? $this->autoDetectType(),
            'pages' => ['index', 'detail', 'form'],
        ];
    }

    public function type(string $type): self
    {
        $this->config['type'] = $type;
        return $this;
    }

    public function pages(array $pages): self
    {
        $this->config['pages'] = $pages;
        return $this;
    }

    public function label(string $label): self
    {
        $this->config['label'] = $label;
        return $this;
    }

    public function indexLabel(string $label): self
    {
        $this->config['indexLabel'] = $label;
        return $this;
    }

    public function required(bool $required = true): self
    {
        $this->config['required'] = $required;
        return $this;
    }

    public function columns(int $columns): self
    {
        $this->config['columns'] = $columns;
        return $this;
    }

    public function hideOnForm(bool $hide = true): self
    {
        $this->config['hideOnForm'] = $hide;
        return $this;
    }

    public function hideOnIndex(bool $hide = true): self
    {
        $this->config['hideOnIndex'] = $hide;
        return $this;
    }

    public function hideOnDetail(bool $hide = true): self
    {
        $this->config['hideOnDetail'] = $hide;
        return $this;
    }

    public function panel(string $panel): self
    {
        $this->config['panel'] = $panel;
        return $this;
    }

    public function multiple(bool $multiple = true): self
    {
        $this->config['multiple'] = $multiple;
        return $this;
    }

    public function autocomplete(bool $autocomplete = true): self
    {
        $this->config['autocomplete'] = $autocomplete;
        return $this;
    }

    public function help(string $help): self
    {
        $this->config['help'] = $help;
        return $this;
    }

    public function formTypeOption(string $key, $value): self
    {
        $this->config['formTypeOptions'][$key] = $value;
        return $this;
    }

    public function formatValue(callable $formatter): self
    {
        $this->config['formatValue'] = $formatter;
        return $this;
    }

    public function renderAsHtml(bool $renderAsHtml = true): self
    {
        $this->config['renderAsHtml'] = $renderAsHtml;
        return $this;
    }

    /**
     * Make this field link to the show action of the related entity.
     *
     * @param string|null $controllerClass Optional: specific controller class to link to.
     *                                     If null, auto-detects based on entity naming convention.
     * @return self
     */
    public function linkToShow(string $controllerClass = null): self
    {
        $this->config['linkToShow'] = true;
        if ($controllerClass) {
            $this->config['linkToShowController'] = $controllerClass;
        }
        return $this;
    }

    public function association(string $targetEntity, $choiceLabel = 'name'): self
    {
        $this->config['type'] = 'association';
        $this->config['targetEntity'] = $targetEntity;
        $this->config['choiceLabel'] = $choiceLabel;
        return $this;
    }

    public function countFormat(string $countLabel = null): self
    {
        $this->config['indexFormat'] = 'count';
        $this->config['countLabel'] = $countLabel ?? $this->config['label'] ?? ucfirst($this->name);
        return $this;
    }

    public function choices(array $choices): self
    {
        $this->config['type'] = 'choice';
        $this->config['choices'] = $choices;
        return $this;
    }

    public function currency(string $currency): self
    {
        $this->config['type'] = 'money';
        $this->config['currency'] = $currency;
        return $this;
    }

    public function renderAsSwitch(bool $asSwitch = true): self
    {
        $this->config['renderAsSwitch'] = $asSwitch;
        return $this;
    }

    public function option(string $key, $value): self
    {
        $this->config[$key] = $value;
        return $this;
    }

    public function indexOnly(): self
    {
        $this->config['pages'] = ['index'];
        return $this;
    }

    public function formOnly(): self
    {
        $this->config['pages'] = ['form'];
        return $this;
    }

    public function detailOnly(): self
    {
        $this->config['pages'] = ['detail'];
        return $this;
    }

    public function notOnIndex(): self
    {
        $this->config['pages'] = ['detail', 'form'];
        return $this;
    }

    public function build(): array
    {
        return $this->config;
    }

    private function autoDetectType(): string
    {
        return match (true) {
            str_contains($this->name, 'email') => 'email',
            str_contains($this->name, 'phone') || str_contains($this->name, 'cell') => 'telephone',
            str_contains($this->name, 'url') || str_contains($this->name, 'website') => 'url',
            str_contains($this->name, 'country') => 'country',
            str_contains($this->name, 'date') => 'date',
            str_contains($this->name, 'time') => 'datetime',
            str_contains($this->name, 'active') || str_contains($this->name, 'enabled') => 'boolean',
            $this->name === 'id' => 'id',
            default => 'text',
        };
    }
}
