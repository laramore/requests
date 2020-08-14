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

use Laramore\Traits\Request\HasLaramoreRelatedRequest;

abstract class RelatedModelRequest extends ModelRequest
{
    use HasLaramoreRelatedRequest;
}
