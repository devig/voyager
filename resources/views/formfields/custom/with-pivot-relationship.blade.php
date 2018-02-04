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

        $localIds = $relationshipOptions->pluck('id')->toArray();
        $foreignKey = strtolower((new \ReflectionClass($relationshipOptions->first()))->getShortName()) . '_id';
        @endphp

        @foreach ($extraOptions->with_pivot as $option)
            {{-- Check if there's a pivot model --}}
            @if (isset($option->pivot_field_type) && $option->pivot_field_type == "dropdown" && isset($option->pivot_model))
                <?php
                    if (class_exists($option->pivot_model)) {
                        $model = '\\' . $option->pivot_model;
                        $option->data = $model::whereIn($foreignKey, $localIds)->pluck($option->pivot_label, 'id');
                    }
                ?>
            @endif
            <select class="form-control select-relationship-with-pivot"
                    data-relationship-field="{{ $relationshipField }}"
                    data-pivot-column="{{ $option->column }}"
                    data-pivot-field-type="{{ $option->pivot_field_type }}">
                @foreach ($relationshipOptions as $relationshipOption)
                    <option value="{{ $relationshipOption->id }}" style="{{ in_array($relationshipOption->id, $relatedIds) ? "display: none;" : "" }}">{{ $relationshipOption->{$options->label} }}</option>
                @endforeach
            </select>

            <div class="selected-relationship-with-pivot-container">
                @foreach ($relations as $relation)
                    <div class="selected-relationship" data-relationship-value="{{ $relation['id'] }}">
                        <span>{{ $relation[$options->label] }}</span>

                        @if (isset($option->pivot_field_type) && $option->pivot_field_type == "string")
                            <input type="text" value="{{ $relation['id'] }}" name="{{ $relationshipField . '_ids[]' }}" hidden>
                            <input value="{{ $relation['pivot'][$option->column] }}" class="form-control with-pivot-relationship-value" name="{{ $relationshipField . '_' . $option->column . '_values[]' }}" type="text" placeholder="Column Value">
                        @elseif (isset($option->pivot_field_type) && $option->pivot_field_type == "dropdown")
                            @if (isset($option->data))
                                <label class='pivot-name'>{{ $option->pivot_name }}</label>;
                                <select class='form-control with-pivot-relationship-value' name='{{ $relationshipField }}_{{$option->column}}_values[]'>;
                                @foreach ($option->data as $key => $selectOption)
                                    <option value='{{ $key }}' {{ $relation['pivot'][$option->column] == $key ? 'selected' : '' }}>{{$selectOption}}</option>
                                @endforeach
                                </select>
                            @endif
                        @endif

                        <span class="remove-relationship">Remove</span>
                    </div>
                @endforeach
            </div>
        @endforeach
    @else
        cannot make relationship because {{ $options->model }} does not exist.
    @endif
@endif

@section('pivot-javascript')
    <script>
        <?php
            foreach ($extraOptions->with_pivot as $option) {
                if (isset($option->data)) {
                    $pivotData = "<label class='pivot-name'>{$option->pivot_name}</label>";
                    $pivotData .= "<select class='form-control with-pivot-relationship-value' name='{$relationshipField}_{$option->column}_values[]'>";
                    foreach ($option->data as $key => $selectOption) {
                        $pivotData .= "<option value='{$key}'>{$selectOption}</option>";
                    }
                    $pivotData .= "</select>";
                    ?>
                        var {{ $relationshipField }} = "<?=$pivotData?>";
                    <?php
                }
            }
        ?>

        $('document').ready(function () {
            $('.select-relationship-with-pivot').val('');
            $('.select-relationship-with-pivot').on('change', function (e) {
                var relationshipName = $(this).attr('data-relationship-field');
                var pivotColumn = $(this).attr('data-pivot-column');
                var pivotFieldType = $(this).attr('data-pivot-field-type');

                var value = $(this).val();
                var name = $(this).find(':selected').html();

                $(this).val('');
                $(this).find('option[value=' + value + ']').toggle();

                var html = '<div class="selected-relationship" data-relationship-value="' + value + '">';
                html += '<span>' + name + '</span>';
                html += '<input type="text" value="' + value + '" name="' + relationshipName + '_ids[]" hidden>';

                if (pivotFieldType == "text") {
                    html += '<input class="form-control with-pivot-relationship-value" name="' + relationshipName + '_' + pivotColumn + '_values[]" type="text" placeholder="Column Value">';
                } else if (pivotFieldType == "dropdown") {
                    console.log(window[relationshipName]);
                    html += window[relationshipName];
                }

                html += '<span class="remove-relationship">Remove</span>'
                html += '</div>';


                $('.selected-relationship-with-pivot-container').append(html);
            });

            $('body').on('click', '.remove-relationship', function () {
                var optionValue = $(this).closest('.selected-relationship').attr('data-relationship-value');
                $('.select-relationship-with-pivot').find('option[value=' + optionValue + ']').toggle();

                if (confirm('Are you sure you want to remove this relationship?')) {
                    $(this).closest('.selected-relationship').remove();
                }
            });
        });
    </script>
@endsection