@if(isset($options->model) && isset($options->type))
    @if(class_exists($options->model))
        @php
        $relationshipField = $row->field;

        $extraOptions = json_decode($options->extra_options);

        $columns = [];

        foreach ($extraOptions->with_pivot as $option) {
            $columns[] = $option->column;
        }

        $relations = isset($dataTypeContent) ? $dataTypeContent->belongsToMany($options->model, $options->pivot_table)
                                                                ->withPivot(implode(", ", $columns))
                                                                ->getResults()->toArray() : array();

        $relatedIds = [];

        foreach ($relations as $relation) {
            $relatedIds[] = $relation['id'];
        }

        $relationshipOptions = app($options->model)->all();
        @endphp

        @foreach ($extraOptions->with_pivot as $option)
            <select class="form-control select-relationship-with-pivot"
                    data-relationship-field="{{ $relationshipField }}" data-pivot-column="{{ $option->column }}">
                @foreach ($relationshipOptions as $relationshipOption)
                    <option value="{{ $relationshipOption->id }}" style="{{ in_array($relationshipOption->id, $relatedIds) ? "display: none;" : "" }}">{{ $relationshipOption->{$options->label} }}</option>
                @endforeach
            </select>

            <div class="selected-relationship-with-pivot-container">
                @foreach ($relations as $relation)
                    <div class="selected-relationship" data-relationship-value="{{ $relation['id'] }}">
                        <span>{{ $relation[$options->label] }}</span>
                        <input type="text" value="{{ $relation['id'] }}" name="{{ $relationshipField . '_ids[]' }}" hidden>
                        <input value="{{ $relation['pivot'][$option->column] }}" class="form-control with-pivot-relationship-value" name="{{ $relationshipField . '_' . $option->column . '_values[]' }}" type="text" placeholder="Column Value">
                        <span class="remove-relationship">Remove</span>
                    </div>
                @endforeach
            </div>
        @endforeach
    @else

        cannot make relationship because {{ $options->model }} does not exist.

    @endif
@endif