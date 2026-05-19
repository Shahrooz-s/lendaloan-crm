<?php
namespace Modules\Invoice\Fields;

use Modules\Core\Contracts\Fields\Customfieldable;
use Modules\Core\Fields\Field;

class CustomTextField extends Field implements Customfieldable
{
    /**
     * Field component.
     */
    protected static $component = 'custom-text-field';

    /**
     * Append short text before the field.
     */
    public ?string $appendText = null;

    /**
     * Prepend short text after the field.
     */
    public ?string $prependText = null;

    /**
     * Initialize Numeric field
     *
     * @param  string  $attribute
     * @param  string|null  $label
     */
    public function __construct($attribute, $label = null)
    {
        parent::__construct($attribute, $label);

        $this
            ->provideSampleValueUsing(fn () => rand(20000, 40000))
            ->useSearchColumn([$this->attribute => '='])
            ->resolveUsing(fn ($model, $attribute) => is_null($model->{$attribute}) ? 0 : (float) $model->{$attribute});
    }

    /**
     * Resolve the displayable field value
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return mixed
     */
    public function resolveForDisplay($model)
    {
        $value = $this->resolve($model);

        if (is_callable($this->displayCallback)) {
            return call_user_func_array($this->displayCallback, [$model, $value, $this->attribute]);
        }

        if (! is_float($value)) {
            $value = (float) $value;
        }

        return $this->appendText.$value.$this->prependText;
    }

    /**
     * Prepend short text before the field.
     */
    public function prependText(string $text): static
    {
        $this->prependText = $text;

        return $this;
    }

    /**
     * Append short text after the field.
     */
    public function appendText(string $text): static
    {
        $this->appendText = $text;

        return $this;
    }

    /**
     * Create the custom field value column in database.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $table
     */
    public static function createValueColumn($table, string $fieldId): void
    {
        $table->decimal($fieldId, 15, 3)->index()->nullable();
    }

    /**
     * Set the numeric field decimal precision, applicable when no currency is provided.
     */
    public function precision(int $precision): static
    {
        $this->withMeta(['attributes' => ['precision' => $precision]]);

        return $this;
    }

    /**
     * Serialize for front end
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'prependText' => $this->prependText,
            'appendText' => $this->appendText,
        ]);
    }
}
