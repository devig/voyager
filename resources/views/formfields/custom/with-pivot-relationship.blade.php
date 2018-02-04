@if(isset($options->model) && isset($options->type))
    @if(class_exists($options->model))
        @php
        // Relationship Name
        $relationshipField = $row->field;

        // All relationship options
        $relationshipOptions = app($options->model)->all();

        // Get Local IDs
        $localIds = $relationshipOptions->pluck('id')->toArray();

        // Get Foreign Key
        $foreignKey = strtolower((new \ReflectionClass($relationshipOptions->first()))->getShortName()) . '_id';

        // Extra Options
        $extraOptions = json_decode($options->extra_options);

        // Pivot Columns
        $columns = [];

        foreach ($extraOptions->with_pivot as &$option) {
            // Get pivot related data where necessary
            if (isset($option->pivot_field_type) && $option->pivot_field_type == "dropdown" && isset($option->pivot_model)) {
                if (class_exists($option->pivot_model)) {
                    $model = '\\' . $option->pivot_model;
                    $option->data = $model::whereIn($foreignKey, $localIds)->pluck($option->pivot_label, 'id');
                }
            }

            // Pivot Columns
            $columns[] = $option->column;
        }

        // Get relationships
        $relations = isset($dataTypeContent) ? $dataTypeContent->belongsToMany($options->model, $options->pivot_table)
                                                                ->withPivot($columns)
                                                                ->getResults()->toArray() : array();

        // Already linked IDs
        $relatedIds = [];
        foreach ($relations as $relation) {
            $relatedIds[] = $relation['id'];
        }
        @endphp

        <select class="form-control select-relationship-with-pivot" data-relationship-field="{{ $relationshipField }}">
            @foreach ($relationshipOptions as $relationshipOption)
                <option value="{{ $relationshipOption->id }}" style="{{ in_array($relationshipOption->id, $relatedIds) ? "display: none;" : "" }}">{{ $relationshipOption->{$options->label} }}</option>
            @endforeach
        </select>

        <div class="selected-relationship-with-pivot-container" data-relationship-field="{{ $relationshipField }}">
            @foreach ($relations as $relation)
                <div class="selected-relationship" data-relationship-value="{{ $relation['id'] }}">
                    <span>{{ $relation[$options->label] }}</span>
                    <input type="text" value="{{ $relation['id'] }}" name="{{ $relationshipField . '_ids[]' }}" hidden>
                    @foreach ($extraOptions->with_pivot as $relationOption)
                        @if (isset($relationOption->pivot_field_type) && $relationOption->pivot_field_type == "text")
                            <input value="{{ $relation['pivot'][$relationOption->column] }}" class="form-control with-pivot-relationship-value" name="{{ $relationshipField . '_' . $relationOption->column . '_values[]' }}" type="text" placeholder="{{ $relationOption->column }} Value">
                        @elseif (isset($relationOption->pivot_field_type) && $relationOption->pivot_field_type == "dropdown")
                            @if (isset($relationOption->data))
                                <label class='pivot-name'>{{ $relationOption->pivot_name }}</label>
                                <select class='form-control with-pivot-relationship-value' name='{{ $relationshipField }}_{{$relationOption->column}}_values[]'>;
                                @foreach ($relationOption->data as $key => $selectOption)
                                    <option value='{{ $key }}' {{ $relation['pivot'][$relationOption->column] == $key ? 'selected' : '' }}>{{$selectOption}}</option>
                                @endforeach
                                </select>
                            @endif
                        @endif
                    @endforeach

                    <span class="remove-relationship">Remove</span>
                </div>
            @endforeach
        </div>
    @else
        cannot make relationship because {{ $options->model }} does not exist.
    @endif
@endif

@section('pivot-javascript')
    <script>
        <?php
            $html = '';
            foreach ($extraOptions->with_pivot as $key => $pivotOption) {
                if (isset($pivotOption->data)) {
                    $html .= "<label class='pivot-name'>{$pivotOption->pivot_name}</label>";

                    $html .= "<select class='form-control with-pivot-relationship-value' name='{$relationshipField}_{$pivotOption->column}_values[]'>";
                    foreach ($pivotOption->data as $key => $selectOption) {
                        $html .= "<option value='{$key}'>{$selectOption}</option>";
                    }
                    $html .= "</select>";
                } else if ($pivotOption->pivot_field_type == "text") {
                    $html .= "<input class='form-control with-pivot-relationship-value' name='{$relationshipField}_{$pivotOption->column}_values[]' type='text' placeholder='{$pivotOption->column} Value'>";
                }
            }
        ?>

        var {{ $relationshipField }} = "<?=$html?>";

        $('document').ready(function () {
            $('.select-relationship-with-pivot[data-relationship-field={{$relationshipField}}]').val('');
            $('.select-relationship-with-pivot[data-relationship-field={{$relationshipField}}]').on('change', function (e) {
                var relationshipName = $(this).attr('data-relationship-field');

                var value = $(this).val();
                var name = $(this).find(':selected').html();

                $(this).val('');
                $(this).find('option[value=' + value + ']').toggle();

                var html = '<div class="selected-relationship" data-relationship-value="' + value + '">';
                html += '<span>' + name + '</span>';
                html += '<input type="text" value="' + value + '" name="' + relationshipName + '_ids[]" hidden>';
                html += window[relationshipName];
                html += '<span class="remove-relationship">Remove</span>'
                html += '</div>';


                $('.selected-relationship-with-pivot-container[data-relationship-field={{$relationshipField}}]').append(html);
            });

            $('body').on('click', '.remove-relationship', function () {
                var optionValue = $(this).closest('.selected-relationship').attr('data-relationship-value');
                $('.select-relationship-with-pivot[data-relationship-field={{$relationshipField}}]').find('option[value=' + optionValue + ']').toggle();

                if (confirm('Are you sure you want to remove this relationship?')) {
                    $(this).closest('.selected-relationship').remove();
                }
            });
        });
    </script>
@endsection