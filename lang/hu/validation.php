<?php

return [
    'required' => 'A(z) :attribute megadása kötelező.',
    'string' => 'A(z) :attribute csak szöveg lehet.',
    'email' => 'A(z) :attribute nem érvényes e-mail cím.',
    'max' => [
        'string' => 'A(z) :attribute legfeljebb :max karakter lehet.',
        'file' => 'A(z) :attribute legfeljebb :max kilobájt lehet.',
    ],
    'min' => [
        'string' => 'A(z) :attribute legalább :min karakter legyen.',
    ],
    'confirmed' => 'A(z) :attribute megerősítése nem egyezik.',
    'unique' => 'A(z) :attribute már foglalt.',
    'url' => 'A(z) :attribute nem érvényes link.',
    'image' => 'A(z) :attribute csak kép lehet.',
    'mimes' => 'A(z) :attribute típusa csak :values lehet.',
    'current_password' => 'A jelenlegi jelszó helytelen.',
    'date' => 'A(z) :attribute nem érvényes dátum.',
    'date_format' => 'A(z) :attribute formátuma nem megfelelő.',
    'in' => 'A kiválasztott :attribute érvénytelen.',
    'boolean' => 'A(z) :attribute értéke csak igen vagy nem lehet.',

    'attributes' => [
        'email' => 'e-mail cím',
        'password' => 'jelszó',
        'name' => 'név',
        'title' => 'cím',
        'description' => 'megjegyzés',
        'event_date' => 'dátum',
        'event_time' => 'időpont',
        'category' => 'fajta',
        'emoji' => 'jelölés',
        'url' => 'link',
        'photo' => 'kép',
    ],
];