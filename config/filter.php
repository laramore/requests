<?php

namespace Laramore\Http\Filters;

return [

    /*
    |--------------------------------------------------------------------------
    | Default filter configurations
    |--------------------------------------------------------------------------
    |
    | This option defines the default configurations.
    |
    */

    'configurations' => [
        Append::class => [
            'allowed_values' => ['true', 'false'],
        ],
        Date::class => [
            //
        ],
        Filter::class => [

        ],
        OrderBy::class => [
            'allowed_values' => ['asc', 'desc', 'random'],
        ],
        Page::class => [
            //
        ],
        PerPage::class => [
            'min' => 1,
            'max' => 100,
        ],
        Search::class => [
            'allowed_values' => ['or', 'and'],
            'only' => ['eq', 'equal', 'not_eq', 'not_equal', 'like', 'not_like'],
        ],
        Trash::class => [
            'allowed_values' => ['with', 'without', 'only'],
        ],
    ],

];
