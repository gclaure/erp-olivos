<?php

return [
    'accepted' => 'El campo :attribute debe ser aceptado.',
    'active_url' => 'El campo :attribute no es una URL válida.',
    'after' => 'El campo :attribute debe ser una fecha posterior a :date.',
    'alpha' => 'El campo :attribute solo debe contener letras.',
    'alpha_dash' => 'El campo :attribute solo debe contener letras, números, guiones y guiones bajos.',
    'alpha_num' => 'El campo :attribute solo debe contener letras y números.',
    'array' => 'El campo :attribute debe ser un arreglo.',
    'before' => 'El campo :attribute debe ser una fecha anterior a :date.',

    'between' => [
        'numeric' => 'El campo :attribute debe estar entre :min y :max.',
        'file' => 'El archivo :attribute debe pesar entre :min y :max kilobytes.',
        'string' => 'El campo :attribute debe contener entre :min y :max caracteres.',
        'array' => 'El campo :attribute debe contener entre :min y :max elementos.',
    ],

    'boolean' => 'El campo :attribute debe ser verdadero o falso.',

    'confirmed' => 'La confirmación de :attribute no coincide.',

    'date' => 'El campo :attribute no corresponde a una fecha válida.',

    'date_format' => 'El campo :attribute no corresponde al formato :format.',

    'different' => 'Los campos :attribute y :other deben ser diferentes.',

    'digits' => 'El campo :attribute debe tener :digits dígitos.',

    'digits_between' => 'El campo :attribute debe tener entre :min y :max dígitos.',

    'email' => 'El campo :attribute debe ser una dirección de correo válida.',

    'filled' => 'El campo :attribute es obligatorio.',

    'exists' => 'El :attribute seleccionado es inválido.',

    'image' => 'El campo :attribute debe ser una imagen.',

    'in' => 'El :attribute seleccionado es inválido.',

    'integer' => 'El campo :attribute debe ser un número entero.',

    'ip' => 'El campo :attribute debe ser una dirección IP válida.',

    'max' => [
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'file' => 'El archivo :attribute no debe ser mayor que :max kilobytes.',
        'string' => 'El campo :attribute no debe contener más de :max caracteres.',
        'array' => 'El campo :attribute no debe contener más de :max elementos.',
    ],

    'mimes' => 'El campo :attribute debe ser un archivo de tipo: :values.',

    'min' => [
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'file' => 'El archivo :attribute debe pesar al menos :min kilobytes.',
        'string' => 'El campo :attribute debe contener al menos :min caracteres.',
        'array' => 'El campo :attribute debe contener al menos :min elementos.',
    ],

    'not_in' => 'El :attribute seleccionado es inválido.',

    'numeric' => 'El campo :attribute debe ser un número.',

    'regex' => 'El formato del campo :attribute es inválido.',

    'required' => 'El campo :attribute es obligatorio.',

    'required_if' => 'El campo :attribute es obligatorio cuando :other es :value.',

    'required_unless' => 'El campo :attribute es obligatorio a menos que :other esté en :values.',

    'required_with' => 'El campo :attribute es obligatorio cuando :values está presente.',

    'required_with_all' => 'El campo :attribute es obligatorio cuando :values están presentes.',

    'required_without' => 'El campo :attribute es obligatorio cuando :values no está presente.',

    'required_without_all' => 'El campo :attribute es obligatorio cuando ninguno de :values está presente.',

    'same' => 'Los campos :attribute y :other deben coincidir.',

    'size' => [
        'numeric' => 'El campo :attribute debe ser :size.',
        'file' => 'El archivo :attribute debe pesar :size kilobytes.',
        'string' => 'El campo :attribute debe contener :size caracteres.',
        'array' => 'El campo :attribute debe contener :size elementos.',
    ],

    'string' => 'El campo :attribute debe ser una cadena de texto.',

    'timezone' => 'El :attribute debe ser una zona válida.',

    'unique' => 'El valor de :attribute ya está en uso.',

    'url' => 'El formato de :attribute es inválido.',

    'uploaded' => 'El campo :attribute no se pudo subir.',

    'password' => [
        'letters' => 'El campo :attribute debe contener al menos una letra.',
        'mixed' => 'El campo :attribute debe contener al menos una letra mayúscula y una minúscula.',
        'numbers' => 'El campo :attribute debe contener al menos un número.',
        'symbols' => 'El campo :attribute debe contener al menos un símbolo.',
        'uncompromised' => 'El campo :attribute ha aparecido en una filtración de datos. Por favor, elige un :attribute diferente.',
    ],

    'attributes' => [
        'name' => 'nombre',
        'description' => 'descripción',
        'code' => 'código',
        'price' => 'precio',
        'category_ids' => 'categorías',
        'unit_of_measure_id' => 'unidad de medida',
        'abbreviation' => 'abreviatura',
        'image' => 'imagen',
        'password' => 'contraseña',
        'password_confirmation' => 'confirmación de la contraseña',
    ],
];
