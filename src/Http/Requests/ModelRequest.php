<?php
/**
 * Automate model requests.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2019
 * @license MIT
 */

namespace Laramore\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Laramore\Traits\Http\Requests\HasLaramoreRequest;

abstract class ModelRequest extends FormRequest
{
    use HasLaramoreRequest;
}
