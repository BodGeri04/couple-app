<?php

return [
    'required' => 'A(z) :attribute megadása kötelező.',
    'email' => 'A(z) :attribute nem érvényes e-mail cím.',
    'string' => 'A(z) :attribute csak szöveg lehet.',
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

    'attributes' => [
        'email' => 'e-mail cím',
        'password' => 'jelszó',
        'name' => 'név',
        'title' => 'cím',
        'url' => 'link',
        'photo' => 'kép',
    ],
];